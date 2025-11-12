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
$stats = $scoreManager->getStats();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Quản lý Điểm số</title>
    <link rel="stylesheet" href="../css/score_recorder.css">
</head>
<body>
    <div class="container">
        <h1>📊 Dashboard - Quản lý Điểm số</h1>
        
        <!-- Navigation -->
        <div class="nav-buttons">
            <a href="scores.php" class="nav-btn">📋 Quản lý Điểm</a>
            <a href="archers.php" class="nav-btn">👥 Quản lý Cung thủ</a>
            <a href="rounds.php" class="nav-btn">🎯 Quản lý Rounds</a>
            <a href="competitions.php" class="nav-btn">🏆 Quản lý Competitions</a>
            <a href="equivalents.php" class="nav-btn">🔄 Equivalent Rounds</a>
            <a href="../homepage.php" class="nav-btn">🏠 Về Trang Chủ</a>
        </div>

        <!-- Stats Grid -->
        <div class="section">
            <h2>📊 Thống kê tổng quan</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_scores'] ?? 0; ?></div>
                    <div class="stat-label">Tổng số điểm</div>
                </div>
                <?php foreach (($stats['approval_stats'] ?? []) as $approval): ?>
                    <?php if ($approval['count'] > 0): ?>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $approval['count']; ?></div>
                        <div class="stat-label"><?php echo $approval['is_approved'] ? 'Đã duyệt' : 'Chờ duyệt'; ?></div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if (isset($stats['general_stats'])): ?>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['general_stats']['avg_score'], 1); ?></div>
                    <div class="stat-label">Điểm trung bình</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['general_stats']['max_score']; ?></div>
                    <div class="stat-label">Điểm cao nhất</div>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($stats['top_scores'])): ?>
            <div class="stats-detail">
                <div class="stat-detail-card">
                    <h4>🏆 Top 5 Điểm cao nhất</h4>
                    <?php foreach ($stats['top_scores'] as $index => $top): ?>
                        <?php if ($top['total_score']): ?>
                        <div style="margin: 8px 0; padding: 5px; background: white; border-radius: 4px;">
                            <strong><?php echo $index + 1; ?>.</strong> 
                            <?php echo htmlspecialchars($top['first_name'] . ' ' . $top['last_name']); ?> - 
                            <span class="score-high"><?php echo $top['total_score']; ?> điểm</span>
                            <br><small><?php echo htmlspecialchars($top['round_name']); ?></small>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="section">
            <h2>🚀 Hành động nhanh</h2>
            <div class="quick-actions">
                <a href="../add_score_step1.php" class="btn btn-primary">➕ Nhập Điểm Mới</a>
                <a href="scores.php?is_approved=0" class="btn btn-warning">⏳ Điểm Chờ Duyệt</a>
                <a href="archers.php" class="btn btn-info">👥 Thêm Cung thủ</a>
                <a href="rounds.php" class="btn btn-success">🎯 Quản lý Rounds</a>
            </div>
        </div>
    </div>
</body>
</html>