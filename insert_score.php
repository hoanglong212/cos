<?php
// --- PHẦN 1: KẾT NỐI VÀ HÀM HỖ TRỢ ---
include 'connect.php';

/**
 * Hàm hỗ trợ tính toán điểm
 * Xử lý 'X' (10 điểm) và 'M' (0 điểm).
 */
function get_arrow_value(string $arrow_str): int {
    $arrow_clean = strtoupper(trim($arrow_str));
    if ($arrow_clean == 'X') {
        return 10;
    }
    if (is_numeric($arrow_clean)) {
        $val = intval($arrow_clean);
        return ($val >= 0 && $val <= 10) ? $val : 0;
    }
    return 0; // 'M' hoặc ký tự không hợp lệ khác là 0
}

/**
 * Hàm hiển thị thông báo lỗi/thành công (để dùng ở cuối)
 */
function show_message($title, $message, $is_error = false) {
    $color = $is_error ? '#721c24' : '#155724';
    $bgcolor = $is_error ? '#f8d7da' : '#d4edda';
    $bordercolor = $is_error ? '#f5c6cb' : '#c3e6cb';

    echo "<!DOCTYPE html><html lang='vi'><head><meta charset='UTF-8'><title>$title</title><link rel='stylesheet' href='style.css'>";
    echo "<style>
            .message-box { 
                width: 85%; max-width: 600px; margin: 20px auto; 
                background: $bgcolor; color: $color; border: 1px solid $bordercolor; 
                border-radius: 10px; padding: 30px 20px; text-align: left; 
            }
            .message-box h1 { color: $color; text-align: center; }
            .message-box p { font-size: 1.1em; }
            .message-box .actions { text-align: center; margin-top: 20px; }
          </style>";
    echo "</head><body>";
    echo "<div class='message-box'>";
    echo "<h1>$title</h1>";
    echo "<p>$message</p>";
    echo "<div class='actions'>";
    echo "<a href='add_score.php' class='btn'>Nhập điểm tiếp</a>";
    echo "<a href='index.php' class='btn'>Về trang chính</a>";
    echo "</div>";
    echo "</div></body></html>";
}

// --- PHẦN 2: KIỂM TRA DỮ LIỆU ĐẦU VÀO ---

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    show_message("Lỗi Truy Cập", "Phương thức truy cập không hợp lệ.", true);
    exit;
}

// Kiểm tra các trường cơ bản
if (empty($_POST['user_id']) || empty($_POST['archer_category_id']) || empty($_POST['round_id']) || empty($_POST['ranges'])) {
    show_message("❌ Lỗi Thiếu Dữ Liệu", "Thiếu thông tin cơ bản (User, Hạng mục, Round) hoặc không có dữ liệu điểm nào được gửi.", true);
    exit;
}

// Lấy dữ liệu metadata
$user_id = intval($_POST['user_id']);
$round_id = intval($_POST['round_id']);
$archer_category_id = intval($_POST['archer_category_id']);
$competition_id = !empty($_POST['competition_id']) ? trim($_POST['competition_id']) : null;
$posted_ranges = $_POST['ranges']; // Đây là mảng đa chiều

// --- PHẦN 3: DATABASE TRANSACTION ---

// Bắt đầu một transaction
$conn->begin_transaction();

try {
    // BƯỚC 1: Insert vào bảng `scores` (bảng cha)
    // CSDL của bạn không có cột 'status' hay 'date_shot' trong scores,
    // nếu có, bạn nên thêm vào đây.
    $sql_scores = "INSERT INTO scores (user_id, round_id, competition_id, archer_category_id)
                   VALUES (?, ?, ?, ?)";
    
    $stmt_scores = $conn->prepare($sql_scores);
    if (!$stmt_scores) throw new Exception("Lỗi chuẩn bị SQL (scores): " . $conn->error);
    
    // 'iisi' = integer, integer, string, integer
    $stmt_scores->bind_param("iisi", $user_id, $round_id, $competition_id, $archer_category_id);
    if (!$stmt_scores->execute()) throw new Exception("Lỗi thực thi (scores): " . $stmt_scores->error);

    // LẤY ID CỦA LƯỢT BẮN VỪA TẠO
    $new_score_id = $conn->insert_id;
    $stmt_scores->close();

    // Chuẩn bị các câu lệnh SQL cho các vòng lặp
    // (Hiệu quả hơn là prepare lặp đi lặp lại)
    $sql_ranges = "INSERT INTO ranges (score_id, range_category_id) VALUES (?, ?)";
    $stmt_ranges = $conn->prepare($sql_ranges);
    if (!$stmt_ranges) throw new Exception("Lỗi chuẩn bị SQL (ranges): " . $conn->error);

    $sql_ends = "INSERT INTO ends (range_id, end_no) VALUES (?, ?)";
    $stmt_ends = $conn->prepare($sql_ends);
    if (!$stmt_ends) throw new Exception("Lỗi chuẩn bị SQL (ends): " . $conn->error);

    $sql_arrows = "INSERT INTO arrows (end_id, score, ring_hit) VALUES (?, ?, ?)";
    $stmt_arrows = $conn->prepare($sql_arrows);
    if (!$stmt_arrows) throw new Exception("Lỗi chuẩn bị SQL (arrows): " . $conn->error);

    $total_score_all = 0; // Biến đếm tổng điểm

    // BƯỚC 2: Lặp qua từng RANGE (cự ly) được gửi lên
    foreach ($posted_ranges as $range_category_id => $range_data) {
        $r_cat_id = intval($range_category_id);

        // 2a. Insert vào bảng `ranges`
        $stmt_ranges->bind_param("ii", $new_score_id, $r_cat_id);
        if (!$stmt_ranges->execute()) throw new Exception("Lỗi thực thi (ranges): " . $stmt_ranges->error);
        
        $new_range_id = $conn->insert_id; // Lấy ID của range vừa tạo

        // BƯỚC 3: Lặp qua từng END (lượt) trong range
        if (empty($range_data['ends'])) continue; // Bỏ qua nếu range không có end
        
        foreach ($range_data['ends'] as $end_no => $end_data) {
            $e_no = intval($end_no);

            // 3a. Insert vào bảng `ends`
            $stmt_ends->bind_param("ii", $new_range_id, $e_no);
            if (!$stmt_ends->execute()) throw new Exception("Lỗi thực thi (ends): " . $stmt_ends->error);
            
            $new_end_id = $conn->insert_id; // Lấy ID của end vừa tạo

            // BƯỚC 4: Lặp qua từng ARROW (mũi tên) trong end
            if (empty($end_data['arrows'])) continue; // Bỏ qua nếu end không có arrow
            
            foreach ($end_data['arrows'] as $arrow_no => $arrow_score_str) {
                $arrow_value = get_arrow_value($arrow_score_str); // Tính điểm (vd: 'X' -> 10)
                $arrow_hit = trim(strtoupper($arrow_score_str)); // Lưu chữ ('X', 'M', '9')
                
                $total_score_all += $arrow_value; // Cộng dồn vào tổng điểm

                // 4a. Insert vào bảng `arrows`
                // 'iis' = integer (end_id), integer (score), string (ring_hit)
                $stmt_arrows->bind_param("iis", $new_end_id, $arrow_value, $arrow_hit);
                if (!$stmt_arrows->execute()) throw new Exception("Lỗi thực thi (arrows): " . $stmt_arrows->error);
            }
        }
    }

    // Đóng các
    $stmt_ranges->close();
    $stmt_ends->close();
    $stmt_arrows->close();

    // BƯỚC 5: Mọi thứ thành công! Commit transaction
    $conn->commit();

    // Hiển thị thông báo thành công
    show_message(
        "✅ Lưu điểm thành công!",
        "Đã lưu trữ thành công lượt bắn (ID: $new_score_id) với tổng điểm là: <strong>$total_score_all</strong>"
    );

} catch (Exception $e) {
    // BƯỚC 6: Có lỗi xảy ra! Rollback (hoàn tác) tất cả
    $conn->rollback();
    
    show_message(
        "❌ Đã xảy ra lỗi nghiêm trọng!",
        "Toàn bộ thao tác đã được hoàn tác để đảm bảo an toàn dữ liệu.<br><br>
         <strong>Chi tiết lỗi:</strong> " . $e->getMessage(),
        true // Đánh dấu đây là lỗi
    );

} finally {
    // Luôn đóng kết nối dù thành công hay thất bại
    if (isset($stmt_scores)) $stmt_scores->close();
    if (isset($stmt_ranges)) $stmt_ranges->close();
    if (isset($stmt_ends)) $stmt_ends->close();
    if (isset($stmt_arrows)) $stmt_arrows->close();
    $conn->close();
}
?>