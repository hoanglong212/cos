<?php
session_start();
require_once "connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    $username = trim($_POST["username"]);
    $password = password_hash($_POST["password"], PASSWORD_BCRYPT);
    $email = trim($_POST["email"]);
    $fname = trim($_POST["fname"]);
    $lname = trim($_POST["lname"]);
    $birthday = $_POST["birthdate"];
    $gender = $_POST["gender"];
    $phone = $_POST["phonenumber"];

    // Check if username or email already exists
    $check = $conn->prepare("SELECT * FROM user_table WHERE username = ? OR email = ?");
    $check->bind_param("ss", $username, $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $error = "⚠️ Username or Email already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO user_table (username, password, email, first_name, last_name, birthday, gender, phone_number)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $username, $password, $email, $fname, $lname, $birthday, $gender, $phone);
        if ($stmt->execute()) {
            $_SESSION["register_success"] = "✅ Registration successful! You can now log in.";
            header("Location: login.php");
            exit;
        } else {
            $error = "❌ Registration failed. Try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Swinburne Archery Club</title>
    <link rel="stylesheet" href="register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="form-box">
            <div class="register-header">
                <i class="fas fa-user-plus"></i>
                <h2>Join Our Club</h2>
                <p>Create your archer account</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert-message alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="fname">First Name</label>
                        <input type="text" id="fname" name="fname" placeholder="Enter your first name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="lname">Last Name</label>
                        <input type="text" id="lname" name="lname" placeholder="Enter your last name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Choose a username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                    <div class="password-strength">
                        <div class="strength-weak"></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="birthdate">Birth Date</label>
                        <input type="date" id="birthdate" name="birthdate" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="">-- Select Gender --</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="phonenumber">Phone Number</label>
                    <input type="text" id="phonenumber" name="phonenumber" placeholder="Enter your phone number" required>
                </div>
                
                <button type="submit" name="register">
                    <i class="fas fa-user-check"></i> Create Account
                </button>
                
                <div class="links-container">
                    <p>Already have an account? <a href="login.php">Login</a></p>
                    <p>Go back to <a href="homepage.php">Homepage</a></p>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthBar = document.querySelector('.password-strength div');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            
            // Update strength bar
            strengthBar.className = '';
            if (password.length === 0) {
                strengthBar.style.width = '0%';
                strengthBar.style.background = '#e9ecef';
            } else {
                switch(strength) {
                    case 1:
                        strengthBar.className = 'strength-weak';
                        break;
                    case 2:
                        strengthBar.className = 'strength-fair';
                        break;
                    case 3:
                        strengthBar.className = 'strength-good';
                        break;
                    case 4:
                        strengthBar.className = 'strength-strong';
                        break;
                }
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const phone = document.getElementById('phonenumber').value;
            const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
            
            if (!phoneRegex.test(phone)) {
                e.preventDefault();
                alert('Please enter a valid phone number');
                return false;
            }
            
            const birthdate = new Date(document.getElementById('birthdate').value);
            const today = new Date();
            const minAge = new Date(today.getFullYear() - 10, today.getMonth(), today.getDate());
            
            if (birthdate > minAge) {
                e.preventDefault();
                alert('You must be at least 10 years old to register');
                return false;
            }
        });
    </script>
</body>
</html>