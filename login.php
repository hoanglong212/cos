<?php
session_start();
require_once "connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM user_table WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {
            $_SESSION["user"] = $user["username"];
            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["first_name"] = $user["first_name"];
            $_SESSION["last_name"] = $user["last_name"];
            $_SESSION["email"] = $user["email"];
            
            header("Location: homepage.php");
            exit;
        } else {
            $error = "❌ Incorrect password.";
        }
    } else {
        $error = "⚠️ Username not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Swinburne Archery Club</title>
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="form-box">
            <div class="login-header">
                <i class="fas fa-bullseye"></i>
                <h2>Archer Login</h2>
                <p>Swinburne Archery Club Score System</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert-message alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($_SESSION["register_success"])): ?>
                <div class="alert-message alert-success">
                    <?php echo $_SESSION["register_success"]; ?>
                    <?php unset($_SESSION["register_success"]); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" name="login">
                    <i class="fas fa-sign-in-alt"></i> Login to Score System
                </button>
            </form>
            
            <div class="links-container">
                <p>Don't have an account? <a href="register.php">Register</a></p>
                <p>Go back to <a href="homepage.php">Homepage</a></p>
            </div>
        </div>
    </div>
</body>
</html>