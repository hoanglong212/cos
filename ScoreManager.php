<?php
// models/ScoreManager.php
class CompleteScoreManager {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Giữ nguyên tất cả các methods từ file cũ
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
    
    // GIỮ NGUYÊN TẤT CẢ CÁC METHODS KHÁC TỪ FILE CŨ
    // ... (getAllScores, getScoresCount, approveScore, deleteScore, getUsers, getRounds, etc.)
    
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
            $sql = "INSERT INTO round_category (round_name, status) VALUES (?, 'active')";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $round_name);
            $stmt->execute();
            $round_id = $this->conn->insert_id;
            
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
    try {
        // Kiểm tra kết nối database
        if (!$this->conn) {
            throw new Exception("Database connection not available");
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format: " . $email);
        }

        // Kiểm tra username/email đã tồn tại
        $check_sql = "SELECT user_id FROM user_table WHERE username = ? OR email = ?";
        $check_stmt = $this->conn->prepare($check_sql);
        
        if (!$check_stmt) {
            throw new Exception("Prepare failed for check: " . $this->conn->error);
        }
        
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $check_stmt->close();
            throw new Exception("Username or email already exists");
        }
        $check_stmt->close();

        // Lấy thông tin từ archer_category để tạo birthday mặc định
        $category_sql = "SELECT age_and_gender_class FROM archer_category WHERE archer_category_id = ?";
        $category_stmt = $this->conn->prepare($category_sql);
        $birthday = '2000-01-01'; // Mặc định
        
        if ($category_stmt) {
            $category_stmt->bind_param("i", $archer_category_id);
            $category_stmt->execute();
            $category_result = $category_stmt->get_result();
            if ($category_row = $category_result->fetch_assoc()) {
                // Có thể extract tuổi từ age_and_gender_class nếu cần
                // Ví dụ: "Open Male" -> 25 tuổi, "Under 18 Female" -> 17 tuổi, etc.
                $birthday = $this->calculateBirthdayFromCategory($category_row['age_and_gender_class']);
            }
            $category_stmt->close();
        }

        // SQL INSERT - chỉ dùng columns có trong bảng user_table
        $sql = "INSERT INTO user_table (first_name, last_name, username, password, email, gender, birthday, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed for insert: " . $this->conn->error);
        }

        // Bind parameters
        $stmt->bind_param("sssssss", $first_name, $last_name, $username, $hashed_password, $email, $gender, $birthday);
        
        $result = $stmt->execute();
        $new_user_id = $this->conn->insert_id;
        $stmt->close();
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error in addArcher: " . $e->getMessage());
        throw $e;
    }
}

private function calculateBirthdayFromCategory($age_gender_class) {
    // Tính tuổi từ category và trả về birthday
    $age = 25; // Mặc định
    
    if (strpos($age_gender_class, 'Under 14') !== false) $age = 13;
    elseif (strpos($age_gender_class, 'Under 16') !== false) $age = 15;
    elseif (strpos($age_gender_class, 'Under 18') !== false) $age = 17;
    elseif (strpos($age_gender_class, 'Under 21') !== false) $age = 20;
    elseif (strpos($age_gender_class, '50+') !== false) $age = 55;
    elseif (strpos($age_gender_class, '60+') !== false) $age = 65;
    elseif (strpos($age_gender_class, '70+') !== false) $age = 75;
    
    $birth_year = date('Y') - $age;
    return $birth_year . '-01-01';
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
        $sql = "SELECT e.*, a.arrow_id, a.score as arrow_score, a.arrow_order
                FROM ends e
                LEFT JOIN arrows a ON e.end_id = a.end_id
                WHERE e.score_id = ?
                ORDER BY e.range_id, e.end_no, a.arrow_order";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $score_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
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
?>