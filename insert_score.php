<?php
include 'connect.php';  

// kiểm tra có nhận dữ liệu từ form không
if (!isset($_POST['user_id']) || !isset($_POST['round_id'])) {
    die("❌ Thiếu dữ liệu. Vui lòng quay lại và điền form ở add_score.php");
}

$user_id = intval($_POST['user_id']);
$round_id = intval($_POST['round_id']);
$competition_id = $_POST['competition_id'] ?? null;
$arrows = $_POST['arrows'] ?? '';

$arrow_values = array_map('intval', explode(",", $arrows));
$total_score = array_sum($arrow_values);

// ⚠️ Vì bảng scores không có cột total_score,
// ta tạm thời lưu tổng điểm vào bảng 'ends' hoặc hiển thị ra thôi.
// Ở đây demo: chỉ lưu user_id, round_id, competition_id, archer_category_id = 1

$sql = "INSERT INTO scores (user_id, round_id, competition_id, archer_category_id)
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("❌ Lỗi : " . $conn->error);
}

$archer_category_id = 1; // tạm thời gán 1, bạn có thể sửa theo thực tế
$stmt->bind_param("iisi", $user_id, $round_id, $competition_id, $archer_category_id);

if ($stmt->execute()) {
    echo "<p>✅ Lưu điểm thành công!</p>";
    echo "<p>Tổng điểm (chưa lưu): $total_score</p>";
    echo "<a href='view_scores.php?user_id=$user_id'>Xem điểm</a><br>";
    echo "<a href='index.php'>⬅ Về trang chính</a>";
} else {
    echo "❌ Lỗi khi lưu: " . $stmt->error;
}
$archer_category_id = null;
$stmt->bind_param("iisi", $user_id, $round_id, $competition_id, $archer_category_id);

$stmt->close();
$conn->close();
?>
