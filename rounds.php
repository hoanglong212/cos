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

// Xử lý form thêm range
$message = "";
$action_success = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_range'])) {
        if ($scoreManager->addRange($_POST['range_name'], $_POST['distance'], $_POST['face_size'], $_POST['number_of_ends'])) {
            $message = "✅ Thêm range thành công!";
        } else {
            $message = "❌ Lỗi khi thêm range!";
            $action_success = false;
        }
    }
    elseif (isset($_POST['add_round'])) {
        try {
            $round_id = $scoreManager->addRound($_POST['round_name'], $_POST['range_ids'] ?? []);
            $message = "✅ Thêm round thành công!";
        } catch (Exception $e) {
            $message = "❌ Lỗi khi thêm round: " . $e->getMessage();
            $action_success = false;
        }
    }
}

$rounds_with_ranges = $scoreManager->getAllRoundsWithRanges();
$ranges = $scoreManager->getAllRanges();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Rounds - Archery System</title>
    <link rel="stylesheet" href="../css/score_recorder.css">
</head>
<body>
    <div class="container">
        <h1>🎯 Quản lý Rounds & Ranges</h1>
        
        <!-- Navigation -->
        <div class="nav-buttons">
            <a href="dashboard.php" class="nav-btn">📊 Dashboard</a>
            <a href="scores.php" class="nav-btn">📋 Quản lý Điểm</a>
            <a href="archers.php" class="nav-btn">👥 Quản lý Cung thủ</a>
            <a href="competitions.php" class="nav-btn">🏆 Competitions</a>
            <a href="../homepage.php" class="nav-btn">🏠 Về Trang Chủ</a>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $action_success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Form thêm Range -->
        <div class="section">
            <div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                <h3>➕ Thêm Range mới</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-column">
                            <label>Tên Range:</label>
                            <input type="text" name="range_name" required>
                        </div>
                        <div class="form-column">
                            <label>Khoảng cách (m):</label>
                            <input type="number" name="distance" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-column">
                            <label>Kích thước target:</label>
                            <select name="face_size" required>
                                <option value="80cm">80cm</option>
                                <option value="122cm">122cm</option>
                                <option value="120cm">120cm</option>
                            </select>
                        </div>
                        <div class="form-column">
                            <label>Số ends:</label>
                            <input type="number" name="number_of_ends" required>
                        </div>
                    </div>
                    <button type="submit" name="add_range" class="btn btn-success">➕ Thêm Range</button>
                </form>
            </div>

            <!-- Form thêm Round -->
            <div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                <h3>➕ Thêm Round mới</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Tên Round:</label>
                        <input type="text" name="round_name" required>
                    </div>
                    <div class="form-group">
                        <label>Chọn Ranges (giữ Ctrl để chọn nhiều):</label>
                        <select name="range_ids[]" multiple size="5" style="height: 120px;">
                            <?php foreach ($ranges as $range): ?>
                                <option value="<?php echo $range['range_category_id']; ?>">
                                    <?php echo htmlspecialchars($range['name'] . ' - ' . $range['distance'] . 'm - ' . $range['face_size']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="add_round" class="btn btn-success">➕ Thêm Round</button>
                </form>
            </div>

            <!-- Danh sách Rounds -->
            <h3>📋 Danh sách Rounds</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên Round</th>
                        <th>Ranges</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rounds_with_ranges as $round): ?>
                        <tr>
                            <td><?php echo $round['round_category_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($round['round_name']); ?></strong></td>
                            <td>
                                <?php if (!empty($round['ranges'])): ?>
                                    <?php echo htmlspecialchars($round['ranges']); ?>
                                <?php else: ?>
                                    <span style="color: #999;">Chưa có ranges</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $round['status'] ?? 'active'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>