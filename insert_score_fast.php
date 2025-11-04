<?php
// session_start();
include 'connect.php'; 

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Yêu cầu không hợp lệ.");
}

// 1. LẤY DỮ LIỆU TỪ FORM
$user_id = $_POST['user_id'] ?? 0;
$round_category_id = $_POST['round_category_id'] ?? 0;
$bow_category_id = $_POST['bow_category_id'] ?? 0;
$date_recorded = $_POST['date_recorded'] ?? date('Y-m-d');
$note = $_POST['note'] ?? '';
$competition_id = $_POST['competition_id'] ?? NULL;
// $total_score_manual = $_POST['total_score'] ?? 0; // KHÔNG CẦN NỮA
$context = $_POST['context'] ?? 'practice';
$is_practice = ($context == 'practice') ? 1 : 0;
$location = $_POST['location'] ?? 'Archery Range';
$scores_array = $_POST['scores'] ?? [];

// -----------------------------------------------------------------
// NÂNG CẤP (SUGGESTION 1): TỰ ĐỘNG TÍNH TỔNG ĐIỂM
// -----------------------------------------------------------------
$calculated_total_score = 0;
$total_arrows_parsed = 0; // Biến đếm để kiểm tra
$validation_errors = []; // Mảng lưu lỗi

foreach ($scores_array as $range_id => $ends) {
    foreach ($ends as $end_num => $arrow_string) {
        
        $arrows = explode(",", $arrow_string);
        $arrow_count = 0;
        
        // Lặp 1: Kiểm tra và tính
        foreach ($arrows as $arrow_score) {
            $val = strtoupper(trim($arrow_score));
            if ($val !== '') { // Chỉ đếm nếu không phải chuỗi rỗng
                $arrow_count++;
                if ($val == 'X' || $val == '10') { $calculated_total_score += 10; }
                elseif ($val == 'M' || $val == '0') { $calculated_total_score += 0; }
                else {
                    $score_val = intval($val);
                    if ($score_val >= 1 && $score_val <= 9) {
                        $calculated_total_score += $score_val;
                    } else {
                        // Ký tự không hợp lệ
                        $validation_errors[] = "Lỗi Cự ly $range_id, Lượt $end_num: Ký tự không hợp lệ '<b>$val</b>'. (Chuỗi: $arrow_string)";
                    }
                }
            }
        }

        // -----------------------------------------------------------------
        // NÂNG CẤP (SUGGESTION 3): XÁC THỰC 6 MŨI TÊN
        // -----------------------------------------------------------------
        if ($arrow_count != 6) {
            $validation_errors[] = "Lỗi Cự ly $range_id, Lượt $end_num: Bạn đã nhập <b>$arrow_count</b> mũi tên, nhưng phải là 6. (Chuỗi: $arrow_string)";
        }
    }
}
// -----------------------------------------------------------------
// KẾT THÚC NÂNG CẤP
// -----------------------------------------------------------------


// 2. KIỂM TRA
if ($user_id == 0 || $round_category_id == 0 || $bow_category_id == 0 || empty($scores_array)) {
    die("Lỗi: Dữ liệu đầu vào không hợp lệ. Vui lòng quay lại và điền đầy đủ thông tin.");
}

// NẾU CÓ LỖI XÁC THỰC, HIỂN THỊ LỖI VÀ DỪNG LẠI
if (!empty($validation_errors)) {
    echo "<h1>Đã phát hiện lỗi nhập liệu!</h1>";
    echo "<p>Giao dịch đã bị hủy bỏ. Dữ liệu của bạn chưa được lưu. Vui lòng kiểm tra các lỗi sau:</p>";
    echo "<ul>";
    foreach ($validation_errors as $error) {
        echo "<li style='color: red; margin-bottom: 10px;'>$error</li>";
    }
    echo "</ul>";
    echo "<a href='javascript:history.back()'>⬅ Quay lại và sửa lỗi</a>"; // Quay lại trang Bước 2
    exit;
}

// 3. BẮT ĐẦU SQL TRANSACTION (Chỉ chạy nếu không có lỗi)
$conn->begin_transaction();

try {
    // BƯỚC A: INSERT VÀO `rounds`
    $sql_rounds = "INSERT INTO rounds (date_recorded, is_practice, location) VALUES (?, ?, ?)";
    $stmt_rounds = $conn->prepare($sql_rounds);
    if (!$stmt_rounds) throw new Exception("Prepare rounds failed: " . $conn->error);
    $stmt_rounds->bind_param("sis", $date_recorded, $is_practice, $location);
    $stmt_rounds->execute();
    $round_id = $conn->insert_id;
    $stmt_rounds->close();

    // BƯỚC B: INSERT VÀO `scores`
    $sql_scores = "INSERT INTO scores (user_id, round_id, competition_id, archer_category_id, total_score, note) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_scores = $conn->prepare($sql_scores);
    if (!$stmt_scores) throw new Exception("Prepare scores failed: " . $conn->error);
    $stmt_scores->bind_param("iisiis", $user_id, $round_id, $competition_id, $bow_category_id, $calculated_total_score, $note);
    $stmt_scores->execute();
    $score_id = $conn->insert_id;
    $stmt_scores->close();

    // CHUẨN BỊ
    $sql_ranges = "INSERT INTO ranges (score_id, range_category_id) VALUES (?, ?)";
    $stmt_ranges = $conn->prepare($sql_ranges);
    if (!$stmt_ranges) throw new Exception("Prepare ranges failed: " . $conn->error);

    $sql_ends = "INSERT INTO ends (range_id, end_no) VALUES (?, ?)";
    $stmt_ends = $conn->prepare($sql_ends);
    if (!$stmt_ends) throw new Exception("Prepare ends failed: " . $conn->error);

    $sql_arrows = "INSERT INTO arrows (end_id, score) VALUES (?, ?)";
    $stmt_arrows = $conn->prepare($sql_arrows);
    if (!$stmt_arrows) throw new Exception("Prepare arrows failed: " . $conn->error);

    // BƯỚC C, D, E: LẶP VÀ LƯU CHI TIẾT
    foreach ($scores_array as $range_category_id => $ends) {
        
        // BƯỚC C: Insert `ranges`
        $stmt_ranges->bind_param("ii", $score_id, $range_category_id);
        $stmt_ranges->execute();
        $range_id = $conn->insert_id;
        
        foreach ($ends as $end_num => $arrow_string) {
            
            // BƯỚC D: Insert `ends`
            $stmt_ends->bind_param("ii", $range_id, $end_num);
            $stmt_ends->execute();
            $end_id = $conn->insert_id;

            // BƯỚC E: Insert `arrows`
            $arrows = explode(",", $arrow_string);
            
            // (Đã xác thực ở trên, giờ chỉ lưu)
            foreach ($arrows as $arrow_score) {
                $score_value = 0;
                $val = strtoupper(trim($arrow_score));
                if ($val == 'X' || $val == '10') { $score_value = 10; }
                elseif ($val == 'M') { $score_value = 0; }
                else { $score_value = intval($val); }

                $stmt_arrows->bind_param("ii", $end_id, $score_value);
                $stmt_arrows->execute();
            }
        }
    }
    
    $stmt_ranges->close();
    $stmt_ends->close();
    $stmt_arrows->close();

    // 4. COMMIT
    $conn->commit();
    
    // 5. CHUYỂN HƯỚNG
    header("Location: view_scores.php?new_score_id=" . $score_id . "&status=success");
    exit;

} catch (Exception $e) {
    // 6. ROLLBACK
    $conn->rollback();
    echo "<h1>Đã xảy ra lỗi!</h1>";
    echo "<p>Giao dịch đã bị hủy bỏ. Dữ liệu của bạn chưa được lưu.</p>";
    echo "<p>Chi tiết lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<a href='add_score_step1.php'>Quay lại Bước 1</a>";
}

$conn->close();
?>

