<?php
// check_tables.php
session_start();
require_once 'connect.php';

class TableChecker {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function checkAllTables() {
        $required_tables = [
            'scores', 'user_table', 'round_category', 'archer_category', 
            'bow_category', 'competitions', 'range_category', 
            'round_category_details', 'equivalent_round', 'equivalent_rounds',
            'ends', 'arrows'
        ];
        
        $results = [];
        foreach ($required_tables as $table) {
            $check = $this->conn->query("SHOW TABLES LIKE '$table'");
            $exists = $check->num_rows > 0;
            
            // Lấy thông tin chi tiết cấu trúc bảng nếu tồn tại
            $structure = [];
            if ($exists) {
                $columns = $this->conn->query("DESCRIBE $table");
                while ($column = $columns->fetch_assoc()) {
                    $structure[] = [
                        'field' => $column['Field'],
                        'type' => $column['Type'],
                        'null' => $column['Null'],
                        'key' => $column['Key'],
                        'default' => $column['Default']
                    ];
                }
            }
            
            $results[$table] = [
                'exists' => $exists,
                'structure' => $structure
            ];
        }
        
        return $results;
    }
}

// Khởi tạo checker
$checker = new TableChecker($conn);
$table_results = $checker->checkAllTables();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiểm tra Bảng Database - Chi Tiết</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 20px; text-align: center; }
        .table-item { margin: 20px 0; border: 1px solid #ddd; border-radius: 5px; overflow: hidden; }
        .table-header { padding: 15px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; }
        .exists { background: #d4edda; color: #155724; }
        .missing { background: #f8d7da; color: #721c24; }
        .status { font-weight: bold; }
        .table-structure { padding: 15px; background: #f8f9fa; display: none; }
        .structure-table { width: 100%; border-collapse: collapse; }
        .structure-table th, .structure-table td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
        .structure-table th { background: #e9ecef; }
        .toggle-btn { background: none; border: none; font-size: 16px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 KIỂM TRA BẢNG DATABASE - CHI TIẾT</h1>
        
        <?php foreach ($table_results as $table => $info): ?>
            <div class="table-item">
                <div class="table-header <?php echo $info['exists'] ? 'exists' : 'missing'; ?>">
                    <span><strong><?php echo $table; ?></strong></span>
                    <span class="status">
                        <?php echo $info['exists'] ? '✅ CÓ' : '❌ THIẾU'; ?>
                        <?php if ($info['exists']): ?>
                            <button class="toggle-btn" onclick="toggleStructure('<?php echo $table; ?>')">📋</button>
                        <?php endif; ?>
                    </span>
                </div>
                
                <?php if ($info['exists'] && !empty($info['structure'])): ?>
                <div id="structure-<?php echo $table; ?>" class="table-structure">
                    <h4>Cấu trúc bảng:</h4>
                    <table class="structure-table">
                        <thead>
                            <tr>
                                <th>Tên cột</th>
                                <th>Kiểu dữ liệu</th>
                                <th>Cho phép NULL</th>
                                <th>Khóa</th>
                                <th>Mặc định</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($info['structure'] as $column): ?>
                                <tr>
                                    <td><strong><?php echo $column['field']; ?></strong></td>
                                    <td><?php echo $column['type']; ?></td>
                                    <td><?php echo $column['null']; ?></td>
                                    <td><?php echo $column['key']; ?></td>
                                    <td><?php echo $column['default']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 5px;">
            <h3>📊 TỔNG KẾT:</h3>
            <?php
            $existing_tables = array_filter($table_results, function($info) { return $info['exists']; });
            $missing_tables = array_filter($table_results, function($info) { return !$info['exists']; });
            ?>
            <p><strong>Số bảng có sẵn:</strong> <?php echo count($existing_tables); ?></p>
            <p><strong>Số bảng thiếu:</strong> <?php echo count($missing_tables); ?></p>
            
            <?php if (!empty($missing_tables)): ?>
            <p><strong>Bảng thiếu:</strong> <?php echo implode(', ', array_keys($missing_tables)); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleStructure(tableName) {
            const element = document.getElementById('structure-' + tableName);
            if (element.style.display === 'block') {
                element.style.display = 'none';
            } else {
                element.style.display = 'block';
            }
        }
    </script>
</body>
</html>