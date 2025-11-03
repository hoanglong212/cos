<?php
// 1. PHẦN LOGIC: Tải dữ liệu trước
include 'connect.php';

$users_list = [];
$rounds_list = [];
$error_message = '';

// 1.1. Tải danh sách người bắn (users)
// Thêm ORDER BY để sắp xếp danh sách theo tên, giúp người dùng dễ tìm
$users_result = $conn->query("SELECT user_id, first_name, last_name FROM user_table ORDER BY first_name, last_name");

if ($users_result) {
    while ($u = $users_result->fetch_assoc()) {
        $users_list[] = $u;
    }
} else {
    // Ghi lại lỗi nếu không thể tải user
    $error_message .= "Lỗi: không thể tải danh sách người bắn. " . $conn->error . "<br>";
}

// 1.2. Tải danh sách round
// Thêm ORDER BY để sắp xếp (ví dụ: theo round_id giảm dần để round mới nhất lên đầu)
// Thêm round_date (nếu có) để hiển thị
$rounds_result = $conn->query("SELECT round_id, location, round_date FROM rounds ORDER BY round_id DESC");

if ($rounds_result) {
    while ($r = $rounds_result->fetch_assoc()) {
        $rounds_list[] = $r;
    }
} else {
    // Ghi lại lỗi nếu không thể tải round
    $error_message .= "Lỗi: không thể tải danh sách round. " . $conn->error . "<br>";
}

// Đóng kết nối sau khi đã lấy hết dữ liệu vào mảng
$conn->close();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nhập điểm mới</title>
  <link rel="stylesheet" href="style.css">
  
  <style>
    .mainboard {
        /* Ghi đè text-align: center từ file style.css */
        text-align: left; 
        /* Form nên hẹp hơn bảng, 600px là hợp lý */
        max-width: 600px;
    }
    .mainboard h1 {
        text-align: center; /* Nhưng tiêu đề h1 thì vẫn căn giữa */
    }
    .form-group {
      margin-bottom: 15px;
    }
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
    }
    .form-group input[type="text"],
    .form-group select {
      width: 100%;
      padding: 10px;
      box-sizing: border-box; 
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 1rem;
    }
    .form-group small {
      display: block;
      margin-top: 5px;
      color: #777;
    }
    .form-actions {
      text-align: right;
      margin-top: 20px;
    }
    .form-actions .btn-back {
        /* Style riêng cho nút quay lại nếu muốn */
        background-color: #f0f0f0;
        color: #333;
    }
    .form-actions .btn-back:hover {
        background-color: #ddd;
    }
    .error-message {
      background-color: #f8d7da;
      color: #721c24;
      padding: 10px;
      border: 1px solid #f5c6cb;
      border-radius: 4px;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  
  <div class="mainboard">
    <h1>🎯 Nhập điểm mới</h1>

    <?php if (!empty($error_message)): ?>
      <div class="error-message">
        <strong>Đã xảy ra lỗi khi tải dữ liệu:</strong><br>
        <?php echo $error_message; ?>
      </div>
    <?php endif; ?>

    <form action="insert_score.php" method="POST">
      
      <div class="form-group">
        <label for="user_id">Người bắn (User):</label>
        <select name="user_id" id="user_id" required>
          <option value="">-- Chọn --</option>
          <?php foreach ($users_list as $u): ?>
            <option value="<?php echo htmlspecialchars($u['user_id']); ?>">
              <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="round_id">Round:</label>
        <select name="round_id" id="round_id" required>
          <option value="">-- Chọn Round --</option>
          <?php foreach ($rounds_list as $r): ?>
            <?php
              $display_date = '';
              if (isset($r['round_date']) && !empty($r['round_date'])) {
                  $display_date = date(" (d/m/Y)", strtotime($r['round_date']));
              }
            ?>
            <option value="<?php echo htmlspecialchars($r['round_id']); ?>">
              Round <?php echo htmlspecialchars($r['round_id']); ?> - <?php echo htmlspecialchars($r['location']) . $display_date; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="competition_id">Competition ID (nếu có):</label>
        <input type="text" name="competition_id" id="competition_id" placeholder="VD: comp001">
      </div>

      <div class="form-group">
        <label for="arrows">Điểm từng mũi tên (cách nhau bằng dấu phẩy):</label>
        <input type="text" name="arrows" id="arrows" placeholder="10,X,9,8,M,7" required
               pattern="^([0-9]|10|[XxMm])(,[0-9]|,10|,[XxMm])*$"
               title="Nhập điểm (0-10, X, M) cách nhau bằng dấu phẩy, không có dấu cách.">
        <small>Ví dụ: 10,X,9,8,M,7</small>
      </div>

      <div class="form-actions">
        <a href="index.php" class="btn btn-back">⬅ Quay lại</a>
        <button type="submit" class="btn">Lưu điểm</button>
      </div>
    </form>
  </div> </body>
</html>