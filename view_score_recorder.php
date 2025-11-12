<?php
// enhanced_score_recorder.php
session_start();
require_once 'connect.php';

// Chuyển hướng nếu có tham số filter cũ
if (isset($_GET['date_from']) || isset($_GET['date_to']) || isset($_GET['is_practice'])) {
    $new_url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: " . $new_url);
    exit();
}

class CompleteScoreManager {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function getDefaultStats() {
        return [
            'total_scores' => 0,
            'general_stats' => [
                'avg_score' => 0,
                'max_score' => 0,
                'min_score' => 0
            ],
            'approval_stats' => [
                ['is_approved' => 0, 'count' => 0],
                ['is_approved' => 1, 'count' => 0]
            ],
            'top_scores' => [],
            'round_stats' => []
        ];
    }
    
    public function getStats() {
        try {
            // Query đơn giản - chỉ lấy thống kê cơ bản từ bảng scores
            $sql = "SELECT 
                        COUNT(*) as total_scores,
                        AVG(total_score) as avg_score,
                        MAX(total_score) as max_score,
                        MIN(total_score) as min_score,
                        SUM(is_approved = 0) as pending_count,
                        SUM(is_approved = 1) as approved_count
                    FROM scores 
                    WHERE total_score IS NOT NULL";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return $this->getDefaultStats();
            }
            
            $stmt->execute();
            $general_stats = $stmt->get_result()->fetch_assoc();
            
            $stats = [];
            if ($general_stats) {
                $stats['total_scores'] = $general_stats['total_scores'];
                $stats['general_stats'] = [
                    'avg_score' => $general_stats['avg_score'] ? round($general_stats['avg_score'], 1) : 0,
                    'max_score' => $general_stats['max_score'] ?: 0,
                    'min_score' => $general_stats['min_score'] ?: 0
                ];
                $stats['approval_stats'] = [
                    ['is_approved' => 0, 'count' => $general_stats['pending_count']],
                    ['is_approved' => 1, 'count' => $general_stats['approved_count']]
                ];
            }
            
            // Top scores với JOIN đúng
            $sql = "SELECT s.score_id, s.total_score, u.first_name, u.last_name, rc.round_name
                    FROM scores s
                    LEFT JOIN user_table u ON s.user_id = u.user_id
                    LEFT JOIN rounds r ON s.round_id = r.round_id
                    LEFT JOIN round_category rc ON r.round_category_id = rc.round_category_id
                    WHERE s.total_score IS NOT NULL
                    ORDER BY s.total_score DESC
                    LIMIT 5";
            
            $stmt = $this->conn->prepare($sql);
            if ($stmt) {
                $stmt->execute();
                $stats['top_scores'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            }
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Lỗi thống kê: " . $e->getMessage());
            return $this->getDefaultStats();
        }
    }
    
    public function getAllScores($limit = 50, $offset = 0, $filters = []) {
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
                LEFT JOIN rounds r ON s.round_id = r.round_id
                LEFT JOIN round_category rc ON r.round_category_id = rc.round_category_id
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
        
        $sql .= " ORDER BY s.score_id DESC LIMIT ? OFFSET ?";
        $types .= "ii";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getScoresCount($filters = []) {
        $sql = "SELECT COUNT(*) as total 
                FROM scores s
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
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'] ?? 0;
    }
    
    public function approveScore($score_id) {
        $sql = "UPDATE scores SET is_approved = 1 WHERE score_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("i", $score_id);
        return $stmt->execute();
    }
    
    public function deleteScore($score_id) {
        try {
            error_log("DEBUG: Deleting score_id: " . $score_id);
            
            // CHỈ XÓA SCORE - vì không có direct link đến ends/arrows
            $sql = "DELETE FROM scores WHERE score_id = ?";
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                error_log("ERROR: Prepare failed: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("i", $score_id);
            $result = $stmt->execute();
            
            if ($result) {
                error_log("SUCCESS: Score deleted: " . $score_id);
                return true;
            } else {
                error_log("ERROR: Execute failed: " . $stmt->error);
                return false;
            }
            
        } catch (Exception $e) {
            error_log("EXCEPTION in deleteScore: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUsers($limit = 100) {
        $sql = "SELECT user_id, username, first_name, last_name, email, gender, is_active 
                FROM user_table 
                ORDER BY first_name, last_name 
                LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    public function getRounds() {
        $sql = "SELECT r.round_id, rc.round_name, r.date_recorded, r.location 
                FROM rounds r
                LEFT JOIN round_category rc ON r.round_category_id = rc.round_category_id
                ORDER BY r.date_recorded DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getAllRoundsWithRanges() {
        $sql = "SELECT rc.*, 
                       GROUP_CONCAT(CONCAT(rng.name, ' (', rng.distance, 'm)') SEPARATOR ', ') as ranges
                FROM round_category rc
                LEFT JOIN round_category_details rrd ON rc.round_category_id = rrd.round_category_id
                LEFT JOIN range_category rng ON rrd.range_category_id = rng.range_category_id
                GROUP BY rc.round_category_id
                ORDER BY rc.round_name";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    public function addRound($round_name, $range_ids = []) {
        $this->conn->begin_transaction();
        try {
            // Thêm round
            $sql = "INSERT INTO round_category (round_name, status) VALUES (?, 'active')";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $round_name);
            $stmt->execute();
            $round_id = $this->conn->insert_id;
            
            // Thêm quan hệ với ranges
            foreach ($range_ids as $range_id) {
                $sql = "INSERT INTO round_category_details (round_category_id, range_category_id) VALUES (?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("ii", $round_id, $range_id);
                $stmt->execute();
            }
            
            $this->conn->commit();
            return $round_id;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
    
    public function getAllRanges() {
        $sql = "SELECT * FROM range_category WHERE status = 'active' ORDER BY distance, name";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    public function addRange($name, $distance, $face_size, $number_of_ends) {
        $sql = "INSERT INTO range_category (name, distance, face_size, number_of_ends, status) 
                VALUES (?, ?, ?, ?, 'active')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssi", $name, $distance, $face_size, $number_of_ends);
        return $stmt->execute();
    }
    
    public function addArcher($first_name, $last_name, $username, $password, $email, $gender, $archer_category_id) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO user_table (first_name, last_name, username, password, email, gender, archer_category_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssssi", $first_name, $last_name, $username, $hashed_password, $email, $gender, $archer_category_id);
        return $stmt->execute();
    }
    
    public function getArcherCategories() {
        $sql = "SELECT ac.*, bc.category_name as bow_name 
                FROM archer_category ac
                LEFT JOIN bow_category bc ON ac.bow_category_id = bc.bow_category_id
                WHERE ac.is_valid = 1
                ORDER BY ac.age_and_gender_class";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getBowCategories() {
        $sql = "SELECT * FROM bow_category ORDER BY category_name";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    public function addCompetition($competition_name, $start_date, $end_date, $championship_id = null) {
        $competition_id = 'COMP_' . uniqid() . '_' . rand(1000, 9999);
        
        $sql = "INSERT INTO competitions (competition_id, competition_name, championship_id, start_date, end_date) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("sssss", $competition_id, $competition_name, $championship_id, $start_date, $end_date);
        return $stmt->execute();
    }
    
    public function getCompetitions($limit = 50) {
        $sql = "SELECT * FROM competitions ORDER BY start_date DESC LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    public function getScoreDetails($score_id) {
        // Lấy tất cả ends và arrows trong 1 query
        $sql = "SELECT e.*, a.arrow_id, a.score as arrow_score, a.arrow_order
                FROM ends e
                LEFT JOIN arrows a ON e.end_id = a.end_id
                WHERE e.score_id = ?
                ORDER BY e.range_id, e.end_no, a.arrow_order";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $score_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Nhóm dữ liệu
        $ends = [];
        foreach ($result as $row) {
            $end_id = $row['end_id'];
            if (!isset($ends[$end_id])) {
                $ends[$end_id] = [
                    'end_id' => $row['end_id'],
                    'range_id' => $row['range_id'],
                    'end_no' => $row['end_no'],
                    'arrows' => []
                ];
            }
            
            if ($row['arrow_id']) {
                $ends[$end_id]['arrows'][] = [
                    'arrow_id' => $row['arrow_id'],
                    'score' => $row['arrow_score'],
                    'arrow_order' => $row['arrow_order']
                ];
            }
        }
        
        // Tính tổng cho mỗi end
        foreach ($ends as &$end) {
            $end_total = 0;
            foreach ($end['arrows'] as $arrow) {
                $end_total += $arrow['score'];
            }
            $end['end_total'] = $end_total;
        }
        
        return array_values($ends);
    }
    
    public function updateArrowScore($arrow_id, $score) {
        $sql = "UPDATE arrows SET score = ? WHERE arrow_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $score, $arrow_id);
        return $stmt->execute();
    }
    
    public function recalculateTotalScore($score_id) {
        $sql = "SELECT SUM(a.score) as total 
                FROM arrows a 
                INNER JOIN ends e ON a.end_id = e.end_id 
                WHERE e.score_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $score_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $total_score = $result['total'] ?? 0;
        
        $sql = "UPDATE scores SET total_score = ? WHERE score_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $total_score, $score_id);
        return $stmt->execute();
    }
    
    public function getEquivalentRounds() {
        $sql = "SELECT er.equivalent_round_id, er.round_category_id, er.equivalent_to_round_category_id, 
                       er.gender, er.age_group, er.bow_category_id, er.description,
                       rc1.round_name as base_round, 
                       rc2.round_name as equivalent_round,
                       bc.category_name as bow_name
                FROM equivalent_round er
                LEFT JOIN round_category rc1 ON er.round_category_id = rc1.round_category_id
                LEFT JOIN round_category rc2 ON er.equivalent_to_round_category_id = rc2.round_category_id
                LEFT JOIN bow_category bc ON er.bow_category_id = bc.bow_category_id
                ORDER BY er.equivalent_round_id DESC";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    public function addEquivalentRound($base_round_id, $equivalent_round_id, $gender, $age_group, $bow_category_id, $description = '') {
        $sql = "INSERT INTO equivalent_round (round_category_id, equivalent_to_round_category_id, gender, age_group, bow_category_id, description) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("iissis", $base_round_id, $equivalent_round_id, $gender, $age_group, $bow_category_id, $description);
        return $stmt->execute();
    }
}

// Khởi tạo manager
$scoreManager = new CompleteScoreManager($conn);

// Xác định tab hiện tại
$current_tab = $_GET['tab'] ?? 'stats';

// Phân trang
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Xử lý tất cả actions
$message = "";
$action_success = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ADD ROUND
    if (isset($_POST['add_round'])) {
        try {
            $round_id = $scoreManager->addRound($_POST['round_name'], $_POST['range_ids'] ?? []);
            $message = "✅ Thêm round thành công!";
        } catch (Exception $e) {
            $message = "❌ Lỗi khi thêm round: " . $e->getMessage();
            $action_success = false;
        }
    }
    
    // ADD RANGE
    elseif (isset($_POST['add_range'])) {
        if ($scoreManager->addRange($_POST['range_name'], $_POST['distance'], $_POST['face_size'], $_POST['number_of_ends'])) {
            $message = "✅ Thêm range thành công!";
        } else {
            $message = "❌ Lỗi khi thêm range!";
            $action_success = false;
        }
    }
    
    // ADD ARCHER
    elseif (isset($_POST['add_archer'])) {
        if ($scoreManager->addArcher($_POST['first_name'], $_POST['last_name'], $_POST['username'], 
                                   $_POST['password'], $_POST['email'], $_POST['gender'], $_POST['archer_category_id'])) {
            $message = "✅ Thêm cung thủ thành công!";
        } else {
            $message = "❌ Lỗi khi thêm cung thủ!";
            $action_success = false;
        }
    }
    
    // ADD COMPETITION
    elseif (isset($_POST['add_competition'])) {
        if ($scoreManager->addCompetition($_POST['competition_name'], $_POST['start_date'], $_POST['end_date'], $_POST['championship_id'] ?? null)) {
            $message = "✅ Thêm competition thành công!";
        } else {
            $message = "❌ Lỗi khi thêm competition!";
            $action_success = false;
        }
    }
    
    // UPDATE ARROW SCORE
    elseif (isset($_POST['update_arrow'])) {
        if ($scoreManager->updateArrowScore($_POST['arrow_id'], $_POST['arrow_score'])) {
            $scoreManager->recalculateTotalScore($_POST['score_id']);
            $message = "✅ Cập nhật điểm arrow thành công!";
        } else {
            $message = "❌ Lỗi khi cập nhật điểm!";
            $action_success = false;
        }
    }
    
    // ADD EQUIVALENT ROUND
    elseif (isset($_POST['add_equivalent_round'])) {
        if ($scoreManager->addEquivalentRound($_POST['base_round_id'], $_POST['equivalent_round_id'], 
                                           $_POST['gender'], $_POST['age_group'], $_POST['bow_category_id'], $_POST['description'])) {
            $message = "✅ Thêm equivalent round thành công!";
        } else {
            $message = "❌ Lỗi khi thêm equivalent round!";
            $action_success = false;
        }
    }
}

// Xử lý GET actions
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
    error_log("USER ACTION: Attempting to delete score_id: " . $score_id);
    
    if ($scoreManager->deleteScore($score_id)) {
        $message = "✅ Xóa điểm thành công!";
    } else {
        $message = "❌ Lỗi khi xóa điểm!";
        error_log("DELETE FAILED for score_id: " . $score_id);
    }
}

// Lấy dữ liệu
$filters = [
    'is_approved' => $_GET['is_approved'] ?? '',
    'user_id' => $_GET['user_id'] ?? '',
    'round_id' => $_GET['round_id'] ?? ''
];

$scores = $scoreManager->getAllScores($limit, $offset, $filters);
$total_scores = $scoreManager->getScoresCount($filters);
$total_pages = ceil($total_scores / $limit);

$stats = $scoreManager->getStats();
$users = $scoreManager->getUsers(50);
$rounds = $scoreManager->getRounds();
$rounds_with_ranges = $scoreManager->getAllRoundsWithRanges();
$ranges = $scoreManager->getAllRanges();
$archer_categories = $scoreManager->getArcherCategories();
$competitions = $scoreManager->getCompetitions(50);
$equivalent_rounds = $scoreManager->getEquivalentRounds();
$bow_categories = $scoreManager->getBowCategories();

// Lấy chi tiết score nếu có yêu cầu
$score_details = [];
if (isset($_GET['view_details'])) {
    $score_details = $scoreManager->getScoreDetails($_GET['view_details']);
}

// Tạo query string cho phân trang
$filter_query = '';
if ($filters['is_approved'] !== '') {
    $filter_query .= '&is_approved=' . $filters['is_approved'];
}
if ($filters['user_id'] !== '') {
    $filter_query .= '&user_id=' . $filters['user_id'];
}
if ($filters['round_id'] !== '') {
    $filter_query .= '&round_id=' . $filters['round_id'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Điểm số - Archery System</title>
    <link rel="stylesheet" href="css/score_recorder.css">
</head>
<body>
    <div class="container">
        <h1>🎯 Quản lý Điểm số Bắn cung - Recorder System</h1>
        
        <!-- Navigation -->
        <div class="nav-buttons">
            <a href="add_score_step1.php" class="nav-btn">➕ Nhập Điểm Mới</a>
            <a href="homepage.html" class="nav-btn">🏠 Về Trang Chủ</a>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $action_success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Tab System -->
        <div class="tab-container">
            <div class="tab-buttons">
                <a href="?tab=stats" class="tab-btn <?php echo $current_tab == 'stats' ? 'active' : ''; ?>">📊 Thống kê</a>
                <a href="?tab=scores" class="tab-btn <?php echo $current_tab == 'scores' ? 'active' : ''; ?>">📋 Điểm số</a>
                <a href="?tab=rounds" class="tab-btn <?php echo $current_tab == 'rounds' ? 'active' : ''; ?>">🎯 Rounds</a>
                <a href="?tab=archers" class="tab-btn <?php echo $current_tab == 'archers' ? 'active' : ''; ?>">👥 Cung thủ</a>
                <a href="?tab=competitions" class="tab-btn <?php echo $current_tab == 'competitions' ? 'active' : ''; ?>">🏆 Competitions</a>
                <a href="?tab=equivalents" class="tab-btn <?php echo $current_tab == 'equivalents' ? 'active' : ''; ?>">🔄 Equivalent Rounds</a>
            </div>

            <!-- Tab 1: Thống kê -->
            <div id="tab-stats" class="tab-content <?php echo $current_tab == 'stats' ? 'active' : ''; ?>">
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
            </div>

            <!-- Tab 2: Điểm số -->
            <div id="tab-scores" class="tab-content <?php echo $current_tab == 'scores' ? 'active' : ''; ?>">
                <!-- Bộ lọc -->
                <div class="section">
                    <h2>🔍 Lọc dữ liệu</h2>
                    <form method="GET" class="filter-form">
                        <input type="hidden" name="tab" value="scores">
                        <input type="hidden" name="page" value="1">
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
                                <a href="?tab=scores" class="btn">🔄 Xóa lọc</a>
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
                            <a href="?tab=scores&page=1<?php echo $filter_query; ?>">« Đầu</a>
                            <a href="?tab=scores&page=<?php echo $page - 1; ?><?php echo $filter_query; ?>">‹ Trước</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?tab=scores&page=<?php echo $i; ?><?php echo $filter_query; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?tab=scores&page=<?php echo $page + 1; ?><?php echo $filter_query; ?>">Sau ›</a>
                            <a href="?tab=scores&page=<?php echo $total_pages; ?><?php echo $filter_query; ?>">Cuối »</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

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
                                        <a href="?tab=scores&view_details=<?php echo $score['score_id']; ?>" class="btn btn-info">📊 Chi tiết</a>
                                        <?php if (!$score['is_approved']): ?>
                                            <a href="?tab=scores&approve_id=<?php echo $score['score_id']; ?>" class="btn btn-success" 
                                               onclick="return confirm('Xác nhận duyệt điểm số #<?php echo $score['score_id']; ?>?')">
                                               Duyệt
                                            </a>
                                        <?php endif; ?>
                                        <a href="?tab=scores&delete_id=<?php echo $score['score_id']; ?>" class="btn btn-danger" 
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
                            <a href="?tab=scores&page=1<?php echo $filter_query; ?>">« Đầu</a>
                            <a href="?tab=scores&page=<?php echo $page - 1; ?><?php echo $filter_query; ?>">‹ Trước</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?tab=scores&page=<?php echo $i; ?><?php echo $filter_query; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?tab=scores&page=<?php echo $page + 1; ?><?php echo $filter_query; ?>">Sau ›</a>
                            <a href="?tab=scores&page=<?php echo $total_pages; ?><?php echo $filter_query; ?>">Cuối »</a>
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

            <!-- Tab 3: Quản lý Rounds -->
            <div id="tab-rounds" class="tab-content <?php echo $current_tab == 'rounds' ? 'active' : ''; ?>">
                <div class="section">
                    <h2>🎯 Quản lý Rounds & Ranges</h2>
                    
                    <!-- Form thêm Range -->
                    <div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                        <h3>➕ Thêm Range mới</h3>
                        <form method="POST">
                            <input type="hidden" name="tab" value="rounds">
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
                            <input type="hidden" name="tab" value="rounds">
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

            <!-- Tab 4: Quản lý Cung thủ -->
            <div id="tab-archers" class="tab-content <?php echo $current_tab == 'archers' ? 'active' : ''; ?>">
                <div class="section">
                    <h2>👥 Quản lý Cung thủ</h2>
                    
                    <!-- Form thêm cung thủ -->
                    <div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                        <h3>➕ Thêm Cung thủ mới</h3>
                        <form method="POST">
                            <input type="hidden" name="tab" value="archers">
                            <div class="form-row">
                                <div class="form-column">
                                    <label>Họ:</label>
                                    <input type="text" name="first_name" required>
                                </div>
                                <div class="form-column">
                                    <label>Tên:</label>
                                    <input type="text" name="last_name" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-column">
                                    <label>Username:</label>
                                    <input type="text" name="username" required>
                                </div>
                                <div class="form-column">
                                    <label>Password:</label>
                                    <input type="password" name="password" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-column">
                                    <label>Email:</label>
                                    <input type="email" name="email" required>
                                </div>
                                <div class="form-column">
                                    <label>Giới tính:</label>
                                    <select name="gender" required>
                                        <option value="Male">Nam</option>
                                        <option value="Female">Nữ</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Category:</label>
                                <select name="archer_category_id" required>
                                    <?php foreach ($archer_categories as $category): ?>
                                        <option value="<?php echo $category['archer_category_id']; ?>">
                                            <?php echo htmlspecialchars($category['age_and_gender_class'] . ' - ' . $category['bow_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="add_archer" class="btn btn-success">➕ Thêm Cung thủ</button>
                        </form>
                    </div>

                    <!-- Danh sách cung thủ -->
                    <h3>📋 Danh sách Cung thủ</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Họ tên</th>
                                <th>Email</th>
                                <th>Giới tính</th>
                                <th>Username</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['user_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></td>
                                    <td><?php echo ($user['is_active'] ?? 1) ? '✅ Active' : '❌ Inactive'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 5: Quản lý Competitions -->
            <div id="tab-competitions" class="tab-content <?php echo $current_tab == 'competitions' ? 'active' : ''; ?>">
                <div class="section">
                    <h2>🏆 Quản lý Competitions</h2>
                    
                    <!-- Form thêm competition -->
                    <div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                        <h3>➕ Thêm Competition mới</h3>
                        <form method="POST">
                            <input type="hidden" name="tab" value="competitions">
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

            <!-- Tab 6: Equivalent Rounds -->
            <div id="tab-equivalents" class="tab-content <?php echo $current_tab == 'equivalents' ? 'active' : ''; ?>">
                <div class="section">
                    <h2>🔄 Quản lý Equivalent Rounds</h2>
                    
                    <!-- Form thêm equivalent round -->
                    <div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                        <h3>➕ Thêm Equivalent Round</h3>
                        <form method="POST">
                            <input type="hidden" name="tab" value="equivalents">
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
        </div>
    </div>

    <script>
        // Xử lý loading state
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    this.classList.add('loading');
                });
            });
        });
    </script>
</body>
</html>