<?php
session_start();
require_once "connect.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    // BƯỚC 1: Kiểm tra user tồn tại
    $sql = "SELECT user_id, username, password, first_name, last_name, email 
            FROM user_table 
            WHERE username = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Kiểm tra password
        if (password_verify($password, $user["password"])) {
            // BƯỚC 2: Kiểm tra role - FIX LỖI Ở ĐÂY
            $sql_role = "SELECT r.role_name 
                        FROM user_roles ur 
                        INNER JOIN roles r ON ur.role_id = r.role_id 
                        WHERE ur.user_id = ? 
                        AND r.role_name IN ('recorder', 'admin')";
            
            $stmt_role = $conn->prepare($sql_role);
            $stmt_role->bind_param("i", $user['user_id']);
            $stmt_role->execute();
            $result_role = $stmt_role->get_result();
            
            if ($result_role->num_rows >= 1) {
                $role_data = $result_role->fetch_assoc();
                
                $_SESSION["user"] = $user["username"];
                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["first_name"] = $user["first_name"];
                $_SESSION["last_name"] = $user["last_name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $role_data["role_name"];
                
                header("Location: homepage.php");
                exit;
            } else {
                $error = "⚠️ Recorder/Admin access only. Use Archer Login for archer accounts.";
            }
        } else {
            $error = "❌ Incorrect password.";
        }
    } else {
        $error = "⚠️ Username not found.";
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recorder Login - Swinburne Archery Club</title>
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .login-header {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }
        .test-accounts {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-box">
            <div class="login-header">
                <i class="fas fa-user-shield"></i>
                <h2>Recorder Login</h2>
                <p>Swinburne Archery Club - Score Management System</p>
            </div>

            
            
            <?php if (!empty($error)): ?>
                <div class="alert-message alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Recorder Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter recorder username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" name="login" class="btn-recorder" style="width: 100%; padding: 12px; background: #e67e22; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    <i class="fas fa-sign-in-alt"></i> Login as Recorder
                </button>
            </form>
            
            <div class="links-container">
                <p>Archer login? <a href="login.php">Archer Login</a></p>
                <p>Go back to <a href="homepage.php">Homepage</a></p>
            </div>
        </div>
    </div>
</body>
</html>