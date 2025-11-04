<?php
include 'connect.php';

// 1. NHẬN DỮ LIỆU TỪ BƯỚC 1
$user_id = $_POST['user_id'] ?? 0;
$round_category_id = $_POST['round_category_id'] ?? 0;
$bow_category_id = $_POST['bow_category_id'] ?? 0;
$date_recorded = $_POST['date_recorded'] ?? date('Y-m-d');
$competition_id = (!empty($_POST['competition_id'])) ? $_POST['competition_id'] : NULL;
$note = $_POST['note'] ?? '';
$context = $_POST['context'] ?? 'practice';

// Kiểm tra validation
if ($round_category_id == 0 || $user_id == 0) {
    die("Lỗi: Vui lòng quay lại Bước 1 và chọn đầy đủ Người bắn và Round Category.");
}

// 2. LẤY TÊN TỪ ID
$user_name = "Không rõ";
$round_name = "Không rõ";
$bow_name = "Không rõ";

// Lấy tên User
$stmt_user = $conn->prepare("SELECT first_name, last_name FROM user_table WHERE user_id = ?");
if ($stmt_user) {
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $res_user = $stmt_user->get_result();
    if ($row = $res_user->fetch_assoc()) {
        $user_name = $row['first_name'] . ' ' . $row['last_name'];
    }
    $stmt_user->close();
}

// Lấy tên Round
$stmt_round = $conn->prepare("SELECT round_name FROM round_category WHERE round_category_id = ?");
if ($stmt_round) {
    $stmt_round->bind_param("i", $round_category_id);
    $stmt_round->execute();
    $res_round = $stmt_round->get_result();
    if ($row = $res_round->fetch_assoc()) {
        $round_name = $row['round_name'];
    }
    $stmt_round->close();
}

// Lấy tên Dụng cụ
$stmt_bow = $conn->prepare("SELECT category_name FROM bow_category WHERE bow_category_id = ?");
if ($stmt_bow) {
    $stmt_bow->bind_param("i", $bow_category_id);
    $stmt_bow->execute();
    $res_bow = $stmt_bow->get_result();
    if ($row = $res_bow->fetch_assoc()) {
        $bow_name = $row['category_name'];
    }
    $stmt_bow->close();
}

// 3. LẤY CẤU TRÚC ROUND
$ranges = [];
$sql = "SELECT 
            rc.range_category_id, rc.name, rc.number_of_ends, rc.distance, rc.face_size
        FROM round_category_details rcd
        JOIN range_category rc ON rcd.range_category_id = rc.range_category_id
        WHERE rcd.round_category_id = ? ORDER BY rc.distance DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $round_category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ranges[] = $row;
    }
    $stmt->close();
} else {
    die("Lỗi SQL: " . $conn->error);
}

if (empty($ranges)) {
    die("Lỗi: Round này chưa được định nghĩa cự ly (ranges). Vui lòng dùng trang 'Quản lý Cấu trúc Round' để thiết lập.");
}

// 4. TÍNH ĐIỂM TỰ ĐỘNG KHI FORM ĐƯỢC SUBMIT
$total_score = 0;
$tens_count = 0;
$total_arrows = 0;
$range_scores = [];
$achievement_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate'])) {
    // Tính điểm từ dữ liệu nhập vào
    if (isset($_POST['scores']) && is_array($_POST['scores'])) {
        foreach ($_POST['scores'] as $range_id => $ends) {
            $range_name = '';
            $range_total = 0;
            $range_arrows = 0;
            
            // Tìm tên range
            foreach ($ranges as $range) {
                if ($range['range_category_id'] == $range_id) {
                    $range_name = $range['name'];
                    break;
                }
            }
            
            // Tính điểm cho từng end trong range
            foreach ($ends as $end_num => $score_string) {
                $score_string = trim($score_string);
                if (!empty($score_string)) {
                    $arrows = explode(',', $score_string);
                    foreach ($arrows as $arrow) {
                        $arrow = strtoupper(trim($arrow));
                        if ($arrow === 'X' || $arrow === '10') {
                            $range_total += 10;
                            $tens_count++;
                        } elseif ($arrow === 'M') {
                            $range_total += 0;
                        } elseif (is_numeric($arrow) && $arrow >= 0 && $arrow <= 9) {
                            $range_total += (int)$arrow;
                            if ($arrow == 10) $tens_count++;
                        }
                        $total_arrows++;
                        $range_arrows++;
                    }
                }
            }
            
            $total_score += $range_total;
            if ($range_arrows > 0) {
                $range_scores[$range_name] = $range_total;
            }
        }
    }
    
    // TÍNH ĐIỂM TRUNG BÌNH
    $average_score = $total_arrows > 0 ? number_format($total_score / $total_arrows, 1) : '0.0';
    
    // KIỂM TRA THÀNH TÍCH
    if ($total_score >= 600) {
        $achievement_message = "
        <div class='achievement-popup' onclick='this.remove()'>
            <div class='achievement-content'>
                <div class='achievement-icon'>🎯🏆</div>
                <h3>Chúc mừng!</h3>
                <p>Điểm số tuyệt vời: <strong>{$total_score}</strong></p>
                <small>Bạn đang tiến bộ rất nhanh!</small>
                <br><small style='color: #7f8c8d; font-size: 0.8rem;'>(Nhấp để đóng)</small>
            </div>
        </div>";
    } elseif ($total_score >= 500) {
        $achievement_message = "
        <div class='achievement-popup' onclick='this.remove()'>
            <div class='achievement-content'>
                <div class='achievement-icon'>⭐</div>
                <h3>Rất tốt!</h3>
                <p>Điểm số ấn tượng: <strong>{$total_score}</strong></p>
                <small>Tiếp tục phát huy nhé!</small>
                <br><small style='color: #7f8c8d; font-size: 0.8rem;'>(Nhấp để đóng)</small>
            </div>
        </div>";
    }
}

// 5. LẤY LỊCH SỬ ĐIỂM ĐỂ SO SÁNH
$previous_score = 0;
$improvement = 0;

$history_sql = "SELECT total_score FROM scores 
                WHERE user_id = ? AND round_category_id = ? 
                ORDER BY date_recorded DESC LIMIT 1";
$history_stmt = $conn->prepare($history_sql);
if ($history_stmt) {
    $history_stmt->bind_param("ii", $user_id, $round_category_id);
    $history_stmt->execute();
    $history_result = $history_stmt->get_result();
    if ($row = $history_result->fetch_assoc()) {
        $previous_score = $row['total_score'];
        if ($total_score > 0) {
            $improvement = $total_score - $previous_score;
        }
    }
    $history_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm điểm - Bước 2</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style_step2.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="main-wrapper-step2">
    
    <!-- HIỂN THỊ THÔNG BÁO THÀNH TÍCH -->
    <?php echo $achievement_message; ?>
    
    <div class="form-card-step2">
        <div class="card-header-step2">
            <!-- NÚT QUAY LẠI GÓC PHẢI -->
            <div class="back-button-container">
                <a href="step1.php" class="back-button">Quay lại Bước 1</a>
            </div>

           
            <h1>🎯 Nhập điểm (Bước 2/2)</h1>
        </div>
        
        <div class="card-body-step2">
            <!-- HỘP TÓM TẮT -->
            <div class="summary-box-modern">
                <h3>Tóm tắt buổi bắn</h3>
                <ul>
                    <li><strong>Người bắn:</strong> <?php echo htmlspecialchars($user_name); ?></li>
                    <li><strong>Round:</strong> <?php echo htmlspecialchars($round_name); ?></li>
                    <li><strong>Dụng cụ:</strong> <?php echo htmlspecialchars($bow_name); ?></li>
                    <li><strong>Ngày:</strong> <?php echo htmlspecialchars($date_recorded); ?></li>
                    <li><strong>Loại:</strong> <?php echo htmlspecialchars(ucfirst($context)); ?></li>
                    <?php if ($competition_id): ?>
                    <li><strong>Competition:</strong> <?php echo htmlspecialchars($competition_id); ?></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- TÍNH ĐIỂM TỰ ĐỘNG -->
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate'])): 
                // Tính phần trăm điểm (giả sử điểm tối đa là 720)
                $max_score = 720;
                $progress_percent = min(100, ($total_score / $max_score) * 100);
                
                // Xác định badge thành tích
                $badges = [];
                if ($total_score >= 600) {
                    $badges[] = '🏆 Elite Archer';
                } elseif ($total_score >= 500) {
                    $badges[] = '⭐ Advanced Shooter';
                }
                if ($tens_count >= 40) {
                    $badges[] = '🎯 Precision Master';
                }
                if ($average_score >= 8.0) {
                    $badges[] = '🔥 Consistent Performer';
                }
            ?>
            <div class="score-calculator">
                <h4>Kết quả tính điểm</h4>
                
                <!-- PROGRESS BAR -->
                <div class="score-progress">
                    <div class="progress-header">
                        <span>Tiến độ điểm số</span>
                        <span><?php echo $total_score; ?> / <?php echo $max_score; ?></span>
                    </div>
                    <div class="progress-container">
                        <div class="progress-fill" style="width: <?php echo $progress_percent; ?>%"></div>
                    </div>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-item total-score">
                        <h5>Tổng điểm</h5>
                        <p class="stat-value"><?php echo $total_score; ?></p>
                    </div>
                    <div class="stat-item">
                        <h5>Số mũi tên 10/X</h5>
                        <p class="stat-value"><?php echo $tens_count; ?></p>
                    </div>
                    <div class="stat-item">
                        <h5>Điểm trung bình</h5>
                        <p class="stat-value"><?php echo $average_score; ?></p>
                    </div>
                    <div class="stat-item">
                        <h5>Tổng mũi tên</h5>
                        <p class="stat-value"><?php echo $total_arrows; ?></p>
                    </div>
                </div>

                <!-- ACHIEVEMENT BADGES -->
                <?php if (!empty($badges)): ?>
                <div class="achievement-badges">
                    <?php foreach ($badges as $badge): ?>
                    <div class="badge"><?php echo $badge; ?></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- SO SÁNH VỚI LẦN TRƯỚC -->
                <?php if ($previous_score > 0): ?>
                <div class="comparison-box">
                    <h5>So sánh với lần trước</h5>
                    <div class="comparison-stats">
                        <div class="comparison-item">
                            <span>Lần trước:</span>
                            <strong><?php echo $previous_score; ?> điểm</strong>
                        </div>
                        <div class="comparison-item <?php echo $improvement >= 0 ? 'improved' : 'declined'; ?>">
                            <span>Thay đổi:</span>
                            <strong><?php echo $improvement >= 0 ? '+' : ''; ?><?php echo $improvement; ?> điểm</strong>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ĐIỂM CHI TIẾT THEO CỰ LY -->
                <?php if (!empty($range_scores)): ?>
                <div class="score-breakdown">
                    <h5>Điểm chi tiết theo cự ly</h5>
                    <div class="range-scores">
                        <?php foreach ($range_scores as $range_name => $score): ?>
                        <div class="range-score-item">
                            <span class="distance"><?php echo htmlspecialchars($range_name); ?></span>
                            <span class="score"><?php echo $score; ?> điểm</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- HƯỚNG DẪN -->
            <div class="help-box-modern">
                <p>
                    <strong>Cách nhập (cho mỗi End):</strong> Nhập 6 mũi tên, cách nhau bằng dấu phẩy (<code>,</code>).
                    <br>
                    <strong>Ví dụ:</strong> <code>9,8,7,X</code> (Dùng X cho 10, M cho 0)
                </p>
            </div>

            <form action="" method="POST" id="score-form">
                <!-- CÁC TRƯỜNG ẨN TỪ BƯỚC 1 -->
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                <input type="hidden" name="round_category_id" value="<?php echo htmlspecialchars($round_category_id); ?>">
                <input type="hidden" name="bow_category_id" value="<?php echo htmlspecialchars($bow_category_id); ?>">
                <input type="hidden" name="date_recorded" value="<?php echo htmlspecialchars($date_recorded); ?>">
                <input type="hidden" name="competition_id" value="<?php echo htmlspecialchars($competition_id); ?>">
                <input type="hidden" name="note" value="<?php echo htmlspecialchars($note); ?>">
                <input type="hidden" name="context" value="<?php echo htmlspecialchars($context); ?>">

                <!-- LƯỚI ĐIỂM -->
                <div class="scoring-grid-modern">
                    <?php foreach ($ranges as $range): ?>
                        <fieldset class="range-fieldset">
                            <legend>
                                📍 <?php echo htmlspecialchars("{$range['distance']}m, {$range['face_size']} Face"); ?>
                                (<?php echo htmlspecialchars($range['number_of_ends']); ?> ends)
                            </legend>
                            
                            <?php for ($endNum = 1; $endNum <= $range['number_of_ends']; $endNum++): 
                                $current_value = '';
                                if (isset($_POST['scores'][$range['range_category_id']][$endNum])) {
                                    $current_value = htmlspecialchars($_POST['scores'][$range['range_category_id']][$endNum]);
                                }
                            ?>
                                <div class="end-row-modern">
                                    <label>End <?php echo $endNum; ?>:</label>
                                    <input 
                                        type="text" 
                                        class="end-input-modern"
                                        placeholder="X,9,8,7,M..."
                                        name="scores[<?php echo $range['range_category_id']; ?>][<?php echo $endNum; ?>]"
                                        value="<?php echo $current_value; ?>"
                                        required
                                        pattern="[0-9XxMm, ]+"
                                        title="Nhập 6 điểm cách nhau bằng dấu phẩy (VD: X,9,8,7,M)"
                                    />
                                </div>
                            <?php endfor; ?>
                        </fieldset>
                    <?php endforeach; ?>
                </div>
                
                <!-- NÚT BẤM -->
                <div class="form-buttons">
                    <button type="submit" name="calculate" class="calculate-btn">
                        🔢 Tính điểm ngay
                    </button>
                    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate'])): ?>
                    <button type="submit" formaction="insert_score_fast.php" class="submit-btn-step2">
                        💾 Lưu điểm và hoàn thành
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <script>
        // TỰ ĐỘNG ĐÓNG POPUP SAU 5 GIÂY
        setTimeout(function() {
            const popup = document.querySelector('.achievement-popup');
            if (popup) {
                popup.remove();
            }
        }, 5000);

        // CHO PHÉP NHẤP ĐỂ ĐÓNG POPUP
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('achievement-popup')) {
                e.target.remove();
            }
        });

        // CHUYỂN VỀ BƯỚC 1
        function goBackToStep1() {
            window.history.back();
        }
    </script>
</body>
</html>