<?php
session_start();
require_once "connect.php";

// Check login status
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['role'] ?? 'guest';
$user_name = $_SESSION['first_name'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

// Get stats for recorder
$pending_scores = 0;
$total_scores = 0;
$active_users = 0;
$user_personal_stats = [];

if (($user_role == 'recorder' || $user_role == 'admin') && $is_logged_in) {
    // Stats for Recorder
    $stats_sql = "SELECT 
        SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending_count,
        COUNT(*) as total_count
        FROM scores";
    
    $stats_result = $conn->query($stats_sql);
    if ($stats_result && $stats_result->num_rows > 0) {
        $stats = $stats_result->fetch_assoc();
        $pending_scores = $stats['pending_count'] ?? 0;
        $total_scores = $stats['total_count'] ?? 0;
    }
    
    $users_sql = "SELECT COUNT(*) as user_count FROM user_table WHERE is_active = 1";
    $users_result = $conn->query($users_sql);
    if ($users_result && $users_result->num_rows > 0) {
        $users = $users_result->fetch_assoc();
        $active_users = $users['user_count'] ?? 0;
    }
} elseif ($user_role == 'archer' && $is_logged_in) {
    // Personal stats for Archer
    $personal_sql = "SELECT 
        MAX(total_score) as personal_best,
        COUNT(*) as total_sessions,
        AVG(total_score) as average_score
        FROM scores 
        WHERE user_id = ? AND is_approved = 1";
    
    $stmt = $conn->prepare($personal_sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user_personal_stats = $result->fetch_assoc();
        }
        $stmt->close();
    }
    
    // Get archer's rank
    $rank_sql = "SELECT COUNT(*) + 1 as user_rank 
                 FROM ( 
                     SELECT user_id, MAX(total_score) as best_score 
                     FROM scores 
                     WHERE is_approved = 1 
                     GROUP BY user_id
                 ) as user_bests 
                 WHERE best_score > COALESCE((SELECT MAX(total_score) FROM scores WHERE user_id = ? AND is_approved = 1), 0)";
    
    $stmt = $conn->prepare($rank_sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $rank_data = $result->fetch_assoc();
            $user_personal_stats['user_rank'] = $rank_data['user_rank'] ?? 'N/A';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swinburne Archery Club - Score Recording System</title>
    <link rel="stylesheet" href="home.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-500: #3498db;
            --primary-400: #5dade2;
            --gradient-primary: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
            --gradient-success: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            --gradient-info: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            --gradient-warning: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            --gradient-danger: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            --gradient-archer: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            --radius-md: 8px;
            --radius-lg: 12px;
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.08);
        }
        
        .hero-img, .feature-img, .round-img, .equipment-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: var(--radius-lg);
        }
        
        .hero-image {
            width: 100%;
            height: 400px;
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        
        .about-image {
            width: 100%;
            height: 300px;
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        
        .round-image {
            width: 100%;
            height: 120px;
            border-radius: var(--radius-md);
            overflow: hidden;
            margin: 1rem 0;
        }
        
        .equipment-image {
            width: 100%;
            height: 300px;
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
        }
        
        .user-role-badge {
            background: #e67e22;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .user-role-badge.archer {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
        }
        
        .user-role-badge.recorder {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }
        
        .user-role-badge.admin {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        
        .btn-logout {
            background: #e74c3c;
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background: #c0392b;
            transform: translateY(-1px);
        }
        
        .btn-recorder {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 5px;
        }
        
        .btn-recorder:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(243, 156, 18, 0.3);
        }
        
        .btn-archer {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 5px;
        }
        
        .btn-archer:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(155, 89, 182, 0.3);
        }
        
        .recorder-stats {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: var(--radius-md);
            margin-top: 20px;
        }
        
        .archer-stats {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: var(--radius-md);
            margin-top: 20px;
        }
        
        .logo {
            height: 40px;
            width: auto;
        }
        
        .personal-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .personal-stat-card {
            background: rgba(255, 255, 255, 0.15);
            padding: 15px;
            border-radius: var(--radius-md);
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        .personal-stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            margin-bottom: 5px;
        }
        
        .personal-stat-label {
            font-size: 0.8rem;
            opacity: 0.9;
        }
        
        .recorder-dashboard {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: var(--radius-lg);
            margin-top: 20px;
        }
        
        .stats-grid-recorder {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card-recorder {
            background: white;
            padding: 20px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            text-align: center;
        }
        
        .stat-card-recorder h3 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-card-recorder p {
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        
        .stat-card-recorder a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        
        .quick-actions-recorder {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .btn-recorder-action {
            background: white;
            color: #2c3e50;
            padding: 15px;
            border-radius: var(--radius-md);
            text-decoration: none;
            text-align: center;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            border: 2px solid transparent;
        }
        
        .btn-recorder-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            border-color: #3498db;
        }
        
        .auth-options {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-brand">
                <img src="assets/logo.png" alt="Swinburne Archery Club" class="logo">
                <span>Swinburne Archery Club</span>
            </div>
            
            <ul class="nav-menu">
                <li><a href="#score-recording" class="nav-link active">Score Recording</a></li>
                <li><a href="#competitions" class="nav-link">Competitions</a></li>
                <li><a href="#rounds" class="nav-link">Rounds & Distances</a></li>
                <li><a href="#results" class="nav-link">Results</a></li>
                <li><a href="#members" class="nav-link">Members</a></li>
                <li><a href="#about" class="nav-link">About</a></li>
            </ul>
            
            <div class="nav-auth">
                <?php if ($is_logged_in): ?>
                    <!-- Logged in - show user info -->
                    <div class="user-info">
                        <span>Welcome, <strong><?php echo htmlspecialchars($user_name); ?>!</strong></span>
                        <div class="user-role-badge <?php echo $user_role; ?>">
                            <?php 
                                $role_names = [
                                    'archer' => 'Archer',
                                    'recorder' => 'Recorder', 
                                    'admin' => 'Admin'
                                ];
                                echo $role_names[$user_role] ?? $user_role; 
                            ?>
                        </div>
                        <a href="logout.php" class="btn-logout">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Not logged in -->
                    <a href="login.php" class="btn-archer">Archer Login</a>
                    <a href="recorder_login.php" class="btn-recorder">Recorder Login</a>
                    <a href="register.php" class="btn-register">Join Club</a>
                <?php endif; ?>
            </div>
            
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <?php if ($user_role == 'recorder' || $user_role == 'admin'): ?>
                <!-- ==================== -->
                <!-- HERO FOR RECORDER -->
                <!-- ==================== -->
                <h1>SCORE MANAGEMENT DASHBOARD</h1>
                <p><strong>Hello <?php echo htmlspecialchars($user_name); ?>!</strong><br>
                Manage scores, approve submissions and track club activities.</p>
                
                <div class="hero-buttons">
                    <a href="recorder/scores.php" class="btn-primary">
                        <i class="fas fa-tasks"></i> Manage Scores
                    </a>
                    <a href="add_score_step1.php" class="btn-secondary">
                        <i class="fas fa-plus-circle"></i> Manual Score Entry
                    </a>
                </div>
                
                <!-- Recorder Dashboard -->
                <div class="recorder-dashboard">
                    <!-- Stats Grid -->
                    <div class="stats-grid-recorder">
                        <div class="stat-card-recorder">
                            <h3><?php echo $pending_scores; ?></h3>
                            <p>Scores Pending Approval</p>
                            <a href="recorder/scores.php?is_approved=0">View Now →</a>
                        </div>
                        <div class="stat-card-recorder">
                            <h3><?php echo $total_scores; ?></h3>
                            <p>Total Scores Recorded</p>
                        </div>
                        <div class="stat-card-recorder">
                            <h3><?php echo $active_users; ?></h3>
                            <p>Active Archers</p>
                            <a href="recorder/archers.php">Manage →</a>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="quick-actions-recorder">
                        <a href="recorder/dashboard.php" class="btn-recorder-action">
                            <i class="fas fa-chart-bar"></i><br>
                            Dashboard
                        </a>
                        <a href="recorder/scores.php" class="btn-recorder-action">
                            <i class="fas fa-list-check"></i><br>
                            Manage Scores
                        </a>
                        <a href="recorder/archers.php" class="btn-recorder-action">
                            <i class="fas fa-users"></i><br>
                            Manage Members
                        </a>
                        <a href="recorder/rounds.php" class="btn-recorder-action">
                            <i class="fas fa-bullseye"></i><br>
                            Manage Rounds
                        </a>
                        <a href="recorder/competitions.php" class="btn-recorder-action">
                            <i class="fas fa-trophy"></i><br>
                            Manage Competitions
                        </a>
                        <a href="recorder/equivalents.php" class="btn-recorder-action">
                            <i class="fas fa-exchange-alt"></i><br>
                            Equivalent Rounds
                        </a>
                    </div>
                </div>
                
            <?php elseif ($user_role == 'archer' && $is_logged_in): ?>
                <!-- ==================== -->
                <!-- HERO FOR ARCHER -->
                <!-- ==================== -->
                <h1>WELCOME BACK, <?php echo strtoupper(htmlspecialchars($user_name)); ?>!</h1>
                <p><strong>Ready to shoot?</strong><br>
                Record scores, track progress and compete with teammates.</p>
                
                <div class="hero-buttons">
                    <a href="add_score_step1.php" class="btn-primary">
                        <i class="fas fa-bullseye"></i> Enter New Score
                    </a>
                    <a href="my_scores.php" class="btn-secondary">
                        <i class="fas fa-chart-line"></i> View My Scores
                    </a>
                </div>
                
                <!-- Personal Stats -->
                <div class="archer-stats">
                    <h4 style="color: white; margin-bottom: 15px;">📊 Personal Statistics</h4>
                    <div class="personal-stats-grid">
                        <div class="personal-stat-card">
                            <div class="personal-stat-value">
                                <?php echo $user_personal_stats['personal_best'] ?? '0'; ?>
                            </div>
                            <div class="personal-stat-label">Personal Best</div>
                        </div>
                        <div class="personal-stat-card">
                            <div class="personal-stat-value">
                                <?php echo $user_personal_stats['total_sessions'] ?? '0'; ?>
                            </div>
                            <div class="personal-stat-label">Sessions</div>
                        </div>
                        <div class="personal-stat-card">
                            <div class="personal-stat-value">
                                <?php echo isset($user_personal_stats['average_score']) ? number_format($user_personal_stats['average_score'], 1) : '0.0'; ?>
                            </div>
                            <div class="personal-stat-label">Average Score</div>
                        </div>
                        <div class="personal-stat-card">
                            <div class="personal-stat-value">
                                #<?php echo $user_personal_stats['user_rank'] ?? 'N/A'; ?>
                            </div>
                            <div class="personal-stat-label">Rank</div>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- ==================== -->
                <!-- HERO FOR GUEST -->
                <!-- ==================== -->
                <h1>ARCHERY SCORE RECORDING SYSTEM</h1>
                <p><strong>Since 1935.</strong><br>
                Professional score recording and competition management system for Swinburne Archery Club.</p>
                
                <div class="hero-buttons">
                    <a href="login.php" class="btn-primary">
                        <i class="fas fa-bullseye"></i> Archer Login
                    </a>
                    <a href="#results" class="btn-secondary">
                        <i class="fas fa-trophy"></i> View Competition Results
                    </a>
                </div>
                
                <div class="auth-options">
                    <a href="login.php" class="btn-archer">
                        <i class="fas fa-user"></i> Archer
                    </a>
                    <a href="recorder_login.php" class="btn-recorder">
                        <i class="fas fa-user-shield"></i> Score Recorder
                    </a>
                    <a href="register.php" class="btn-register">
                        <i class="fas fa-user-plus"></i> Join Club
                    </a>
                </div>
                
                <div class="quick-stats">
                    <div class="stat-item">
                        <h3>144+</h3>
                        <p>Arrows/Round</p>
                    </div>
                    <div class="stat-item">
                        <h3>20-90m</h3>
                        <p>Distances</p>
                    </div>
                    <div class="stat-item">
                        <h3>5 Types</h3>
                        <p>Equipment</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="hero-image">
            <img src="assets/hero-.webp" alt="Archery Club" class="hero-img">
        </div>
    </section>

    <!-- Quick Actions Section -->
    <?php if ($user_role == 'recorder' || $user_role == 'admin'): ?>
        <!-- ==================== -->
        <!-- QUICK ACTIONS FOR RECORDER -->
        <!-- ==================== -->
        <section class="news">
            <div class="container">
                <h2 class="section-title">QUICK MANAGEMENT TOOLS</h2>
                
                <div class="news-grid">
                    <a href="recorder/scores.php" class="news-card action-card">
                        <div class="news-image">
                            <img src="assets/manage score.jpg" alt="Manage Scores" class="round-img">
                        </div>
                        <div class="news-content">
                            <h3 class="news-title">Manage Scores</h3>
                            <p class="news-excerpt">Approve, edit, view and manage all archery scores</p>
                            <span class="read-more">Access →</span>
                        </div>
                    </a>
                    
                    <a href="recorder/archers.php" class="news-card action-card">
                        <div class="news-image">
                            <img src="assets/members.png" alt="Manage Members" class="round-img">
                        </div>
                        <div class="news-content">
                            <h3 class="news-title">Manage Members</h3>
                            <p class="news-excerpt">Approve new members and manage accounts</p>
                            <span class="read-more">Manage →</span>
                        </div>
                    </a>
                    
                    <a href="recorder/rounds.php" class="news-card action-card">
                        <div class="news-image">
                            <img src="assets/round.jpg" alt="Manage Rounds" class="round-img">
                        </div>
                        <div class="news-content">
                            <h3 class="news-title">Manage Rounds</h3>
                            <p class="news-excerpt">Organize and manage rounds and distances</p>
                            <span class="read-more">Manage →</span>
                        </div>
                    </a>
                </div>
            </div>
        </section>
        
    <?php elseif ($user_role == 'archer' && $is_logged_in): ?>
        <!-- ==================== -->
        <!-- QUICK ACTIONS FOR ARCHER -->
        <!-- ==================== -->
        <section class="news">
            <div class="container">
                <h2 class="section-title">QUICK ACTIONS</h2>
                
                <div class="news-grid">
                    <a href="add_score_step1.php" class="news-card action-card">
                        <div class="news-image">
                            <img src="assets/images.jpg" alt="Enter New Score" class="round-img">
                        </div>
                        <div class="news-content">
                            <h3 class="news-title">Enter New Score</h3>
                            <p class="news-excerpt">Record your latest practice or competition score</p>
                            <span class="read-more">Enter Score →</span>
                        </div>
                    </a>
                    
                    <a href="my_scores.php" class="news-card action-card">
                        <div class="news-image">
                            <img src="assets/competition.jpg" alt="Score History" class="round-img">
                        </div>
                        <div class="news-content">
                            <h3 class="news-title">Score History</h3>
                            <p class="news-excerpt">View your progress and personal best scores</p>
                            <span class="read-more">View History →</span>
                        </div>
                    </a>
                    
                    <a href="view_competitions.php" class="news-card action-card">
                        <div class="news-image">
                            <img src="assets/WA720 Round.png" alt="Competition Results" class="round-img">
                        </div>
                        <div class="news-content">
                            <h3 class="news-title">Competition Results</h3>
                            <p class="news-excerpt">View latest tournament results and rankings</p>
                            <span class="read-more">View Results →</span>
                        </div>
                    </a>
                </div>
            </div>
        </section>
        
    <?php else: ?>
        <!-- ==================== -->
        <!-- QUICK ACTIONS FOR GUEST -->
        <!-- ==================== -->
        <section class="news">
            <div class="container">
                <h2 class="section-title">GET STARTED</h2>
                
                <div class="news-grid">
                    <a href="login.php" class="news-card action-card">
                        <div class="news-image">
                            <img src="assets/images.jpg" alt="Archer Login" class="round-img">
                        </div>
                        <div class="news-content">
                            <h3 class="news-title">Archer Login</h3>
                            <p class="news-excerpt">Access your account to record and view scores</p>
                            <span class="read-more">Login →</span>
                        </div>
                    </a>
                    
                    <a href="register.php" class="news-card action-card">
                        <div class="news-image">
                            <img src="assets/club activity.jpg" alt="Join Club" class="round-img">
                        </div>
                        <div class="news-content">
                            <h3 class="news-title">Join Club</h3>
                            <p class="news-excerpt">Become a member and start your archery journey</p>
                            <span class="read-more">Register →</span>
                        </div>
                    </a>
                    
                    <a href="recorder_login.php" class="news-card action-card">
                        <div class="news-image">
                            <img src="assets/competition.jpg" alt="Score Recorder Access" class="round-img">
                        </div>
                        <div class="news-content">
                            <h3 class="news-title">Score Recorder Access</h3>
                            <p class="news-excerpt">Login to score management system for recorders</p>
                            <span class="read-more">Record Scores →</span>
                        </div>
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Features Section -->
    <section class="about">
        <div class="container">
            <h2 class="section-title">SCORE RECORDING FEATURES</h2>
            
            <div class="about-content">
                <div class="about-text">
                    <div class="feature-grid">
                        <div class="feature-item">
                            <i class="fas fa-mobile-alt"></i>
                            <div>
                                <h4>Mobile Score Entry</h4>
                                <p>Record scores on mobile devices with user-friendly interface</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <i class="fas fa-trophy"></i>
                            <div>
                                <h4>Competition Management</h4>
                                <p>Manage WA rounds, equivalent rounds and club championships</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <i class="fas fa-chart-line"></i>
                            <div>
                                <h4>Personal Bests & Analytics</h4>
                                <p>Track PB scores and performance trends over time</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <i class="fas fa-shield-alt"></i>
                            <div>
                                <h4>Approval System</h4>
                                <p>All scores verified by certified score recorders</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="about-image">
                    <img src="assets/club activity.jpg" alt="Club Activities" class="feature-img">
                </div>
            </div>
        </div>
    </section>

    <!-- Rounds & Distances Section -->
    <section class="news">
        <div class="container">
            <h2 class="section-title">STANDARD ROUNDS & DISTANCES</h2>
            
            <div class="news-grid">
                <div class="news-card">
                    <div class="news-image">
                        <img src="assets/images.jpg" alt="WA90/1440 Round" class="round-img">
                    </div>
                    <div class="news-content">
                        <div class="news-date">Standard WA Round</div>
                        <h3 class="news-title">WA90/1440 Round</h3>
                        <p class="news-excerpt">36 arrows at 90m, 70m, 60m, 50m, 40m, 30m. Total 144 arrows, maximum score 1440.</p>
                        <a href="#" class="read-more">View Details</a>
                    </div>
                </div>
                
                <div class="news-card">
                    <div class="news-image">
                        <img src="assets/WA720 Round.png" alt="WA720 Round" class="round-img">
                    </div>
                    <div class="news-content">
                        <div class="news-date">Competition Round</div>
                        <h3 class="news-title">WA720 Round</h3>
                        <p class="news-excerpt">2 distances 6 ends at 70m. Standard Olympic round format.</p>
                        <a href="#" class="read-more">View Details</a>
                    </div>
                </div>
                
                <div class="news-card">
                    <div class="news-image">
                        <img src="assets/Sydney & Brisbane.png" alt="Sydney & Brisbane Rounds" class="round-img">
                    </div>
                    <div class="news-content">
                        <div class="news-date">Australian Rounds</div>
                        <h3 class="news-title">Sydney & Brisbane</h3>
                        <p class="news-excerpt">Named after Australian cities with specific distance combinations.</p>
                        <a href="#" class="read-more">View Details</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Equipment Section -->
    <section class="about" style="background: #2c3e50; color: white;">
        <div class="container">
            <h2 class="section-title" style="color: white;">EQUIPMENT CLASSIFICATIONS & CATEGORIES</h2>
            
            <div class="about-content">
                <div class="about-text">
                    <div class="categories-grid">
                        <div>
                            <h4 style="color: white; margin-bottom: 1rem;">Bow Types</h4>
                            <ul class="category-list">
                                <li><i class="fas fa-check"></i> Recurve</li>
                                <li><i class="fas fa-check"></i> Compound</li>
                                <li><i class="fas fa-check"></i> Recurve Barebow</li>
                                <li><i class="fas fa-check"></i> Compound Barebow</li>
                                <li><i class="fas fa-check"></i> Longbow</li>
                            </ul>
                        </div>
                        
                        <div>
                            <h4 style="color: white; margin-bottom: 1rem;">Age Categories</h4>
                            <ul class="category-list">
                                <li><i class="fas fa-user"></i> Male/Female Open</li>
                                <li><i class="fas fa-user-plus"></i> 50+/60+/70+</li>
                                <li><i class="fas fa-user-graduate"></i> Under 21/18/16/14</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="category-note">
                        <i class="fas fa-info-circle"></i>
                        <p>Each archer competes in their specific category (e.g., "Male Open Compound")</p>
                    </div>
                </div>
                
                <div class="about-image">
                    <img src="assets/equipment.jpg" alt="Archery Equipment" class="equipment-img">
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <section class="about" style="background: #f8f9fa;">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h4>Swinburne Archery Club</h4>
                    <p><i class="fas fa-map-marker-alt"></i> Lower Arroyo Park, Pasadena, California</p>
                    <p><i class="fas fa-clock"></i> Practice: Tue & Thu 5-8PM, Sat 9AM-12PM</p>
                </div>
                <div class="footer-section">
                    <h4>CONTACT</h4>
                    <p><i class="fas fa-envelope"></i> score.recording@rovingarchers.org</p>
                    <p><i class="fas fa-phone"></i> (626) 555-ARCHERY</p>
                </div>
                <div class="footer-section">
                    <h4>QUICK LINKS</h4>
                    <p><a href="login.php" style="color: #666; text-decoration: none;"><i class="fas fa-sign-in-alt"></i> Archer Login</a></p>
                    <p><a href="recorder_login.php" style="color: #666; text-decoration: none;"><i class="fas fa-user-shield"></i> Recorder Login</a></p>
                    <p><a href="register.php" style="color: #666; text-decoration: none;"><i class="fas fa-user-plus"></i> Join Club</a></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>Archery Score Recording System © 2024 - Following World Archery & Archery Australia Standards</p>
            </div>
        </div>
    </section>

    <script>
        // Mobile menu toggle
        const hamburger = document.querySelector('.hamburger');
        const navMenu = document.querySelector('.nav-menu');
        
        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            hamburger.classList.toggle('active');
        });

        // Close mobile menu when clicking on links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                hamburger.classList.remove('active');
            });
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>