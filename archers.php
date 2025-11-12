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

// Xử lý form thêm cung thủ
$message = "";
$action_success = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_archer'])) {
    try {
        if ($scoreManager->addArcher(
            $_POST['first_name'], 
            $_POST['last_name'], 
            $_POST['username'], 
            $_POST['password'], 
            $_POST['email'], 
            $_POST['gender'], 
            $_POST['birthday']  // ĐÃ SỬA THÀNH birthday
        )) {
            $message = "✅ Thêm cung thủ thành công!";
            $action_success = true;
            
            // Reset form values after success
            $_POST = array();
        }
    } catch (Exception $e) {
        $message = "❌ Lỗi: " . $e->getMessage();
        $action_success = false;
    }
}

$users = $scoreManager->getUsers(100);
$archer_categories = $scoreManager->getArcherCategories();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Cung thủ - Archery System</title>
    <link rel="stylesheet" href="../css/score_recorder.css">
    <style>
        .form-notes {
            font-size: 0.8rem;
            color: #666;
            margin-top: 5px;
        }
        .form-notes.error {
            color: #e74c3c;
        }
        .form-notes.success {
            color: #27ae60;
        }
        input:invalid, select:invalid {
            border-color: #e74c3c;
        }
        input:valid, select:valid {
            border-color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>👥 Quản lý Cung thủ</h1>
        
        <!-- Navigation -->
        <div class="nav-buttons">
            <a href="dashboard.php" class="nav-btn">📊 Dashboard</a>
            <a href="scores.php" class="nav-btn">📋 Quản lý Điểm</a>
            <a href="rounds.php" class="nav-btn">🎯 Quản lý Rounds</a>
            <a href="competitions.php" class="nav-btn">🏆 Competitions</a>
            <a href="../homepage.php" class="nav-btn">🏠 Về Trang Chủ</a>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $action_success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
                <?php if (!$action_success): ?>
                    <div class="form-notes error">
                        💡 Mẹo: Thử username và email khác
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Form thêm cung thủ -->
        <div class="section">
            <div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                <h3>➕ Thêm Cung thủ mới</h3>
                <form method="POST" id="addArcherForm">
                    <div class="form-row">
                        <div class="form-column">
                            <label>Họ:</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                            <div class="form-notes">Ví dụ: Nguyễn, Trần, Lê</div>
                        </div>
                        <div class="form-column">
                            <label>Tên:</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                            <div class="form-notes">Ví dụ: Văn A, Thị B</div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-column">
                            <label>Username:</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                   pattern="[a-zA-Z0-9_]{3,50}" 
                                   title="Username chỉ chứa chữ cái, số và gạch dưới (3-50 ký tự)" required>
                            <div class="form-notes">Chỉ dùng chữ, số và _ (ví dụ: long_archer123)</div>
                        </div>
                        <div class="form-column">
                            <label>Password:</label>
                            <input type="password" name="password" minlength="3" required>
                            <div class="form-notes">Tối thiểu 3 ký tự</div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-column">
                            <label>Email:</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            <div class="form-notes">Ví dụ: ten@email.com</div>
                        </div>
                        <div class="form-column">
                            <label>Giới tính:</label>
                            <select name="gender" required>
                                <option value="Male" <?php echo ($_POST['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Nam</option>
                                <option value="Female" <?php echo ($_POST['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Nữ</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- THÊM FIELD BIRTHDAY -->
                    <div class="form-row">
                        <div class="form-column">
                            <label>Ngày sinh:</label>
                            <input type="date" name="birthday" 
                                   value="<?php echo htmlspecialchars($_POST['birthday'] ?? '2000-01-01'); ?>" 
                                   max="<?php echo date('Y-m-d'); ?>" required>
                            <div class="form-notes">Chọn ngày sinh thực tế</div>
                        </div>
                        <div class="form-column">
                            <label>Phân loại cung thủ:</label>
                            <select name="archer_category_id" required>
                                <option value="">-- Chọn phân loại --</option>
                                <?php foreach ($archer_categories as $category): ?>
                                    <option value="<?php echo $category['archer_category_id']; ?>" 
                                        <?php echo ($_POST['archer_category_id'] ?? '') == $category['archer_category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['age_and_gender_class'] . ' - ' . $category['bow_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-notes">Chọn theo độ tuổi và loại cung</div>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_archer" class="btn btn-success">➕ Thêm Cung thủ</button>
                    
                    <div class="form-notes success" style="margin-top: 15px;">
                        💡 <strong>Mẹo:</strong> Nếu bị lỗi "đã tồn tại", hãy thử username và email khác!
                    </div>
                </form>
            </div>

            <!-- Debug: Hiển thị users hiện có -->
            <div style="background: #e8f4fd; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <h4>🔍 Users hiện có trong hệ thống (<?php echo count($users); ?> người):</h4>
                <div style="max-height: 200px; overflow-y: auto; font-size: 0.9rem;">
                    <?php foreach ($users as $user): ?>
                        <div style="padding: 2px 0;">
                            <strong>ID <?php echo $user['user_id']; ?>:</strong> 
                            <?php echo htmlspecialchars($user['username']); ?> - 
                            <?php echo htmlspecialchars($user['email']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Danh sách cung thủ -->
            <h3>📋 Danh sách Cung thủ (<?php echo count($users); ?> người)</h3>
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

    <script>
        // Form validation
        document.getElementById('addArcherForm').addEventListener('submit', function(e) {
            const username = document.querySelector('input[name="username"]').value;
            const email = document.querySelector('input[name="email"]').value;
            
            // Basic validation
            if (!username.match(/^[a-zA-Z0-9_]{3,50}$/)) {
                alert('Username không hợp lệ! Chỉ dùng chữ, số và gạch dưới (3-50 ký tự).');
                e.preventDefault();
                return;
            }
            
            if (email.length < 5 || email.indexOf('@') === -1) {
                alert('Email không hợp lệ!');
                e.preventDefault();
                return;
            }
        });

        // Auto-generate username suggestion
        document.querySelector('input[name="first_name"]').addEventListener('blur', function() {
            const firstName = this.value.toLowerCase().replace(/[^a-z]/g, '');
            const lastName = document.querySelector('input[name="last_name"]').value.toLowerCase().replace(/[^a-z]/g, '');
            
            if (firstName && lastName) {
                const suggestedUsername = (firstName + '_' + lastName + Math.floor(Math.random() * 100)).substring(0, 20);
                document.querySelector('input[name="username"]').placeholder = "Gợi ý: " + suggestedUsername;
            }
        });
    </script>
</body>
</html>