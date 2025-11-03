<?php
// Tắt báo lỗi notice (vì file này chỉ trả về JSON)
error_reporting(E_ALL & ~E_NOTICE);
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
// sắp xếp theo thứ tự (giả sử là 'range_order' trong bảng join)
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
            r.round_id = ?
        ORDER BY
            rcd.detail_id ASC"; // Sắp xếp theo thứ tự được thêm vào (giả sử)
            // LƯU Ý: Sẽ tốt hơn nếu bảng 'round_category_details' có cột 'range_order' (thứ tự 1, 2, 3...)

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Lỗi chuẩn bị SQL: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $round_id);
$stmt->execute();
$result = $stmt->get_result();

$ranges = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Đảm bảo kiểu dữ liệu là số
        $row['range_category_id'] = intval($row['range_category_id']);
        $row['number_of_ends'] = intval($row['number_of_ends']);
        $ranges[] = $row;
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi thực thi SQL: ' . $stmt->error]);
    exit;
}

$stmt->close();
$conn->close();

// 3. Trả về kết quả dưới dạng JSON
header('Content-Type: application/json');
echo json_encode($ranges);
?>