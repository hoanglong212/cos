<?php
include 'connect.php';

header('Content-Type: application/json');

if ($_POST) {
    $age_gender_class = trim($_POST['age_gender_class']);
    $bow_category_id = intval($_POST['bow_category_id']);
    
    // Debug: ghi log để kiểm tra
    error_log("Tìm archer_category: age_gender_class='$age_gender_class', bow_category_id=$bow_category_id");
    
    // Kiểm tra xem có tồn tại không
    $sql = "SELECT ac.archer_category_id, ac.category_name 
            FROM archer_category ac 
            WHERE ac.age_and_gender_class = ? AND ac.bow_category_id = ? AND ac.is_valid = 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $age_gender_class, $bow_category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'archer_category_id' => $row['archer_category_id'],
            'category_name' => $row['category_name']
        ]);
    } else {
        // Debug chi tiết hơn
        $check_sql = "SELECT age_and_gender_class, bow_category_id, category_name 
                     FROM archer_category 
                     WHERE is_valid = 1 
                     ORDER BY age_and_gender_class, bow_category_id";
        $check_result = $conn->query($check_sql);
        $available_categories = [];
        while ($cat = $check_result->fetch_assoc()) {
            $available_categories[] = $cat;
        }
        
        // Kiểm tra xem age_gender_class có tồn tại không (bất kỳ bow_category_id)
        $check_age_sql = "SELECT DISTINCT age_and_gender_class FROM archer_category WHERE is_valid = 1";
        $age_result = $conn->query($check_age_sql);
        $available_age_classes = [];
        while ($age_class = $age_result->fetch_assoc()) {
            $available_age_classes[] = $age_class['age_and_gender_class'];
        }
        
        error_log("Available age classes: " . implode(', ', $available_age_classes));
        error_log("Requested: '$age_gender_class' with bow_category_id=$bow_category_id");
        
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy archer category phù hợp',
            'debug' => [
                'requested' => [
                    'age_gender_class' => $age_gender_class, 
                    'bow_category_id' => $bow_category_id
                ],
                'available_age_classes' => $available_age_classes,
                'available_categories' => $available_categories
            ]
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No POST data received'
    ]);
}
?>