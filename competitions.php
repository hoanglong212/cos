<?php
session_start();
require_once '../connect.php';
require_once '../models/ScoreManager.php';

// Check recorder role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'recorder' && $_SESSION['role'] != 'admin')) {
    header("Location: ../login.php");
    exit();
}

$scoreManager = new CompleteScoreManager($conn);

// Xử lý form thêm competition
$message = "";
$action_success = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_competition'])) {
    if ($scoreManager->addCompetition($_POST['competition_name'], $_POST['start_date'], $_POST['end_date'], $_POST['championship_id'] ?? null)) {
        $message = "✅ Thêm competition thành công!";
    } else {
        $message = "❌ Lỗi khi thêm competition!";
        $action_success = false;
    }
}

$competitions = $scoreManager->getCompetitions(50);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Competitions - Archery System</title>
    <link rel="stylesheet" href="../css/score_recorder.css">
</head>
<body>
    <div class="container">
        <h1>🏆 Quản lý Competitions</h1>
        
        <!-- Navigation -->
        <div class="nav-buttons">
            <a href="dashboard.php" class="nav-btn">📊 Dashboard</a>
            <a href="scores.php" class="nav-btn">📋 Quản lý Điểm</a>
            <a href="archers.php" class="nav-btn">👥 Quản lý Cung thủ</a>
            <a href="rounds.php" class="nav-btn">🎯 Quản lý Rounds</a>
            <a href="../homepage.php" class="nav-btn">🏠 Về Trang Chủ</a>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $action_success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Form thêm competition -->
        <div class="section">
            <div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                <h3>➕ Thêm Competition mới</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Tên Competition:</label>
                        <input type="text" name="competition_name" required>
                    </div>
                    <div class="form-row">
                        <div class="form-column">
                            <label>Ngày bắt đầu:</label>
                            <input type="date" name="start_date" required>
                        </div>
                        <div class="form-column">
                            <label>Ngày kết thúc:</label>
                            <input type="date" name="end_date" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Championship ID (nếu có):</label>
                        <input type="text" name="championship_id">
                    </div>
                    <button type="submit" name="add_competition" class="btn btn-success">➕ Thêm Competition</button>
                </form>
            </div>

            <!-- Danh sách competitions -->
            <h3>📋 Danh sách Competitions</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên Competition</th>
                        <th>Championship</th>
                        <th>Ngày bắt đầu</th>
                        <th>Ngày kết thúc</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($competitions as $comp): ?>
                        <tr>
                            <td><?php echo $comp['competition_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($comp['competition_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($comp['championship_id'] ?? 'N/A'); ?></td>
                            <td><?php echo $comp['start_date']; ?></td>
                            <td><?php echo $comp['end_date']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>