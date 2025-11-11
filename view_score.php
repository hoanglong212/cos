<?php
// view_scores.php
session_start();
require_once 'connect.php';

class ScoreManager {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Lấy tất cả điểm số
    public function getAllScores($limit = 100, $filters = []) {
        $sql = "SELECT 
                    s.score_id, 
                    s.total_score, 
                    s.is_approved, 
                    s.user_id, 
                    s.competition_id,
                    s.round_id,
                    s.archer_category_id,
                    u.first_name, 
                    u.last_name,
                    rc.round_name, 
                    ac.age_and_gender_class as archer_category_name,
                    ac.bow_category_id,
                    bc.category_name as bow_name
                FROM scores s
                LEFT JOIN user_table u ON s.user_id = u.user_id
                LEFT JOIN round_category rc ON s.round_id = rc.round_category_id
                LEFT JOIN archer_category ac ON s.archer_category_id = ac.archer_category_id
                LEFT JOIN bow_category bc ON ac.bow_category_id = bc.bow_category_id
                WHERE 1=1";
        
        $types = "";
        $params = [];
        
        if (isset($filters['is_approved']) && $filters['is_approved'] !== '') {
            $sql .= " AND s.is_approved = ?";
            $types .= "i";
            $params[] = $filters['is_approved'];
        }
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND s.user_id = ?";
            $types .= "i";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['round_id'])) {
            $sql .= " AND s.round_id = ?";
            $types .= "i";
            $params[] = $filters['round_id'];
        }
        
        $sql .= " ORDER BY s.score_id DESC LIMIT ?";
        $types .= "i";
        $params[] = $limit;
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            die("Lỗi SQL: " . $this->conn->error . " - Query: " . $sql);
        }
        
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Lấy thống kê
    public function getStats() {
        $stats = [];
        
        try {
            // Tổng số điểm số
            $sql = "SELECT COUNT(*) as total_scores FROM scores";
            $result = $this->conn->query($sql);
            if ($result) {
                $stats['total_scores'] = $result->fetch_assoc()['total_scores'];
            }
            
            // Điểm số theo trạng thái duyệt
            $sql = "SELECT is_approved, COUNT(*) as count FROM scores GROUP BY is_approved";
            $result = $this->conn->query($sql);
            if ($result) {
                $stats['approval_stats'] = $result->fetch_all(MYSQLI_ASSOC);
            }
            
            // Thống kê tổng quan
            $sql = "SELECT 
                        COUNT(*) as total_scores,
                        AVG(total_score) as avg_score,
                        MAX(total_score) as max_score,
                        MIN(total_score) as min_score
                    FROM scores 
                    WHERE total_score IS NOT NULL";
            $result = $this->conn->query($sql);
            if ($result) {
                $stats['general_stats'] = $result->fetch_assoc();
            }
            
            // Top điểm cao nhất
            $sql = "SELECT s.score_id, s.total_score, u.first_name, u.last_name, rc.round_name
                    FROM scores s
                    LEFT JOIN user_table u ON s.user_id = u.user_id
                    LEFT JOIN round_category rc ON s.round_id = rc.round_category_id
                    ORDER BY s.total_score DESC
                    LIMIT 5";
            $result = $this->conn->query($sql);
            if ($result) {
                $stats['top_scores'] = $result->fetch_all(MYSQLI_ASSOC);
            }
            
            // Số điểm theo round - SỬA LẠI PHẦN NÀY
            $sql = "SELECT rc.round_name, COUNT(*) as count 
                    FROM scores s
                    LEFT JOIN round_category rc ON s.round_id = rc.round_category_id
                    WHERE rc.round_name IS NOT NULL
                    GROUP BY s.round_id, rc.round_name
                    ORDER BY count DESC
                    LIMIT 5";
            $result = $this->conn->query($sql);
            if ($result) {
                $stats['round_stats'] = $result->fetch_all(MYSQLI_ASSOC);
            }
            
        } catch (Exception $e) {
            error_log("Lỗi thống kê: " . $e->getMessage());
        }
        
        return $stats;
    }
    
    // Duyệt điểm số
    public function approveScore($score_id) {
        $sql = "UPDATE scores SET is_approved = 1 WHERE score_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            die("Lỗi SQL: " . $this->conn->error);
        }
        $stmt->bind_param("i", $score_id);
        return $stmt->execute();
    }
    
    // Xóa điểm số
    public function deleteScore($score_id) {
        $sql = "DELETE FROM scores WHERE score_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            die("Lỗi SQL: " . $this->conn->error);
        }
        $stmt->bind_param("i", $score_id);
        return $stmt->execute();
    }
    
    // Lấy danh sách users
    public function getUsers() {
        $sql = "SELECT user_id, first_name, last_name FROM user_table ORDER BY first_name, last_name";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Lấy danh sách rounds
    public function getRounds() {
        $sql = "SELECT round_category_id, round_name FROM round_category ORDER BY round_name";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Khởi tạo manager
$scoreManager = new ScoreManager($conn);

// Xử lý actions
$message = "";
if (isset($_GET['approve_id'])) {
    if ($scoreManager->approveScore($_GET['approve_id'])) {
        $message = "✅ Duyệt điểm thành công!";
    } else {
        $message = "❌ Lỗi khi duyệt điểm!";
    }
    header("Location: view_scores.php?message=" . urlencode($message));
    exit();
}

if (isset($_GET['delete_id'])) {
    if ($scoreManager->deleteScore($_GET['delete_id'])) {
        $message = "✅ Xóa điểm thành công!";
    } else {
        $message = "❌ Lỗi khi xóa điểm!";
    }
    header("Location: view_scores.php?message=" . urlencode($message));
    exit();
}

// Lấy message từ URL
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Lấy filters
$filters = [
    'is_approved' => $_GET['is_approved'] ?? '',
    'user_id' => $_GET['user_id'] ?? '',
    'round_id' => $_GET['round_id'] ?? ''
];

// Lấy dữ liệu
$scores = $scoreManager->getAllScores(100, $filters);
$stats = $scoreManager->getStats();
$users = $scoreManager->getUsers();
$rounds = $scoreManager->getRounds();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Điểm số - Archery System</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; line-height: 1.6; background: #f4f4f4; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2, h3 { color: #333; margin-bottom: 15px; }
        .message { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: bold; }
        tr:hover { background-color: #f5f5f5; }
        .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 0.9em; margin: 2px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 5px; text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; color: #007bff; }
        .stat-label { color: #666; font-size: 0.9em; }
        .filter-form { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .filter-row { display: flex; gap: 15px; align-items: end; flex-wrap: wrap; }
        .filter-group { flex: 1; min-width: 150px; }
        .score-high { color: #28a745; font-weight: bold; }
        .score-medium { color: #ffc107; }
        .score-low { color: #dc3545; }
        .approved { color: #28a745; }
        .pending { color: #ffc107; }
        .nav-buttons { margin: 20px 0; text-align: center; }
        .nav-btn { display: inline-block; padding: 12px 24px; margin: 0 10px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .nav-btn:hover { background: #0056b3; }
        .stats-detail { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-detail-card { background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Quản lý Điểm số Bắn cung</h1>
        
        <!-- Navigation -->
        <div class="nav-buttons">
            <a href="add_score_step1.php" class="nav-btn">➕ Nhập Điểm Mới</a>
            <a href="index.php" class="nav-btn">🏠 Về Trang Chủ</a>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Thống kê -->
        <div class="section">
            <h2>📊 Thống kê tổng quan</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_scores'] ?? 0; ?></div>
                    <div class="stat-label">Tổng số điểm</div>
                </div>
                <?php foreach (($stats['approval_stats'] ?? []) as $approval): ?>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $approval['count']; ?></div>
                        <div class="stat-label"><?php echo $approval['is_approved'] ? 'Đã duyệt' : 'Chờ duyệt'; ?></div>
                    </div>
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
            
            <!-- Thống kê chi tiết -->
            <?php if (!empty($stats['top_scores']) || !empty($stats['round_stats'])): ?>
            <div class="stats-detail">
                <?php if (!empty($stats['top_scores'])): ?>
                <div class="stat-detail-card">
                    <h4>🏆 Top 5 Điểm cao nhất</h4>
                    <?php foreach ($stats['top_scores'] as $index => $top): ?>
                        <div style="margin: 8px 0; padding: 5px; background: white; border-radius: 4px;">
                            <strong><?php echo $index + 1; ?>.</strong> 
                            <?php echo htmlspecialchars($top['first_name'] . ' ' . $top['last_name']); ?> - 
                            <span class="score-high"><?php echo $top['total_score']; ?> điểm</span>
                            <br><small><?php echo htmlspecialchars($top['round_name']); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($stats['round_stats'])): ?>
                <div class="stat-detail-card">
                    <h4>📈 Round phổ biến</h4>
                    <?php foreach ($stats['round_stats'] as $round): ?>
                        <div style="margin: 8px 0; padding: 5px; background: white; border-radius: 4px;">
                            <strong><?php echo htmlspecialchars($round['round_name']); ?></strong> - 
                            <?php echo $round['count']; ?> lượt
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

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
                                <option value="<?php echo $round['round_category_id']; ?>" <?php echo $filters['round_id'] == $round['round_category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($round['round_name']); ?>
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
                        <a href="view_scores.php" class="btn">🔄 Xóa lọc</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Danh sách điểm số -->
        <div class="section">
            <h2>📋 Danh sách Điểm số (<?php echo count($scores); ?> kết quả)</h2>
            <?php if (empty($scores)): ?>
                <div style="text-align: center; color: #666; padding: 40px;">
                    <h3>🎯 Chưa có điểm số nào được ghi nhận</h3>
                    <p>Hãy bắt đầu bằng cách nhập điểm mới!</p>
                    <a href="add_score_step1.php" class="nav-btn" style="margin-top: 15px;">➕ Nhập Điểm Ngay</a>
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
            <?php endif; ?>
        </div>
    </div>
</body>
</html>