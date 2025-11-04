<?php
// insert_score.php
include 'connect.php';

// HÀM HIỂN THỊ LỖI CHI TIẾT
function displayError($message, $details = '', $preserved_data = []) {
    $details_html = $details ? "<br><small>$details</small>" : "";
    
    echo "
    <!DOCTYPE html>
    <html lang='vi'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Lỗi Nhập Điểm</title>
        <link rel='stylesheet' href='error_style.css'>
    </head>
    <body>
        <div class='error-container'>
            <div class='error-icon'>⚠️</div>
            <h1 class='error-title'>Có lỗi xảy ra</h1>
            <div class='error-message'>
                $message
                $details_html
            </div>
            
            <div class='btn-group'>
                <a href='add_score.php' class='btn btn-primary'>Quay lại nhập điểm</a>
                <a href='index.php' class='btn btn-secondary'>Về trang chủ</a>
            </div>
            
            <div class='tips'>
                <h4>💡 Mẹo nhập điểm:</h4>
                <ul>
                    <li>Chọn đầy đủ thông tin người bắn và vòng thi</li>
                    <li>Kiểm tra điểm từng mũi tên (0-10 điểm)</li>
                    <li>Đảm bảo thiết bị được chọn đúng</li>
                    <li>Nếu lỗi tiếp tục, hãy liên hệ quản trị viên</li>
                </ul>
            </div>
        </div>
    </body>
    </html>
    ";
    exit;
}

// HÀM HIỂN THỊ THÀNH CÔNG
function displaySuccess($score_id, $total_score, $date_recorded) {
    echo "
    <!DOCTYPE html>
    <html lang='vi'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Thành Công</title>
        <link rel='stylesheet' href='css/error_style.css'>
    </head>
    <body>
        <div class='success-container'>
            <div class='success-icon'>🎯</div>
            <h1 class='success-title'>Nhập điểm thành công!</h1>
            <div class='success-message'>
                Điểm số đã được lưu vào hệ thống.
            </div>
            
            <div class='score-info'>
                <strong>Mã điểm:</strong> #$score_id<br>
                <strong>Tổng điểm:</strong> $total_score<br>
                <strong>Ngày nhập:</strong> $date_recorded
            </div>
            
            <div class='btn-group'>
                <a href='view_scores.php?new_score_id=$score_id' class='btn btn-primary'>Xem chi tiết điểm</a>
                <a href='add_score.php' class='btn btn-primary'>Nhập điểm mới</a>
                <a href='index.php' class='btn btn-secondary'>Về trang chủ</a>
            </div>
        </div>
        
        <script>
            // Tự động chuyển hướng sau 5 giây
            setTimeout(function() {
                window.location.href = 'view_scores.php?new_score_id=$score_id';
            }, 5000);
        </script>
    </body>
    </html>
    ";
    exit;
}

// HÀM VALIDATE CHI TIẾT
function validateInput($user_id, $round_category_id, $equipment, $ends_data) {
    $errors = [];
    
    if ($user_id <= 0) {
        $errors[] = "Vui lòng chọn người bắn";
    }
    
    if ($round_category_id <= 0) {
        $errors[] = "Vui lòng chọn vòng thi";
    }
    
    $allowed_equipment = ['Recurve', 'Compound', 'Barebow', 'Traditional'];
    if (!in_array($equipment, $allowed_equipment)) {
        $errors[] = "Thiết bị không hợp lệ. Chọn: " . implode(', ', $allowed_equipment);
    }
    
    if (empty($ends_data)) {
        $errors[] = "Không có dữ liệu điểm nào được gửi";
    }
    
    foreach ($ends_data as $end_num => $arrows) {
        foreach ($arrows as $arrow_num => $score) {
            if ($score < 0 || $score > 10) {
                $errors[] = "End $end_num, mũi tên $arrow_num: Điểm phải từ 0-10 (hiện tại: $score)";
            }
        }
    }
    
    return $errors;
}

// MAIN EXECUTION
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    displayError(
        "Phương thức yêu cầu không hợp lệ", 
        "Trang này chỉ chấp nhận yêu cầu POST từ form nhập điểm."
    );
}

// 1. Lấy và validate dữ liệu cơ bản
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$round_category_id = isset($_POST['round_id']) ? intval($_POST['round_id']) : 0; 
$equipment = isset($_POST['equipment']) ? trim($_POST['equipment']) : '';
$date_recorded = isset($_POST['date_recorded']) && $_POST['date_recorded'] !== '' ? $_POST['date_recorded'] : date('Y-m-d');
$note = isset($_POST['note']) ? trim($_POST['note']) : '';

// 2. Thu thập điểm
$total_score = 0;
$ends_data = [];
$max_end = 0;

foreach ($_POST as $k => $v) {
    if (preg_match('/^end(\d+)_arrow(\d+)$/', $k, $m)) {
        $end_num = intval($m[1]);
        $arrow_num = intval($m[2]);
        $score = isset($_POST[$k]) ? intval($_POST[$k]) : 0;
        
        if ($end_num > $max_end) $max_end = $end_num;
        $ends_data[$end_num][$arrow_num] = $score;
        $total_score += $score;
    }
}

// 3. Validate toàn bộ dữ liệu
$validation_errors = validateInput($user_id, $round_category_id, $equipment, $ends_data);

if (!empty($validation_errors)) {
    $error_message = "Dữ liệu nhập không hợp lệ";
    $error_details = "• " . implode("<br>• ", $validation_errors);
    displayError($error_message, $error_details);
}

// 4. Xử lý database
$conn->begin_transaction();
try {
    // INSERT scores
    $sql_scores = "INSERT INTO scores (archer_id, round_id, equipment, total_score, date_recorded, note) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_scores = $conn->prepare($sql_scores);
    if (!$stmt_scores) throw new Exception("Lỗi chuẩn bị câu lệnh scores: " . $conn->error);
    
    $stmt_scores->bind_param("iisiss", $user_id, $round_category_id, $equipment, $total_score, $date_recorded, $note);
    if (!$stmt_scores->execute()) throw new Exception("Lỗi thực thi scores: " . $stmt_scores->error);
    
    $score_id = $conn->insert_id;
    $stmt_scores->close();

    // INSERT ends & arrows
    $sql_ends = "INSERT INTO ends (score_id, end_number) VALUES (?, ?)";
    $stmt_ends = $conn->prepare($sql_ends);
    if (!$stmt_ends) throw new Exception("Lỗi chuẩn bị câu lệnh ends: " . $conn->error);

    $sql_arrows = "INSERT INTO arrows (end_id, score) VALUES (?, ?)";
    $stmt_arrows = $conn->prepare($sql_arrows);
    if (!$stmt_arrows) throw new Exception("Lỗi chuẩn bị câu lệnh arrows: " . $conn->error);

    foreach ($ends_data as $end_num => $arrows) {
        $stmt_ends->bind_param("ii", $score_id, $end_num);
        if (!$stmt_ends->execute()) throw new Exception("Lỗi thực thi ends: " . $stmt_ends->error);
        
        $end_id = $conn->insert_id;
        foreach ($arrows as $arrow_score) {
            $stmt_arrows->bind_param("ii", $end_id, $arrow_score);
            if (!$stmt_arrows->execute()) throw new Exception("Lỗi thực thi arrows: " . $stmt_arrows->error);
        }
    }
    
    $stmt_ends->close();
    $stmt_arrows->close();

    // COMMIT và hiển thị thành công
    $conn->commit();
    displaySuccess($score_id, $total_score, $date_recorded);

} catch (Exception $e) {
    $conn->rollback();
    displayError(
        "Lỗi hệ thống khi lưu điểm", 
        "Chi tiết lỗi: " . htmlspecialchars($e->getMessage()) . 
        "<br>Vui lòng thử lại hoặc liên hệ quản trị viên."
    );
}

$conn->close();
?>