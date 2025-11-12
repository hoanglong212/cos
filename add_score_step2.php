<?php
include 'connect.php';

// 1. RECEIVE DATA FROM STEP 1 - ADD archer_category_id
$user_id = $_POST['user_id'] ?? 0;
$round_category_id = $_POST['round_category_id'] ?? 0;
$bow_category_id = $_POST['bow_category_id'] ?? 0;
$archer_category_id = $_POST['archer_category_id'] ?? 0; // ADD THIS LINE
$date_recorded = $_POST['date_recorded'] ?? date('Y-m-d');
$competition_id = !empty($_POST['competition_id']) ? $_POST['competition_id'] : NULL;
$note = $_POST['note'] ?? '';
$context = $_POST['context'] ?? 'practice';

// Check validation - ADD archer_category_id CHECK
if ($round_category_id == 0 || $user_id == 0 || $archer_category_id == 0) {
    die("Error: Please go back to Step 1 and select all required information.");
}

// 2. GET BASIC INFORMATION - ADD ARCHER CATEGORY INFO
[$user_name, $round_name, $bow_name, $archer_category_name] = getBasicInfo($conn, $user_id, $round_category_id, $bow_category_id, $archer_category_id);

// 3. GET ROUND STRUCTURE
$ranges = getRoundRanges($conn, $round_category_id);

// 4. CALCULATE SCORES AUTOMATICALLY
[$total_score, $tens_count, $total_arrows, $range_scores, $achievement_message, $max_possible_score, $selected_ends_count] = 
    calculateScores($_POST, $ranges);

// 5. GET SCORE HISTORY
[$previous_score, $improvement] = getScoreHistory($conn, $user_id, $round_category_id, $total_score);

// BASIC INFO FUNCTION - ADD ARCHER CATEGORY
function getBasicInfo($conn, $user_id, $round_category_id, $bow_category_id, $archer_category_id) {
    $user_name = $round_name = $bow_name = $archer_category_name = "Unknown";
    
    // Get User name
    $stmt = $conn->prepare("SELECT first_name, last_name FROM user_table WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) {
            $user_name = $row['first_name'] . ' ' . $row['last_name'];
        }
        $stmt->close();
    }
    
    // Get Round name
    $stmt = $conn->prepare("SELECT round_name FROM round_category WHERE round_category_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $round_category_id);
        $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) {
            $round_name = $row['round_name'];
        }
        $stmt->close();
    }
    
    // Get Equipment name
    $stmt = $conn->prepare("SELECT category_name FROM bow_category WHERE bow_category_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $bow_category_id);
        $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) {
            $bow_name = $row['category_name'];
        }
        $stmt->close();
    }
    
    // Get Archer Category name - ADD THIS SECTION
    $stmt = $conn->prepare("SELECT category_name FROM archer_category WHERE archer_category_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $archer_category_id);
        $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) {
            $archer_category_name = $row['category_name'];
        }
        $stmt->close();
    }
    
    return [$user_name, $round_name, $bow_name, $archer_category_name];
}

// GET ROUND STRUCTURE FUNCTION (keep same)
function getRoundRanges($conn, $round_category_id) {
    $sql = "SELECT rc.range_category_id, rc.name, rc.number_of_ends, rc.distance, rc.face_size
            FROM round_category_details rcd
            JOIN range_category rc ON rcd.range_category_id = rc.range_category_id
            WHERE rcd.round_category_id = ? ORDER BY rc.distance DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) die("SQL Error: " . $conn->error);
    
    $stmt->bind_param("i", $round_category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ranges = [];
    while ($row = $result->fetch_assoc()) {
        $ranges[] = $row;
    }
    $stmt->close();
    
    if (empty($ranges)) {
        die("Error: This round has not been defined with ranges.");
    }
    
    return $ranges;
}

// CALCULATE SCORES FUNCTION (keep same)
function calculateScores($post, $ranges) {
    $total_score = $tens_count = $total_arrows = $max_possible_score = $selected_ends_count = 0;
    $range_scores = [];
    $achievement_message = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($post['calculate'])) {
        // Calculate maximum possible score
        foreach ($ranges as $range) {
            $is_selected = isset($post['selected_ranges'][$range['range_category_id']]);
            if ($is_selected) {
                $max_possible_score += $range['number_of_ends'] * 6 * 10;
                $selected_ends_count += $range['number_of_ends'];
            }
        }
        
        // Default to all if none selected
        if ($max_possible_score === 0) {
            foreach ($ranges as $range) {
                $max_possible_score += $range['number_of_ends'] * 6 * 10;
                $selected_ends_count += $range['number_of_ends'];
            }
        }
        
        // Calculate actual score
        if (isset($post['scores']) && is_array($post['scores'])) {
            foreach ($post['scores'] as $range_id => $ends) {
                $range_name = getRangeName($ranges, $range_id);
                $range_total = 0;
                
                foreach ($ends as $score_string) {
                    $score_string = trim($score_string);
                    if (!empty($score_string)) {
                        $arrows = explode(',', $score_string);
                        foreach ($arrows as $arrow) {
                            $arrow = strtoupper(trim($arrow));
                            $score = calculateArrowScore($arrow);
                            $range_total += $score;
                            if ($score === 10) $tens_count++;
                            $total_arrows++;
                        }
                    }
                }
                
                $total_score += $range_total;
                if ($range_total > 0) {
                    $range_scores[$range_name] = $range_total;
                }
            }
        }
        
        // Check achievement
        $achievement_message = checkAchievement($total_score, $max_possible_score, $tens_count, $selected_ends_count);
    }
    
    return [$total_score, $tens_count, $total_arrows, $range_scores, $achievement_message, $max_possible_score, $selected_ends_count];
}

// HELPER FUNCTIONS (keep same)
function getRangeName($ranges, $range_id) {
    foreach ($ranges as $range) {
        if ($range['range_category_id'] == $range_id) {
            return $range['name'];
        }
    }
    return '';
}

function calculateArrowScore($arrow) {
    if ($arrow === 'X' || $arrow === '10') return 10;
    if ($arrow === 'M') return 0;
    if (is_numeric($arrow) && $arrow >= 0 && $arrow <= 9) return (int)$arrow;
    return 0;
}

function checkAchievement($total_score, $max_possible_score, $tens_count, $selected_ends_count) {
    $high_threshold = $max_possible_score * 0.83;
    $medium_threshold = $max_possible_score * 0.69;
    
    if ($total_score >= $high_threshold) {
        return createAchievementPopup('🎯🏆', 'Congratulations!', 'Excellent score', $total_score);
    } elseif ($total_score >= $medium_threshold) {
        return createAchievementPopup('⭐', 'Well done!', 'Impressive score', $total_score);
    }
    return '';
}

function createAchievementPopup($icon, $title, $message, $score) {
    return "<div class='achievement-popup' onclick='this.remove()'>
        <div class='achievement-content'>
            <div class='achievement-icon'>{$icon}</div>
            <h3>{$title}</h3>
            <p>{$message}: <strong>{$score}</strong></p>
            <small>" . ($icon === '🎯🏆' ? 'You are improving very fast!' : 'Keep up the good work!') . "</small>
            <br><small style='color: #7f8c8d; font-size: 0.8rem;'>(Click to close)</small>
        </div>
    </div>";
}

// GET SCORE HISTORY FUNCTION (keep same)
function getScoreHistory($conn, $user_id, $round_category_id, $current_score) {
    $previous_score = $improvement = 0;
    
    $stmt = $conn->prepare("SELECT total_score FROM scores WHERE user_id = ? AND round_category_id = ? ORDER BY date_recorded DESC LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("ii", $user_id, $round_category_id);
        $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) {
            $previous_score = $row['total_score'];
            $improvement = $current_score - $previous_score;
        }
        $stmt->close();
    }
    
    return [$previous_score, $improvement];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Score - Step 2</title>
    <link rel="stylesheet" href="style2.css"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="main-wrapper-step2">
    
    <?php echo $achievement_message; ?>
    
    <div class="form-card-step2">
        <div class="card-header-step2">
            <div class="back-button-container">
                <a href="add_score_step1.php" class="back-button">Back to Step 1</a>
            </div>
            <h1>🎯 Enter Scores (Step 2/2)</h1>
        </div>
        
        <div class="card-body-step2">
            <!-- SUMMARY BOX - ADD ARCHER CLASSIFICATION -->
            <div class="summary-box-modern">
                <h3>Session Summary</h3>
                <ul>
                    <li><strong>Shooter:</strong> <?= htmlspecialchars($user_name) ?></li>
                    <li><strong>Classification:</strong> <?= htmlspecialchars($archer_category_name) ?></li> <!-- ADD THIS LINE -->
                    <li><strong>Round:</strong> <?= htmlspecialchars($round_name) ?></li>
                    <li><strong>Equipment:</strong> <?= htmlspecialchars($bow_name) ?></li>
                    <li><strong>Date:</strong> <?= htmlspecialchars($date_recorded) ?></li>
                    <li><strong>Type:</strong> <?= htmlspecialchars(ucfirst($context)) ?></li>
                    <?php if ($competition_id): ?>
                    <li><strong>Competition:</strong> <?= htmlspecialchars($competition_id) ?></li>
                    <?php endif; ?>
                    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate'])): ?>
                    <li><strong>Ends calculated:</strong> <?= $selected_ends_count ?> ends</li>
                    <li><strong>Maximum possible score:</strong> <?= $max_possible_score ?> points</li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- SCORE CALCULATION RESULTS -->
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate'])): 
                $average_score = $total_arrows > 0 ? number_format($total_score / $total_arrows, 1) : '0.0';
                $progress_percent = $max_possible_score > 0 ? min(100, ($total_score / $max_possible_score) * 100) : 0;
                $badges = getAchievementBadges($total_score, $max_possible_score, $tens_count, $selected_ends_count, $average_score);
            ?>
            <div class="score-calculator">
                <h4>Score Calculation Results</h4>
                
                <!-- PROGRESS BAR -->
                <div class="score-progress">
                    <div class="progress-header">
                        <span>Score Progress</span>
                        <span><?= $total_score ?> / <?= $max_possible_score ?></span>
                    </div>
                    <div class="progress-container">
                        <div class="progress-fill" style="width: <?= $progress_percent ?>%"></div>
                    </div>
                    <?php if ($selected_ends_count > 0): ?>
                    <div style="text-align: center; margin-top: 5px; font-size: 0.8rem; color: #7f8c8d;">
                        <?= $selected_ends_count ?> ends calculated • <?= number_format($progress_percent, 1) ?>%
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-item total-score">
                        <h5>Total Score</h5>
                        <p class="stat-value"><?= $total_score ?></p>
                    </div>
                    <div class="stat-item">
                        <h5>10/X Arrows</h5>
                        <p class="stat-value"><?= $tens_count ?></p>
                    </div>
                    <div class="stat-item">
                        <h5>Average Score</h5>
                        <p class="stat-value"><?= $average_score ?></p>
                    </div>
                    <div class="stat-item">
                        <h5>Total Arrows</h5>
                        <p class="stat-value"><?= $total_arrows ?></p>
                    </div>
                </div>

                <!-- BADGES -->
                <?php if (!empty($badges)): ?>
                <div class="achievement-badges">
                    <?php foreach ($badges as $badge): ?>
                    <div class="badge"><?= $badge ?></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- COMPARISON -->
                <?php if ($previous_score > 0): ?>
                <div class="comparison-box">
                    <h5>Comparison with Previous</h5>
                    <div class="comparison-stats">
                        <div class="comparison-item">
                            <span>Previous:</span>
                            <strong><?= $previous_score ?> points</strong>
                        </div>
                        <div class="comparison-item <?= $improvement >= 0 ? 'improved' : 'declined' ?>">
                            <span>Change:</span>
                            <strong><?= $improvement >= 0 ? '+' : '' ?><?= $improvement ?> points</strong>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- DETAILED SCORES -->
                <?php if (!empty($range_scores)): ?>
                <div class="score-breakdown">
                    <h5>Detailed Scores by Distance</h5>
                    <div class="range-scores">
                        <?php foreach ($range_scores as $range_name => $score): ?>
                        <div class="range-score-item">
                            <span class="distance"><?= htmlspecialchars($range_name) ?></span>
                            <span class="score"><?= $score ?> points</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- INSTRUCTIONS -->
            <div class="help-box-modern">
                <p>
                    <strong>🎯 How to enter scores:</strong> Enter 6 scores for each end separated by commas.
                    <br>
                    <strong>📝 Example:</strong> <code>X,9,8,7,M,10</code> (Use <code>X</code> for 10, <code>M</code> for 0)
                </p>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="quick-actions">
                <button type="button" class="quick-btn" onclick="selectAllRanges()">✅ Select all distances</button>
                <button type="button" class="quick-btn secondary" onclick="deselectAllRanges()">❌ Deselect all</button>
                <button type="button" class="quick-btn" onclick="fillSampleData()">🧪 Fill sample data</button>
            </div>

            <!-- RANGE SELECTOR -->
            <div class="range-selector-modern">
                <h4>Select distances to enter scores</h4>
                <div class="range-options-grid">
                    <?php foreach ($ranges as $range): 
                        $is_checked = !isset($_POST['selected_ranges']) || isset($_POST['selected_ranges'][$range['range_category_id']]);
                    ?>
                    <div class="range-option-card <?= $is_checked ? 'selected' : '' ?>" 
                         onclick="toggleRangeCard(this, <?= $range['range_category_id'] ?>)">
                        <input type="checkbox" class="range-checkbox-modern" 
                               name="selected_ranges[<?= $range['range_category_id'] ?>]" value="1" 
                               <?= $is_checked ? 'checked' : '' ?>
                               id="range-<?= $range['range_category_id'] ?>">
                        <label class="range-label" for="range-<?= $range['range_category_id'] ?>">
                            <div class="range-info">
                                <span class="range-distance"><?= htmlspecialchars($range['distance']) ?>m</span>
                                <span class="range-details"><?= htmlspecialchars($range['name']) ?> • <?= htmlspecialchars($range['face_size']) ?> Face</span>
                            </div>
                            <div class="range-ends"><?= $range['number_of_ends'] ?> ends</div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <form action="" method="POST" id="score-form">
                <!-- HIDDEN FIELDS - ADD archer_category_id -->
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_id) ?>">
                <input type="hidden" name="round_category_id" value="<?= htmlspecialchars($round_category_id) ?>">
                <input type="hidden" name="bow_category_id" value="<?= htmlspecialchars($bow_category_id) ?>">
                <input type="hidden" name="archer_category_id" value="<?= htmlspecialchars($archer_category_id) ?>"> <!-- ADD THIS LINE -->
                <input type="hidden" name="date_recorded" value="<?= htmlspecialchars($date_recorded) ?>">
                <input type="hidden" name="competition_id" value="<?= htmlspecialchars($competition_id) ?>">
                <input type="hidden" name="note" value="<?= htmlspecialchars($note) ?>">
                <input type="hidden" name="context" value="<?= htmlspecialchars($context) ?>">

                <!-- SCORING GRID -->
                <div class="scoring-grid-modern" id="scoring-grid">
                    <?php foreach ($ranges as $range): 
                        $is_visible = !isset($_POST['selected_ranges']) || isset($_POST['selected_ranges'][$range['range_category_id']]);
                    ?>
                        <fieldset class="range-fieldset <?= !$is_visible ? 'hidden-range' : '' ?>" id="range-fieldset-<?= $range['range_category_id'] ?>">
                            <legend>📍 <?= htmlspecialchars("{$range['distance']}m - {$range['name']} ({$range['face_size']} Face)") ?></legend>
                            
                            <div class="ends-grid">
                                <?php for ($endNum = 1; $endNum <= $range['number_of_ends']; $endNum++): 
                                    $current_value = $_POST['scores'][$range['range_category_id']][$endNum] ?? '';
                                ?>
                                    <div class="end-card">
                                        <div class="end-header">
                                            <div class="end-number">End <?= $endNum ?></div>
                                            <div class="arrows-count">6 arrows</div>
                                        </div>
                                        <input type="text" class="end-input-modern" placeholder="X,9,8,7,M,10"
                                               name="scores[<?= $range['range_category_id'] ?>][<?= $endNum ?>]"
                                               value="<?= htmlspecialchars($current_value) ?>"
                                               pattern="[0-9XxMm, ]+"
                                               title="Enter 6 scores separated by commas (e.g., X,9,8,7,M,10)">
                                        <div class="input-hint">Enter 6 scores, separated by commas</div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </fieldset>
                    <?php endforeach; ?>
                </div>
                
                <!-- BUTTONS -->
                <div class="form-buttons">
                    <button type="submit" name="calculate" class="calculate-btn">🔢 Calculate Score Now</button>
                    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate'])): ?>
                    <button type="submit" formaction="insert_score_fast.php" class="submit-btn-step2">💾 Save Score and Complete</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <script>
        // AUTO CLOSE POPUP AFTER 5 SECONDS
        setTimeout(() => {
            document.querySelector('.achievement-popup')?.remove();
        }, 5000);

        // ALLOW CLICK TO CLOSE POPUP
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('achievement-popup')) {
                e.target.remove();
            }
        });

        // TOGGLE RANGE CARD
        function toggleRangeCard(card, rangeId) {
            const checkbox = card.querySelector('.range-checkbox-modern');
            const rangeFieldset = document.getElementById('range-fieldset-' + rangeId);
            
            checkbox.checked = !checkbox.checked;
            card.classList.toggle('selected', checkbox.checked);
            rangeFieldset.classList.toggle('hidden-range', !checkbox.checked);
        }

        // SELECT ALL RANGES
        function selectAllRanges() {
            document.querySelectorAll('.range-checkbox-modern').forEach(checkbox => checkbox.checked = true);
            document.querySelectorAll('.range-option-card').forEach(card => card.classList.add('selected'));
            document.querySelectorAll('.range-fieldset').forEach(fieldset => fieldset.classList.remove('hidden-range'));
        }

        // DESELECT ALL RANGES
        function deselectAllRanges() {
            document.querySelectorAll('.range-checkbox-modern').forEach(checkbox => checkbox.checked = false);
            document.querySelectorAll('.range-option-card').forEach(card => card.classList.remove('selected'));
            document.querySelectorAll('.range-fieldset').forEach(fieldset => fieldset.classList.add('hidden-range'));
        }

        // FILL SAMPLE DATA
        function fillSampleData() {
            const sampleData = ['X,9,8,7,6,5', '10,9,8,X,M,7', '9,8,7,6,5,4', 'X,X,9,8,7,6', '10,9,8,7,6,5', '9,8,7,X,10,M'];
            document.querySelectorAll('.end-input-modern').forEach(input => {
                input.value = sampleData[Math.floor(Math.random() * sampleData.length)];
            });
        }

        // INITIALIZE INITIAL STATE
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.range-checkbox-modern').forEach(checkbox => {
                const rangeId = checkbox.id.replace('range-', '');
                const rangeFieldset = document.getElementById('range-fieldset-' + rangeId);
                if (checkbox.checked) {
                    rangeFieldset.classList.remove('hidden-range');
                }
            });
        });
    </script>
</body>
</html>

<?php
// ACHIEVEMENT BADGES FUNCTION
function getAchievementBadges($total_score, $max_possible_score, $tens_count, $selected_ends_count, $average_score) {
    $badges = [];
    if ($total_score >= $max_possible_score * 0.83) {
        $badges[] = '🏆 Elite Archer';
    } elseif ($total_score >= $max_possible_score * 0.69) {
        $badges[] = '⭐ Advanced Shooter';
    }
    if ($tens_count >= $selected_ends_count * 3) {
        $badges[] = '🎯 Precision Master';
    }
    if ($average_score >= 8.0) {
        $badges[] = '🔥 Consistent Performer';
    }
    return $badges;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_score'])) {
    // Get data from form
    $user_id = $_POST['user_id'] ?? 0;
    $round_category_id = $_POST['round_category_id'] ?? 0;
    $bow_category_id = $_POST['bow_category_id'] ?? 0;
    $archer_category_id = $_POST['archer_category_id'] ?? 0;
    $date_recorded = $_POST['date_recorded'] ?? date('Y-m-d');
    $competition_id = $_POST['competition_id'] ?? NULL;
    $total_score = $_POST['total_score'] ?? 0;
    $note = $_POST['note'] ?? '';
    
    // Save to database
    $sql = "INSERT INTO scores (user_id, round_id, competition_id, archer_category_id, total_score, is_approved, date_recorded) 
            VALUES (?, ?, ?, ?, ?, 1, ?)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // Note: round_id here is the round_category_id from your form
        $stmt->bind_param("iisiis", $user_id, $round_category_id, $competition_id, $archer_category_id, $total_score, $date_recorded);
        
        if ($stmt->execute()) {
            echo "<div class='success-message'>✅ Score saved successfully!</div>";
            // You can redirect to management page
            // header("Location: archery_management.php?message=Score+saved+successfully");
            // exit();
        } else {
            echo "<div class='error-message'>❌ Error saving score: " . $conn->error . "</div>";
        }
        $stmt->close();
    } else {
        echo "<div class='error-message'>❌ SQL preparation error: " . $conn->error . "</div>";
    }
}
?>