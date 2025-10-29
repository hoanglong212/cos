<?php include 'connect.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Nhập điểm mới</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h1>🎯 Nhập điểm mới</h1>

  <form action="insert_score.php" method="POST">
    <label>Người bắn (User):</label>
    <select name="user_id" required>
      <option value="">-- Chọn --</option>
      <?php
        $users = $conn->query("SELECT user_id, first_name, last_name FROM user_table");
        while ($u = $users->fetch_assoc()) {
          echo "<option value='{$u['user_id']}'>{$u['first_name']} {$u['last_name']}</option>";
        }
      ?>
    </select><br>

    <label>Round:</label>
    <select name="round_id" required>
      <option value="">-- Chọn Round --</option>
      <?php
        $rounds = $conn->query("SELECT round_id, location FROM rounds");
        while ($r = $rounds->fetch_assoc()) {
          echo "<option value='{$r['round_id']}'>Round {$r['round_id']} - {$r['location']}</option>";
        }
      ?>
    </select><br>

    <label>Competition ID (nếu có):</label>
    <input type="text" name="competition_id" placeholder="VD: comp001"><br>

    <label>Điểm từng mũi tên (cách nhau bằng dấu phẩy):</label><br>
    <input type="text" name="arrows" placeholder="10,9,8,10,7,9" required><br>

    <button type="submit">Lưu điểm</button>
  </form>

  <a href="index.php" class="btn">⬅ Quay lại</a>
</body>
</html>
