<?php include 'connect.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Danh sách Archer</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h1>🏹 Danh sách Archer (user_table)</h1>
  <table>
    <tr>
      <th>ID</th>
      <th>Họ</th>
      <th>Tên</th>
      <th>Giới tính</th>
      <th>Xem điểm</th>
    </tr>
    <?php
      $sql = "SELECT user_id, first_name, last_name, gender FROM user_table LIMIT 20";
      $result = $conn->query($sql);
      if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
          echo "<tr>
                  <td>{$row['user_id']}</td>
                  <td>{$row['first_name']}</td>
                  <td>{$row['last_name']}</td>
                  <td>{$row['gender']}</td>
                  <td><a href='view_scores.php?user_id={$row['user_id']}'>Xem điểm</a></td>
                </tr>";
        }
      } else {
        echo "<tr><td colspan='5'>Chưa có người bắn nào.</td></tr>";
      }
    ?>
  </table>
  <a href="add_score.php" class="btn">➕ Nhập điểm mới</a>
</body>
</html>

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
    die("❌ Lỗi SQL: " . $conn->error);
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

body {
  font-family: Arial, sans-serif;
  background-color: #eeeff2;
  color: #271d1d;
  text-align: center;
  margin: 0;
  padding: 20px;
  background-image: url(archer.jpg);
  background-size:  100% 100%;
  background-position: center;
  background-repeat: no-repeat; 
  background-attachment: fixed;
}

.mainboard {
   width: 85%;
  max-width: 1000px;
  margin: 0 auto;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 10px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.2);
  padding: 30px 20px;
}


h1 {
  color: #2e4ba4;
}


.table-container {
  width: 80%;
  margin: 20px auto;
  max-height: 500px;
  overflow-y: auto;
  background: #fff;
  border-radius: 6px;
  box-shadow: 0 0 8px rgba(0,0,0,0.1);
  padding-right: 10px;          
  scrollbar-gutter: stable;
}

.table-container::-webkit-scrollbar {
  width: 8px;
}
.table-container::-webkit-scrollbar-thumb {
  background-color: #718093;
  border-radius: 4px;
}
.table-container::-webkit-scrollbar-thumb:hover {
  background-color: #596275;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 10px;
  border: 1px solid #dcdde1;
}

th {
  background: #396cd9;
  color: white;
  position: sticky;
  top: 0;
  z-index: 1;
}

tr:nth-child(even) {
  background: #f1f2f6;
}


a.btn, button {
  display: inline-block;
  margin: 10px;
  padding: 10px 20px;
  background: #44bd32;
  color: white;
  text-decoration: none;
  border-radius: 5px;
}

a.btn:hover, button:hover {
  background: #4cd137;
}


input {
  margin: 5px;
  padding: 5px;
}

<?php include 'connect.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Lịch sử điểm</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="mainboard">
  <h1>📊 Lịch sử điểm</h1>
  <form method="GET" action="">
    <label>Nhập User ID:</label>
    <input type="number" name="user_id" required>
    <button type="submit">Xem điểm</button>
    
  </form>

  <?php
  if (isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
    $sql = "SELECT s.score_id, s.total_score, s.competition_id, s.round_id, r.location, s.date_recorded
            FROM scores s
            LEFT JOIN rounds r ON s.round_id = r.round_id
            WHERE s.user_id = $user_id
            ORDER BY s.score_id DESC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
      echo "<table>
              <tr>
                <th>Score ID</th>
                <th>Round</th>
                <th>Competition</th>
                <th>Tổng điểm</th>
                <th>Ngày ghi</th>
              </tr>";
      while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['score_id']}</td>
                <td>{$row['location']}</td>
                <td>{$row['competition_id']}</td>
                <td>{$row['total_score']}</td>
                <td>{$row['date_recorded']}</td>
              </tr>";
      }
      echo "</table>";
    } else {
      echo "<p>⚠️ Người này chưa có điểm nào.</p>";
    }
  }
  ?>
  </div>
</body>

<p>
<a href="index.php" class="btn">⬅ Quay lại</a>
</p>
</html>


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

<?php
$servername = "sql12.freesqldatabase.com";
$username = "sql12802109";
$password = "QM8dUEyvi2";
$dbname = "sql12802109";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("❌ Kết nối thất bại: " . $conn->connect_error);
}
?>
