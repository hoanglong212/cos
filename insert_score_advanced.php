<?php
// insert_score.php
include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo "Invalid request";
    exit;
}

// 1. Lấy dữ liệu cơ bản
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
// (Lưu ý: 'round_id' từ form giờ là 'round_category_id')
$round_category_id = isset($_POST['round_id']) ? intval($_POST['round_id']) : 0; 
$equipment = isset($_POST['equipment']) ? trim($_POST['equipment']) : '';
$date_recorded = isset($_POST['date_recorded']) && $_POST['date_recorded'] !== '' ? $_POST['date_recorded'] : date('Y-m-d');
$note = isset($_POST['note']) ? trim($_POST['note']) : '';

// 2. Thu thập điểm và tính tổng
$total_score = 0;
$ends_data = []; // Mảng để giữ dữ liệu chi tiết

// Tìm tổng số End (dựa trên tên input lớn nhất)
$max_end = 0;
foreach ($_POST as $k => $v) {
    if (preg_match('/^end(\d+)_arrow(\d+)$/', $k, $m)) {
        if (intval($m[1]) > $max_end) {
            $max_end = intval($m[1]);
        }
    }
}

if ($user_id <= 0 || $round_category_id <= 0 || $max_end == 0) {
    echo "<div class='msg error'>Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.</div>";
    exit;
}

// Tính tổng điểm và xây dựng mảng chi tiết
for ($e = 1; $e <= $max_end; $e++) {
    $ends_data[$e] = [];
    for ($a = 1; $a <= 6; $a++) {
        $key = "end{$e}_arrow{$a}";
        $score = isset($_POST[$key]) ? intval($_POST[$key]) : 0;
        $ends_data[$e][$a] = $score; // Lưu chi tiết mũi tên
        $total_score += $score; // Cộng vào tổng điểm
    }
}

// 3. Bắt đầu Transaction
$conn->begin_transaction();
try {
    // BƯỚC A: INSERT VÀO BẢNG `scores`
    // (Lưu ý: Tôi đổi 'round_id' thành 'round_category_id' cho khớp logic)
    // Bạn cần đảm bảo cột trong CSDL của bạn tên là gì, ở đây tôi giả sử là 'round_category_id'
    $sql_scores = "INSERT INTO scores (archer_id, round_category_id, equipment, total_score, date_recorded, note) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    
    // Dựa trên ERD của bạn, bảng 'scores' có 'archer_id' và 'round_id'
    // Chúng ta sẽ giả sử 'round_id' trong 'scores' thực sự là 'round_category_id'
    $sql_scores = "INSERT INTO scores (archer_id, round_id, equipment, total_score, date_recorded, note) 
                   VALUES (?, ?, ?, ?, ?, ?)";
                   
    $stmt_scores = $conn->prepare($sql_scores);
    if (!$stmt_scores) throw new Exception("Prepare scores failed: " . $conn->error);
    
    // (i=user_id, i=round_category_id, s=equipment, i=total_score, s=date_recorded, s=note)
    $stmt_scores->bind_param("iisiss", $user_id, $round_category_id, $equipment, $total_score, $date_recorded, $note);
    $stmt_scores->execute();
    
    // Lấy score_id vừa tạo
    $score_id = $conn->insert_id;
    $stmt_scores->close();

    // BƯỚC B: INSERT VÀO BẢNG `ends`
    $sql_ends = "INSERT INTO ends (score_id, end_number) VALUES (?, ?)";
    $stmt_ends = $conn->prepare($sql_ends);
    if (!$stmt_ends) throw new Exception("Prepare ends failed: " . $conn->error);

    // BƯỚC C: INSERT VÀO BẢNG `arrows`
    $sql_arrows = "INSERT INTO arrows (end_id, score) VALUES (?, ?)";
    $stmt_arrows = $conn->prepare($sql_arrows);
    if (!$stmt_arrows) throw new Exception("Prepare arrows failed: " . $conn->error);

    // Lặp qua dữ liệu đã thu thập
    foreach ($ends_data as $end_num => $arrows) {
        // Chạy BƯỚC B
        $stmt_ends->bind_param("ii", $score_id, $end_num);
        $stmt_ends->execute();
        
        // Lấy end_id vừa tạo
        $end_id = $conn->insert_id;
        
        // Lặp qua các mũi tên
        foreach ($arrows as $arrow_num => $arrow_score) {
            // Chạy BƯỚC C
            $stmt_arrows->bind_param("ii", $end_id, $arrow_score);
            $stmt_arrows->execute();
        }
    }
    
    $stmt_ends->close();
    $stmt_arrows->close();

    // 4. Commit
    $conn->commit();
    
    // 5. Chuyển hướng về trang xem điểm (Yêu cầu 8)
    header("Location: view_scores.php?new_score_id=" . $score_id);
    exit;

} catch (Exception $e) {
    $conn->rollback();
    echo "<div class='msg error'>Lỗi hệ thống: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<a href='add_score.php'>Quay lại</a>";
    exit;
}

$conn->close();
?>