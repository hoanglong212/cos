<?php
// Bật hiển thị lỗi để gỡ lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'connect.php';

// 1. Lấy round_id từ request
if (!isset($_GET['round_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Thiếu round_id']);
    exit;
}
$round_id = intval($_GET['round_id']);

// 2. Chuẩn bị câu truy vấn an toàn (Prepared Statement)
// Câu lệnh này lấy tất cả các cự ly (ranges) thuộc về một round,
$sql = "SELECT
            rc.range_category_id,
            rc.name AS range_name,
            rc.distance,
            rc.number_of_ends
        FROM
            rounds r
        JOIN
            round_category_details rcd ON r.round_category_id = rcd.round_category_id
        JOIN
            range_category rc ON rcd.range_category_id = rc.range_category_id
        WHERE
            r.round_id = ?";
            
// (Lưu ý: Chúng ta đã gỡ bỏ 'ORDER BY' bị lỗi trước đó)

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Lỗi chuẩn bị SQL: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $round_id);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi thực thi SQL: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
$ranges = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Đảm bảo kiểu dữ liệu là số
        $row['range_category_id'] = intval($row['range_category_id']);
        $row['number_of_ends'] = intval($row['number_of_ends']);
        $ranges[] = $row;
    }
}

$stmt->close();
$conn->close();

// 3. Trả về kết quả dưới dạng JSON
header('Content-Type: application/json');
echo json_encode($ranges);
?>