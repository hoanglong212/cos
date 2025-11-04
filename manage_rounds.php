<?php
// Bật hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'connect.php';

$status_message = ''; // Dùng để hiển thị thông báo thành công/lỗi
$selected_round_cat_id = null;
$selected_round_name = '';
$all_rounds_list = [];
$all_ranges_list = [];
$linked_ranges_list = [];

// === BƯỚC 1: XỬ LÝ KHI THÊM MỚI (FORM SUBMIT) ===
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_range'])) {
    $round_cat_id = intval($_POST['round_category_id']);
    $range_cat_id_to_add = intval($_POST['range_category_id_to_add']);

    if ($round_cat_id > 0 && $range_cat_id_to_add > 0) {
        // 1. Kiểm tra xem liên kết này đã tồn tại chưa
        $sql_check = "SELECT COUNT(*) as count FROM round_category_details 
                      WHERE round_category_id = ? AND range_category_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ii", $round_cat_id, $range_cat_id_to_add);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $row = $result_check->fetch_assoc();
        $stmt_check->close();

        if ($row['count'] == 0) {
            // 2. Nếu chưa tồn tại, thêm mới
            $sql_insert = "INSERT INTO round_category_details (round_category_id, range_category_id) 
                           VALUES (?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ii", $round_cat_id, $range_cat_id_to_add);
            if ($stmt_insert->execute()) {
                $status_message = "✅ Đã thêm cự ly mới thành công!";
            } else {
                $status_message = "❌ Lỗi khi thêm: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        } else {
            $status_message = "⚠️ Cự ly này đã được thêm vào round từ trước.";
        }
    }
    // Cập nhật ID đã chọn để tải lại trang
    $selected_round_cat_id = $round_cat_id;
}

// === BƯỚC 2: LẤY ID TỪ URL (KHI CHỌN TỪ DROPDOWN) ===
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['round_cat_id'])) {
    $selected_round_cat_id = intval($_GET['round_cat_id']);
}

// === BƯỚC 3: TẢI TẤT CẢ DỮ LIỆU ĐỂ HIỂN THỊ ===

// 3.1. Tải TẤT CẢ Round Categories (cho dropdown chính)
$result_all_rounds = $conn->query("SELECT round_category_id, round_name FROM round_category ORDER BY round_name");
if ($result_all_rounds) {
    while ($row = $result_all_rounds->fetch_assoc()) {
        $all_rounds_list[] = $row;
    }
}

// 3.2. Tải TẤT CẢ Range Categories (cho dropdown "thêm mới")
$result_all_ranges = $conn->query("SELECT range_category_id, name, distance, number_of_ends FROM range_category ORDER BY name");
if ($result_all_ranges) {
    while ($row = $result_all_ranges->fetch_assoc()) {
        $all_ranges_list[] = $row;
    }
}

// 3.3. NẾU ĐÃ CHỌN MỘT ROUND, tải các cự ly (ranges) ĐÃ LIÊN KẾT
if ($selected_round_cat_id) {
    // Lấy tên của round đã chọn
    $stmt_name = $conn->prepare("SELECT round_name FROM round_category WHERE round_category_id = ?");
    $stmt_name->bind_param("i", $selected_round_cat_id);
    $stmt_name->execute();
    $result_name = $stmt_name->get_result();
    $selected_round_name = $result_name->fetch_assoc()['round_name'] ?? 'Không rõ';
    $stmt_name->close();

    // Lấy các cự ly đã liên kết
    $sql_linked = "SELECT rc.name, rc.distance, rc.number_of_ends, rcd.round_category_id, rcd.range_category_id
                   FROM round_category_details rcd
                   JOIN range_category rc ON rcd.range_category_id = rc.range_category_id
                   WHERE rcd.round_category_id = ?";
    $stmt_linked = $conn->prepare($sql_linked);
    $stmt_linked->bind_param("i", $selected_round_cat_id);
    $stmt_linked->execute();
    $result_linked = $stmt_linked->get_result();
    if ($result_linked) {
        while ($row = $result_linked->fetch_assoc()) {
            $linked_ranges_list[] = $row;
        }
    }
    $stmt_linked->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý Cấu trúc Round</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* CSS cho trang quản lý */
    .mainboard {
        text-align: left; 
        max-width: 800px;
    }
    .mainboard h1, .mainboard h2, .mainboard h3 {
        text-align: center;
    }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
    .form-group select {
      width: 100%; padding: 10px; box-sizing: border-box; 
      border: 1px solid #ccc; border-radius: 5px; font-size: 1rem;
    }
    .status-message {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        text-align: center;
        font-weight: bold;
    }
    .status-success { background-color: #d4edda; color: #155724; }
    .status-error { background-color: #f8d7da; color: #721c24; }
    .status-warn { background-color: #fff3cd; color: #856404; }

    .linked-ranges {
        background: #f9f9f9;
        border: 1px solid #eee;
        padding: 10px 20px;
        border-radius: 8px;
    }
    .linked-ranges ul {
        padding-left: 20px;
    }
    .linked-ranges li {
        font-size: 1.1em;
        margin-bottom: 8px;
    }
    .add-range-form {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 2px solid #eee;
    }
  </style>
</head>
<body>
  
  <div class="mainboard">
    <h1>📋 Quản lý Cấu trúc Round</h1>
    <p style="text-align: center;">Trang này dùng để định nghĩa một "Round" (ví dụ: Perth) bao gồm những "Cự ly" (ví dụ: 50m, 40m) nào.</p>

    <?php if (!empty($status_message)): ?>
        <?php
            $status_class = 'status-warn'; // Mặc định là cảnh báo
            if (strpos($status_message, '✅') !== false) $status_class = 'status-success';
            if (strpos($status_message, '❌') !== false) $status_class = 'status-error';
        ?>
        <div class="status-message <?php echo $status_class; ?>">
            <?php echo htmlspecialchars($status_message); ?>
        </div>
    <?php endif; ?>

    <form action="manage_rounds.php" method="GET" class="form-group">
      <label for="round_cat_id">Chọn Round Category để xem/sửa:</label>
      <select name="round_cat_id" id="round_cat_id" onchange="this.form.submit()">
        <option value="">-- Chọn một round --</option>
        <?php foreach ($all_rounds_list as $round): ?>
          <option value="<?php echo htmlspecialchars($round['round_category_id']); ?>"
            <?php if ($round['round_category_id'] == $selected_round_cat_id) echo 'selected'; ?>>
            <?php echo htmlspecialchars($round['round_name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
    
    <hr>

    <?php if ($selected_round_cat_id): ?>
        <h2>Cấu trúc của: <?php echo htmlspecialchars($selected_round_name); ?></h2>

        <div class="linked-ranges">
            <h3>Các cự ly (Ranges) đã định nghĩa:</h3>
            <?php if (count($linked_ranges_list) > 0): ?>
                <ul>
                    <?php foreach ($linked_ranges_list as $linked_range): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($linked_range['name']); ?></strong>
                            (<?php echo htmlspecialchars($linked_range['distance']); ?>m, <?php echo htmlspecialchars($linked_range['number_of_ends']); ?> ends)
                            </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="text-align: center; color: #888;">Chưa có cự ly nào được định nghĩa cho round này.</p>
            <?php endif; ?>
        </div>

        <div class="add-range-form">
            <h3>Thêm cự ly mới vào "<?php echo htmlspecialchars($selected_round_name); ?>"</h3>
            <form action="manage_rounds.php" method="POST">
                <input type="hidden" name="round_category_id" value="<?php echo htmlspecialchars($selected_round_cat_id); ?>">
                
                <div class="form-group">
                    <label for="range_category_id_to_add">Chọn cự ly (Range) để thêm:</label>
                    <select name="range_category_id_to_add" id="range_category_id_to_add" required>
                        <option value="">-- Chọn cự ly --</option>
                        <?php foreach ($all_ranges_list as $range): ?>
                            <option value="<?php echo htmlspecialchars($range['range_category_id']); ?>">
                                <?php echo htmlspecialchars($range['name'] . ' (' . $range['distance'] . 'm, ' . $range['number_of_ends'] . ' ends)'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="text-align: right;">
                    <button type="submit" name="add_range" class="btn">Thêm cự ly</button>
                </div>
            </form>
        </div>

    <?php endif; ?>

    <a href="index.php" class="btn" style="background: #777; margin-top: 20px;">⬅ Về trang chính</a>
    
  </div> </body>
</html>