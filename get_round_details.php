<?php
header('Content-Type: application/json; charset=utf-8');
include 'connect.php';

// Tên biến đã đổi thành 'round_cat_id' để rõ ràng,
// nhưng JS của bạn đang gửi 'round_id', nên chúng ta sẽ dùng nó
if (!isset($_GET['round_id']) || !is_numeric($_GET['round_id'])) {
    echo json_encode(['success' => false, 'message' => 'round_category_id missing']);
    exit;
}

$round_category_id = intval($_GET['round_id']); // Đây thực chất là round_category_id

$sql = "
  SELECT 
    SUM(rc.number_of_ends) AS total_ends,
    GROUP_CONCAT(CONCAT(rc.distance, 'm (', rc.number_of_ends, ' ends)') SEPARATOR ' | ') AS ranges_desc,
    rcat.round_name
  FROM round_category_details rcd
  JOIN range_category rc ON rcd.range_category_id = rc.range_category_id
  JOIN round_category rcat ON rcd.round_category_id = rcat.round_category_id
  WHERE rcd.round_category_id = ?
  GROUP BY rcat.round_category_id, rcat.round_name
";

$stmt = $conn->prepare($sql);
if ($stmt) {
  $stmt->bind_param("i", $round_category_id);
  $stmt->execute();
  $res = $stmt->get_result();
  
  if ($row = $res->fetch_assoc()) {
    $total_ends = intval($row['total_ends']);
    $desc = trim($row['round_name'] . ' — ' . $row['ranges_desc']);
    echo json_encode([
        'success' => true, 
        'total_ends' => max(1, $total_ends), // Đảm bảo luôn > 0
        'description' => $desc
    ]);
    exit;
  }
}

// Fallback nếu không tìm thấy (ví dụ: round chưa được định nghĩa trong manage_rounds)
echo json_encode(['success' => true, 'total_ends' => 6, 'description' => 'Round chưa có cấu hình (Mặc định 6 ends)']);