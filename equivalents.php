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

// Xử lý form thêm equivalent round
$message = "";
$action_success = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_equivalent_round'])) {
    if ($scoreManager->addEquivalentRound($_POST['base_round_id'], $_POST['equivalent_round_id'], 
                                       $_POST['gender'], $_POST['age_group'], $_POST['bow_category_id'], $_POST['description'])) {
        $message = "✅ Thêm equivalent round thành công!";
    } else {
        $message = "❌ Lỗi khi thêm equivalent round!";
        $action_success = false;
    }
}

$equivalent_rounds = $scoreManager->getEquivalentRounds();
$rounds_with_ranges = $scoreManager->getAllRoundsWithRanges();
$bow_categories = $scoreManager->getBowCategories();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equivalent Rounds - Archery System</title>
    <link rel="stylesheet" href="../css/score_recorder.css">
</head>
<body>
    <div class="container">
        <h1>🔄 Quản lý Equivalent Rounds</h1>
        
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

        <!-- Form thêm equivalent round -->
        <div class="section">
            <div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                <h3>➕ Thêm Equivalent Round</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-column">
                            <label>Base Round:</label>
                            <select name="base_round_id" required>
                                <?php foreach ($rounds_with_ranges as $round): ?>
                                    <option value="<?php echo $round['round_category_id']; ?>">
                                        <?php echo htmlspecialchars($round['round_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-column">
                            <label>Equivalent Round:</label>
                            <select name="equivalent_round_id" required>
                                <?php foreach ($rounds_with_ranges as $round): ?>
                                    <option value="<?php echo $round['round_category_id']; ?>">
                                        <?php echo htmlspecialchars($round['round_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-column">
                            <label>Giới tính:</label>
                            <select name="gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="form-column">
                            <label>Nhóm tuổi:</label>
                            <input type="text" name="age_group" placeholder="e.g., Open, 50+, U21" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Loại cung:</label>
                        <select name="bow_category_id" required>
                            <?php foreach ($bow_categories as $bow): ?>
                                <option value="<?php echo $bow['bow_category_id']; ?>">
                                    <?php echo htmlspecialchars($bow['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mô tả:</label>
                        <textarea name="description" rows="2"></textarea>
                    </div>
                    <button type="submit" name="add_equivalent_round" class="btn btn-success">➕ Thêm Equivalent</button>
                </form>
            </div>

            <!-- Danh sách equivalent rounds -->
            <h3>📋 Danh sách Equivalent Rounds</h3>
            <?php if (!empty($equivalent_rounds)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Base Round</th>
                        <th>Equivalent Round</th>
                        <th>Giới tính</th>
                        <th>Nhóm tuổi</th>
                        <th>Loại cung</th>
                        <th>Mô tả</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equivalent_rounds as $equiv): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($equiv['base_round']); ?></td>
                            <td><?php echo htmlspecialchars($equiv['equivalent_round']); ?></td>
                            <td><?php echo htmlspecialchars($equiv['gender']); ?></td>
                            <td><?php echo htmlspecialchars($equiv['age_group']); ?></td>
                            <td><?php echo htmlspecialchars($equiv['bow_name']); ?></td>
                            <td><?php echo htmlspecialchars($equiv['description'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>Chưa có equivalent rounds nào được định nghĩa.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>