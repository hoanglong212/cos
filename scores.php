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

// Xử lý actions
$message = "";
$action_success = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_arrow'])) {
    if ($scoreManager->updateArrowScore($_POST['arrow_id'], $_POST['arrow_score'])) {
        $scoreManager->recalculateTotalScore($_POST['score_id']);
        $message = "✅ Cập nhật điểm arrow thành công!";
    } else {
        $message = "❌ Lỗi khi cập nhật điểm!";
        $action_success = false;
    }
}

if (isset($_GET['approve_id'])) {
    if ($scoreManager->approveScore($_GET['approve_id'])) {
        $message = "✅ Duyệt điểm thành công!";
    } else {
        $message = "❌ Lỗi khi duyệt điểm!";
        $action_success = false;
    }
}

if (isset($_GET['delete_id'])) {
    $score_id = $_GET['delete_id'];
    if ($scoreManager->deleteScore($score_id)) {
        $message = "✅ Xóa điểm thành công!";
    } else {
        $message = "❌ Lỗi khi xóa điểm!";
    }
}

// Phân trang và lọc
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$filters = [
    'is_approved' => $_GET['is_approved'] ?? '',
    'user_id' => $_GET['user_id'] ?? '',
    'round_id' => $_GET['round_id'] ?? ''
];

$scores = $scoreManager->getAllScores($limit, $offset, $filters);
$total_scores = $scoreManager->getScoresCount($filters);
$total_pages = ceil($total_scores / $limit);

$users = $scoreManager->getUsers(50);
$rounds = $scoreManager->getRounds();

// Lấy chi tiết score nếu có yêu cầu
$score_details = [];
if (isset($_GET['view_details'])) {
    $score_details = $scoreManager->getScoreDetails($_GET['view_details']);
}

$filter_query = '';
if ($filters['is_approved'] !== '') $filter_query .= '&is_approved=' . $filters['is_approved'];
if ($filters['user_id'] !== '') $filter_query .= '&user_id=' . $filters['user_id'];
if ($filters['round_id'] !== '') $filter_query .= '&round_id=' . $filters['round_id'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Điểm số - Archery System</title>
    <link rel="stylesheet" href="../css/score_recorder.css">
</head>
<body>
    <div class="container">
        <h1>📋 Quản lý Điểm số</h1>
        
        <!-- Navigation -->
        <div class="nav-buttons">
            <a href="dashboard.php" class="nav-btn">📊 Dashboard</a>
            <a href="archers.php" class="nav-btn">👥 Quản lý Cung thủ</a>
            <a href="rounds.php" class="nav-btn">🎯 Quản lý Rounds</a>
            <a href="../add_score_step1.php" class="nav-btn">➕ Nhập Điểm Mới</a>
            <a href="../homepage.php" class="nav-btn">🏠 Về Trang Chủ</a>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $action_success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Bộ lọc -->
        <div class="section">
            <h2>🔍 Lọc dữ liệu</h2>
            <form method="GET" class="filter-form">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Cung thủ:</label>
                        <select name="user_id">
                            <option value="">Tất cả cung thủ</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['user_id']; ?>" <?php echo $filters['user_id'] == $user['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Round:</label>
                        <select name="round_id">
                            <option value="">Tất cả round</option>
                            <?php foreach ($rounds as $round): ?>
                                <option value="<?php echo $round['round_id']; ?>" <?php echo $filters['round_id'] == $round['round_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($round['round_name'] . ' (' . $round['date_recorded'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Trạng thái:</label>
                        <select name="is_approved">
                            <option value="">Tất cả trạng thái</option>
                            <option value="1" <?php echo $filters['is_approved'] === '1' ? 'selected' : ''; ?>>Đã duyệt</option>
                            <option value="0" <?php echo $filters['is_approved'] === '0' ? 'selected' : ''; ?>>Chờ duyệt</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">🔍 Lọc</button>
                        <a href="scores.php" class="btn">🔄 Xóa lọc</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Danh sách điểm số -->
        <div class="section">
            <h2>📋 Danh sách Điểm số (<?php echo $total_scores; ?> kết quả)</h2>
            
            <!-- Phân trang -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?php echo $filter_query; ?>">« Đầu</a>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $filter_query; ?>">‹ Trước</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo $filter_query; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $filter_query; ?>">Sau ›</a>
                    <a href="?page=<?php echo $total_pages; ?><?php echo $filter_query; ?>">Cuối »</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (empty($scores)): ?>
                <div style="text-align: center; color: #666; padding: 40px;">
                    <h3>🎯 Chưa có điểm số nào được ghi nhận</h3>
                    <p>Hãy bắt đầu bằng cách nhập điểm mới!</p>
                    <a href="../add_score_step1.php" class="nav-btn" style="margin-top: 15px;">➕ Nhập Điểm Ngay</a>
                </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cung thủ</th>
                        <th>Round</th>
                        <th>Hạng</th>
                        <th>Dụng cụ</th>
                        <th>Competition</th>
                        <th>Điểm số</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($scores as $score): 
                        $scoreClass = 'score-medium';
                        if ($score['total_score'] > 500) $scoreClass = 'score-high';
                        elseif ($score['total_score'] < 200) $scoreClass = 'score-low';
                    ?>
                        <tr>
                            <td><strong>#<?php echo $score['score_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($score['first_name'] . ' ' . $score['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($score['round_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($score['archer_category_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($score['bow_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($score['competition_id'] ?? 'N/A'); ?></td>
                            <td class="<?php echo $scoreClass; ?>">
                                <strong><?php echo $score['total_score']; ?></strong>
                            </td>
                            <td class="<?php echo $score['is_approved'] ? 'approved' : 'pending'; ?>">
                                <?php echo $score['is_approved'] ? '✅ Đã duyệt' : '⏳ Chờ duyệt'; ?>
                            </td>
                            <td>
                                <a href="?view_details=<?php echo $score['score_id']; ?>" class="btn btn-info">📊 Chi tiết</a>
                                <?php if (!$score['is_approved']): ?>
                                    <a href="?approve_id=<?php echo $score['score_id']; ?>" class="btn btn-success" 
                                       onclick="return confirm('Xác nhận duyệt điểm số #<?php echo $score['score_id']; ?>?')">
                                       Duyệt
                                    </a>
                                <?php endif; ?>
                                <a href="?delete_id=<?php echo $score['score_id']; ?>" class="btn btn-danger" 
                                   onclick="return confirm('Cảnh báo: Bạn có chắc chắn muốn xóa điểm số #<?php echo $score['score_id']; ?>?')">
                                   Xóa
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Phân trang dưới -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?php echo $filter_query; ?>">« Đầu</a>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $filter_query; ?>">‹ Trước</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo $filter_query; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $filter_query; ?>">Sau ›</a>
                    <a href="?page=<?php echo $total_pages; ?><?php echo $filter_query; ?>">Cuối »</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Chi tiết điểm số -->
        <?php if (!empty($score_details)): ?>
        <div class="section">
            <h2>🎯 Chi tiết Điểm số #<?php echo $_GET['view_details']; ?></h2>
            <?php foreach ($score_details as $end): ?>
                <div class="end-card">
                    <h4>Range <?php echo $end['range_id']; ?> - End <?php echo $end['end_no']; ?></h4>
                    <div class="arrow-grid">
                        <?php foreach ($end['arrows'] as $arrow): ?>
                            <div class="arrow-item">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="arrow_id" value="<?php echo $arrow['arrow_id']; ?>">
                                    <input type="hidden" name="score_id" value="<?php echo $_GET['view_details']; ?>">
                                    <input type="number" name="arrow_score" value="<?php echo $arrow['score']; ?>" 
                                           min="0" max="10" style="width: 60px; text-align: center;">
                                    <button type="submit" name="update_arrow" class="btn btn-primary btn-sm">✓</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top: 10px; font-weight: bold;">
                        Tổng end: <?php echo $end['end_total']; ?> điểm
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>