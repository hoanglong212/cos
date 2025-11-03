<?php
include 'connect.php';

// --- PHẦN 1: HÀM HỖ TRỢ ---

/**
 * Tính tổng điểm từ chuỗi mũi tên.
 * Xử lý đúng cho 'X' (10 điểm) và 'M' (0 điểm).
 */
function calculate_total_score(string $arrows_string): int {
    $total = 0;
    $arrow_parts = explode(',', $arrows_string);

    foreach ($arrow_parts as $arrow) {
        $arrow_clean = strtoupper(trim($arrow)); // Chuẩn hoá input

        if ($arrow_clean == 'X') {
            $total += 10;
        } elseif (is_numeric($arrow_clean)) {
            $score_val = intval($arrow_clean);
            // Đảm bảo điểm nằm trong khoảng hợp lệ (0-10)
            if ($score_val >= 0 && $score_val <= 10) {
                 $total += $score_val;
            }
        }
        // 'M' hoặc bất kỳ ký tự không hợp lệ nào khác sẽ được tính là 0
    }
    return $total;
}

// --- PHẦN 2: XỬ LÝ FORM ---

// 2.1. Kiểm tra dữ liệu đầu vào cơ bản
if (!isset($_POST['user_id']) || !isset($_POST['round_id']) || empty($_POST['arrows'])) {
    die("❌ Thiếu dữ liệu. Vui lòng quay lại và điền đầy đủ thông tin.");
}

// 2.2. Lấy và làm sạch dữ liệu
$user_id = intval($_POST['user_id']);
$round_id = intval($_POST['round_id']);
$arrows_string = $_POST['arrows'];
// Xử lý competition_id (cho phép NULL nếu không nhập)
$competition_id = !empty($_POST['competition_id']) ? trim($_POST['competition_id']) : null;
$archer_category_id = 1; // Tạm gán, bạn có thể lấy từ form hoặc logic khác

// 2.3. Tính toán điểm số
$total_score = calculate_total_score($arrows_string);


// --- PHẦN 3: DATABASE TRANSACTION ---

// Bắt đầu một transaction
$conn->begin_transaction();

try {
    // BƯỚC 1: Insert vào bảng `scores` (bảng metadata)
    $sql_scores = "INSERT INTO scores (user_id, round_id, competition_id, archer_category_id)
                   VALUES (?, ?, ?, ?)";
    
    $stmt_scores = $conn->prepare($sql_scores);
    if (!$stmt_scores) {
        throw new Exception("Lỗi chuẩn bị SQL (scores): " . $conn->error);
    }
    // 'iisi' = integer, integer, string, integer
    $stmt_scores->bind_param("iisi", $user_id, $round_id, $competition_id, $archer_category_id);
    
    if (!$stmt_scores->execute()) {
        throw new Exception("Lỗi thực thi (scores): " . $stmt_scores->error);
    }

    // LẤY ID CỦA LƯỢT BẮN VỪA TẠO
    // Đây là bước then chốt để liên kết 2 bảng
    $new_score_id = $conn->insert_id;
    
    $stmt_scores->close();

    // BƯỚC 2: Insert vào bảng `ends` (bảng chi tiết điểm)
    // Giả sử bảng 'ends' của bạn có cột: score_id, end_arrows (chuỗi '10,X,9'), end_total (tổng điểm)
    $sql_ends = "INSERT INTO ends (score_id, end_arrows, end_total) VALUES (?, ?, ?)";
    
    $stmt_ends = $conn->prepare($sql_ends);
    if (!$stmt_ends) {
        throw new Exception("Lỗi chuẩn bị SQL (ends): " . $conn->error);
    }
    // 'isi' = integer, string, integer
    $stmt_ends->bind_param("isi", $new_score_id, $arrows_string, $total_score);

    if (!$stmt_ends->execute()) {
        throw new Exception("Lỗi thực thi (ends): " . $stmt_ends->error);
    }
    $stmt_ends->close();

    // Nếu cả 2 bước trên thành công, commit transaction
    $conn->commit();

    // Hiển thị thông báo thành công
    echo "<h1>✅ Lưu điểm thành công!</h1>";
    echo "<p>Cung thủ ID: $user_id</p>";
    echo "<p>Điểm: $arrows_string</p>";
    echo "<p>Tổng điểm: <strong>$total_score</strong></p>";
    echo "<hr>";
    echo "<a href='add_score.php' class='btn'>Nhập điểm tiếp</a>";
    echo "<a href='view_scores.php?score_id=$new_score_id' class='btn'>Xem chi tiết lượt bắn này</a>";
    echo "<a href='index.php' class='btn'>⬅ Về trang chính</a>";

} catch (Exception $e) {
    // Nếu có bất kỳ lỗi nào xảy ra, rollback (hoàn tác) tất cả các thay đổi
    $conn->rollback();
    
    echo "<h1>❌ Đã xảy ra lỗi nghiêm trọng!</h1>";
    echo "<p>Toàn bộ thao tác đã được hoàn tác để đảm bảo an toàn dữ liệu.</p>";
    echo "<p>Chi tiết lỗi: " . $e->getMessage() . "</p>";
    echo "<a href='add_score.php' class='btn'>Thử lại</a>";

} finally {
    // Luôn đóng kết nối dù thành công hay thất bại
    $conn->close();
}
?>