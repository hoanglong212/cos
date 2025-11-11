<?php
// insert_score.php
include 'connect.php';

// BẬT HIỂN THỊ LỖI ĐỂ DEBUG
error_reporting(E_ALL);
ini_set('display_errors', 1);

// HÀM HIỂN THỊ LỖI CHI TIẾT
function displayError($message, $details = '') {
    // Xử lý chi tiết lỗi để hiển thị đẹp hơn
    $details_html = "";
    if ($details) {
        $error_lines = explode("\n", $details);
        $formatted_errors = [];
        
        foreach ($error_lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                // Xử lý dòng lỗi để hiển thị đẹp hơn
                if (strpos($line, "•") === 0) {
                    $formatted_errors[] = $line;
                } else {
                    $formatted_errors[] = "• " . $line;
                }
            }
        }
        
        if (!empty($formatted_errors)) {
            $details_html = "
            <div class='error-details'>
                <div class='error-summary'>Chi tiết lỗi:</div>
                <ul class='error-list'>
            ";
            
            // Giới hạn hiển thị 10 lỗi đầu tiên để tránh quá dài
            $display_errors = array_slice($formatted_errors, 0, 10);
            foreach ($display_errors as $error) {
                $clean_error = str_replace("•", "", $error); // Loại bỏ ký tự • gốc
                $details_html .= "<li class='error-item'>• " . htmlspecialchars(trim($clean_error)) . "</li>";
            }
            
            // Thông báo nếu có nhiều lỗi hơn
            if (count($formatted_errors) > 10) {
                $remaining = count($formatted_errors) - 10;
                $details_html .= "<li class='error-item-more'>... và $remaining lỗi khác</li>";
            }
            
            $details_html .= "
                </ul>
            </div>
            ";
        }
    }
    
    echo "
    <!DOCTYPE html>
    <html lang='vi'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Lỗi Nhập Điểm</title>
        <link rel='stylesheet' href='main.css'>
        <style>
            /* Additional styles for error page */
            .error-page-body { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0; 
                padding: 20px; 
                min-height: 100vh; 
                display: flex; 
                align-items: center; 
                justify-content: center;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .error-container {
                max-width: 800px;
                margin: 2rem auto;
                padding: 2rem;
                background: white;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
                border-left: 6px solid #e74c3c;
            }
            .error-header {
                display: flex;
                align-items: center;
                margin-bottom: 1.5rem;
                padding-bottom: 1rem;
                border-bottom: 2px solid #f8f9fa;
            }
            .error-icon {
                font-size: 2.5rem;
                color: #e74c3c;
                margin-right: 1rem;
            }
            .error-title {
                color: #e74c3c;
                font-size: 1.8rem;
                font-weight: 600;
                margin: 0;
            }
            .error-message {
                color: #495057;
                margin-bottom: 1.5rem;
                font-size: 1.1rem;
                line-height: 1.6;
            }
            .error-details {
                background: #f8f9fa;
                padding: 1.5rem;
                border-radius: 8px;
                margin-bottom: 1.5rem;
                border: 1px solid #e9ecef;
            }
            .error-summary {
                font-weight: 600;
                color: #495057;
                margin-bottom: 1rem;
                font-size: 1.1rem;
            }
            .error-list {
                list-style: none;
                padding: 0;
                margin: 0;
                max-height: 200px;
                overflow-y: auto;
            }
            .error-item {
                padding: 0.5rem 0;
                border-bottom: 1px solid #e9ecef;
                color: #6c757d;
                font-size: 0.95rem;
            }
            .error-item:last-child {
                border-bottom: none;
            }
            .error-item-more {
                padding: 0.5rem 0;
                color: #3498db;
                font-style: italic;
                text-align: center;
            }
            .error-tips {
                background: #e8f4fd;
                padding: 1.5rem;
                border-radius: 8px;
                margin-bottom: 2rem;
                border-left: 4px solid #3498db;
            }
            .tips-title {
                color: #2c3e50;
                font-weight: 600;
                margin-bottom: 1rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .tips-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            .tip-item {
                padding: 0.5rem 0;
                padding-left: 1.5rem;
                position: relative;
                color: #495057;
            }
            .tip-item:before {
                content: \"💡\";
                position: absolute;
                left: 0;
            }
            .error-actions {
                display: flex;
                gap: 1rem;
                justify-content: flex-end;
                flex-wrap: wrap;
            }
            .btn {
                padding: 0.75rem 1.5rem;
                border: none;
                border-radius: 6px;
                font-weight: 600;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                text-align: center;
                transition: all 0.3s ease;
                font-size: 0.95rem;
            }
            .btn-primary {
                background: #3498db;
                color: white;
            }
            .btn-primary:hover {
                background: #2980b9;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
            }
            .btn-secondary {
                background: #95a5a6;
                color: white;
            }
            .btn-secondary:hover {
                background: #7f8c8d;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(149, 165, 166, 0.3);
            }
            
            /* Scrollbar styling */
            .error-list::-webkit-scrollbar {
                width: 6px;
            }
            .error-list::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 3px;
            }
            .error-list::-webkit-scrollbar-thumb {
                background: #c1c1c1;
                border-radius: 3px;
            }
            .error-list::-webkit-scrollbar-thumb:hover {
                background: #a8a8a8;
            }
            
            /* Responsive */
            @media (max-width: 768px) {
                .error-page-body {
                    padding: 10px;
                }
                .error-container {
                    margin: 1rem;
                    padding: 1.5rem;
                }
                .error-header {
                    flex-direction: column;
                    text-align: center;
                    gap: 0.5rem;
                }
                .error-icon {
                    margin-right: 0;
                    margin-bottom: 0.5rem;
                }
                .error-actions {
                    flex-direction: column;
                }
                .btn {
                    width: 100%;
                }
            }
            
            /* Animation */
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            .error-container {
                animation: slideIn 0.3s ease-out;
            }
        </style>
    </head>
    <body class='error-page-body'>
        <div class='error-container'>
            <div class='error-header'>
                <div class='error-icon'>⚠️</div>
                <h1 class='error-title'>Có lỗi xảy ra</h1>
            </div>
            
            <div class='error-message'>
                " . htmlspecialchars($message) . "
            </div>
            
            " . $details_html . "
            
            <div class='error-tips'>
                <div class='tips-title'>
                    <span>💡</span>
                    Mẹo nhập điểm:
                </div>
                <ul class='tips-list'>
                    <li class='tip-item'>Chọn đầy đủ thông tin người bắn và vòng thi</li>
                    <li class='tip-item'>Kiểm tra điểm từng mũi tên (0-10, X, M)</li>
                    <li class='tip-item'>Đảm bảo phân loại cung thủ và dụng cụ được chọn đúng</li>
                    <li class='tip-item'>Nếu lỗi tiếp tục, hãy liên hệ quản trị viên</li>
                </ul>
            </div>

            <div class='error-actions'>
                <a href='javascript:history.back()' class='btn btn-primary'>Quay lại nhập điểm</a>
                <a href='homepage.html' class='btn btn-secondary'>Về trang chủ</a>
            </div>
        </div>
    </body>
    </html>
    ";
    exit;
}

// HÀM HIỂN THỊ THÀNH CÔNG (giữ nguyên)
function displaySuccess($score_id, $total_score, $date_recorded, $context) {
    $loai_diem = ($context === 'competition') ? 'Thi đấu' : 'Luyện tập';
    
    echo "
    <!DOCTYPE html>
    <html lang='vi'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Thành Công</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
                margin: 0; padding: 20px; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            }
            .success-container { 
                background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); 
                max-width: 500px; text-align: center;
            }
            .success-icon { font-size: 4rem; margin-bottom: 20px; }
            .success-title { color: #27ae60; margin-bottom: 20px; }
            .success-message { color: #555; margin-bottom: 20px; }
            .score-info { 
                background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;
                text-align: left; border-left: 4px solid #27ae60;
            }
            .btn-group { margin: 25px 0; }
            .btn { 
                display: inline-block; padding: 12px 25px; margin: 5px; text-decoration: none; 
                border-radius: 8px; font-weight: bold; transition: all 0.3s; color: white;
            }
            .btn-primary { background: #3498db; }
            .btn-primary:hover { background: #2980b9; transform: translateY(-2px); }
            .btn-secondary { background: #95a5a6; }
            .btn-secondary:hover { background: #7f8c8d; }
        </style>
    </head>
    <body>
        <div class='success-container'>
            <div class='success-icon'>🎯</div>
            <h1 class='success-title'>Nhập điểm thành công!</h1>
            <div class='success-message'>
                Điểm số đã được lưu vào hệ thống.
            </div>
            
            <div class='score-info'>
                <strong>Mã lượt bắn:</strong> #$score_id<br>
                <strong>Tổng điểm:</strong> $total_score<br>
                <strong>Ngày nhập:</strong> $date_recorded<br>
                <strong>Loại điểm:</strong> $loai_diem
            </div>
            
            <div class='btn-group'>
                <a href='view_scores.php' class='btn btn-primary'>Xem tất cả điểm</a>
                <a href='add_score_step1.php' class='btn btn-primary'>Nhập điểm mới</a>
                <a href='homepage.html' class='btn btn-secondary'>Về trang chủ</a>
            </div>
        </div>
    </body>
    </html>
    ";
    exit;
}

// HÀM KIỂM TRA DỮ LIỆU ĐIỂM CHI TIẾT (giữ nguyên)
function validateScoreData($score_data) {
    $errors = [];
    
    if (empty($score_data)) {
        $errors[] = "Không có dữ liệu điểm nào được gửi";
        return $errors;
    }
    
    $total_valid_arrows = 0;
    
    foreach ($score_data as $range_id => $ends) {
        foreach ($ends as $end_num => $score_string) {
            $score_string = trim($score_string);
            
            // Kiểm tra nếu end này hoàn toàn trống
            if (empty($score_string)) {
                $errors[] = "End $end_num (Range $range_id) bị trống";
                continue;
            }
            
            $arrows = explode(',', $score_string);
            $arrow_count = 0;
            
            foreach ($arrows as $arrow_index => $arrow) {
                $arrow = strtoupper(trim($arrow));
                
                // Kiểm tra định dạng điểm
                if (!in_array($arrow, ['M', 'X', '10', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'])) {
                    $errors[] = "End $end_num, mũi tên " . ($arrow_index + 1) . ": '$arrow' không hợp lệ (chỉ chấp nhận 0-9, X, M)";
                    continue;
                }
                
                $arrow_count++;
                $total_valid_arrows++;
            }
            
            // Kiểm tra số lượng mũi tên trong end
            if ($arrow_count === 0) {
                $errors[] = "End $end_num (Range $range_id) không có mũi tên hợp lệ nào";
            }
        }
    }
    
    // Kiểm tra tổng số mũi tên
    if ($total_valid_arrows === 0) {
        $errors[] = "Không có mũi tên hợp lệ nào trong tất cả các end";
    }
    
    return $errors;
}

// PHẦN CÒN LẠI CỦA MÃ NGUỒN GIỮ NGUYÊN
// ... (phần main execution và các hàm khác giữ nguyên)

// MAIN EXECUTION
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Phương thức yêu cầu không hợp lệ.");
    }

    // DEBUG: Hiển thị toàn bộ POST data
    error_log("=== FULL POST DATA ===");
    foreach ($_POST as $key => $value) {
        error_log("$key: " . (is_array($value) ? print_r($value, true) : $value));
    }

    // 1. Lấy dữ liệu từ form
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $round_category_id = isset($_POST['round_category_id']) ? intval($_POST['round_category_id']) : 0;
    $bow_category_id = isset($_POST['bow_category_id']) ? intval($_POST['bow_category_id']) : 0;
    $archer_category_id = isset($_POST['archer_category_id']) ? intval($_POST['archer_category_id']) : 0;
    $date_recorded = isset($_POST['date_recorded']) && $_POST['date_recorded'] !== '' ? $_POST['date_recorded'] : date('Y-m-d');
    $competition_id_input = isset($_POST['competition_id']) ? trim($_POST['competition_id']) : '';
    $note = isset($_POST['note']) ? trim($_POST['note']) : '';
    $context = isset($_POST['context']) ? trim($_POST['context']) : 'practice';

    // 2. KIỂM TRA NGHIÊM NGẶT DỮ LIỆU CƠ BẢN
    $basic_errors = [];
    
    if ($user_id <= 0) {
        $basic_errors[] = "Vui lòng chọn người bắn";
    }
    
    if ($round_category_id <= 0) {
        $basic_errors[] = "Vui lòng chọn round";
    }

    if ($bow_category_id <= 0) {
        $basic_errors[] = "Vui lòng chọn dụng cụ";
    }

    if ($archer_category_id <= 0) {
        $basic_errors[] = "Vui lòng chọn phân loại cung thủ";
    }

    if (!empty($basic_errors)) {
        throw new Exception("Dữ liệu cơ bản không hợp lệ:\n• " . implode("\n• ", $basic_errors));
    }

    // 3. XỬ LÝ PHÂN BIỆT THI ĐẤU/LUYỆN TẬP
    if ($context === 'competition' && !empty($competition_id_input)) {
        $competition_id = $competition_id_input;
    } else {
        $competition_id = NULL;
    }

    // 4. THU THẬP VÀ VALIDATE DỮ LIỆU ĐIỂM
    $score_data = [];
    $total_score = 0;
    $tens_count = 0;
    $total_arrows = 0;

    // Chỉ xử lý cấu trúc scores[range][end]
    if (isset($_POST['scores']) && is_array($_POST['scores']) && !empty($_POST['scores'])) {
        $score_data = $_POST['scores'];
        error_log("Score data found: " . print_r($score_data, true));
        
        // VALIDATE CHI TIẾT DỮ LIỆU ĐIỂM
        $score_errors = validateScoreData($score_data);
        if (!empty($score_errors)) {
            throw new Exception("Dữ liệu điểm không hợp lệ:\n• " . implode("\n• ", $score_errors));
        }
        
        // TÍNH ĐIỂM SAU KHI ĐÃ VALIDATE
        foreach ($score_data as $range_category_id => $ends) {
            foreach ($ends as $end_num => $score_string) {
                $score_string = trim($score_string);
                if (!empty($score_string)) {
                    $arrows = explode(',', $score_string);
                    
                    foreach ($arrows as $arrow) {
                        $arrow = strtoupper(trim($arrow));
                        $score = 0;
                        
                        if ($arrow === 'X' || $arrow === '10') {
                            $score = 10;
                            $tens_count++;
                        } elseif ($arrow === 'M') {
                            $score = 0;
                        } elseif (is_numeric($arrow) && $arrow >= 0 && $arrow <= 9) {
                            $score = (int)$arrow;
                        }
                        
                        $total_score += $score;
                        $total_arrows++;
                    }
                }
            }
        }
    } else {
        throw new Exception("Không tìm thấy dữ liệu điểm. Có thể form chưa được gửi đúng cách.");
    }

    // KIỂM TRA LẦN CUỐI TRƯỚC KHI LƯU
    if ($total_arrows === 0) {
        throw new Exception("Không có mũi tên hợp lệ nào để lưu. Vui lòng kiểm tra lại dữ liệu điểm.");
    }

    if ($total_score === 0) {
        throw new Exception("Tổng điểm bằng 0. Nếu đúng là 0 điểm, vui lòng xác nhận lại.");
    }

    error_log("VALIDATION PASSED - Total arrows: $total_arrows, Total score: $total_score, Tens count: $tens_count");

    // 5. Xác định round_id
    $round_id = $round_category_id;

    // 6. BẮT ĐẦU TRANSACTION
    $conn->begin_transaction();

    try {
        // KIỂM TRA LẠI TRƯỚC KHI INSERT
        if ($user_id <= 0 || $round_id <= 0 || $archer_category_id <= 0) {
            throw new Exception("Dữ liệu không hợp lệ khi chuẩn bị lưu");
        }

        // BƯỚC 1: Tạo bản ghi trong bảng scores
        $sql_score = "INSERT INTO scores (user_id, round_id, competition_id, archer_category_id, total_score, is_approved) 
                      VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_score = $conn->prepare($sql_score);
        
        if (!$stmt_score) {
            throw new Exception("Lỗi chuẩn bị câu lệnh scores: " . $conn->error);
        }
        
        $is_approved = 0; // Mặc định chờ duyệt
        
        $stmt_score->bind_param("iisiii", 
            $user_id, 
            $round_id, 
            $competition_id,
            $archer_category_id, 
            $total_score, 
            $is_approved
        );
        
        if (!$stmt_score->execute()) {
            throw new Exception("Lỗi thực thi scores: " . $stmt_score->error);
        }
        
        $score_id = $conn->insert_id;
        $stmt_score->close();

        // BƯỚC 2: Lưu thông tin bổ sung nếu bảng score_metadata tồn tại
        $check_table_sql = "SHOW TABLES LIKE 'score_metadata'";
        $result = $conn->query($check_table_sql);
        if ($result->num_rows > 0) {
            $sql_metadata = "INSERT INTO score_metadata (score_id, date_recorded, bow_category_id, tens_count, total_arrows, note, context) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_metadata = $conn->prepare($sql_metadata);
            
            if ($stmt_metadata) {
                $stmt_metadata->bind_param("isiiiss", 
                    $score_id, 
                    $date_recorded, 
                    $bow_category_id, 
                    $tens_count, 
                    $total_arrows, 
                    $note, 
                    $context
                );
                if (!$stmt_metadata->execute()) {
                    throw new Exception("Lỗi thực thi metadata: " . $stmt_metadata->error);
                }
                $stmt_metadata->close();
            }
        }

        // COMMIT transaction
        $conn->commit();
        
        // HIỂN THỊ THÀNH CÔNG
        displaySuccess($score_id, $total_score, $date_recorded, $context);

    } catch (Exception $e) {
        // ROLLBACK nếu có lỗi
        $conn->rollback();
        throw new Exception("Lỗi khi lưu vào database: " . $e->getMessage());
    }

} catch (Exception $e) {
    displayError("Không thể lưu điểm", $e->getMessage());
}

if (isset($conn)) {
    $conn->close();
}