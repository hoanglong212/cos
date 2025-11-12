<?php
// insert_score.php
include 'connect.php';

// ENABLE ERROR DISPLAY FOR DEBUGGING
error_reporting(E_ALL);
ini_set('display_errors', 1);

// FUNCTION TO DISPLAY DETAILED ERRORS
function displayError($message, $details = '') {
    // Process error details for better display
    $details_html = "";
    if ($details) {
        $error_lines = explode("\n", $details);
        $formatted_errors = [];
        
        foreach ($error_lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                // Process error line for better display
                if (strpos($line, "•") === 0) {
                    $formatted_errors[] = $line;
                } else {
                    $formatted_errors[] = "• " . $line;
                }
            }
        }
        
        if (!empty($formatted_errors)) {
            $details_html = "
            <div class='error-details'>
                <div class='error-summary'>Error details:</div>
                <ul class='error-list'>
            ";
            
            // Limit display to first 10 errors to avoid being too long
            $display_errors = array_slice($formatted_errors, 0, 10);
            foreach ($display_errors as $error) {
                $clean_error = str_replace("•", "", $error); // Remove original • character
                $details_html .= "<li class='error-item'>• " . htmlspecialchars(trim($clean_error)) . "</li>";
            }
            
            // Notification if there are more errors
            if (count($formatted_errors) > 10) {
                $remaining = count($formatted_errors) - 10;
                $details_html .= "<li class='error-item-more'>... and $remaining other errors</li>";
            }
            
            $details_html .= "
                </ul>
            </div>
            ";
        }
    }
    
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Score Entry Error</title>
        <link rel='stylesheet' href='main.css'>
        <style>
            /* Additional styles for error page */
            .error-page-body { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0; 
                padding: 20px; 
                min-height: 100vh; 
                display: flex; 
                align-items: center; 
                justify-content: center;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .error-container {
                max-width: 800px;
                margin: 2rem auto;
                padding: 2rem;
                background: white;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
                border-left: 6px solid #e74c3c;
            }
            .error-header {
                display: flex;
                align-items: center;
                margin-bottom: 1.5rem;
                padding-bottom: 1rem;
                border-bottom: 2px solid #f8f9fa;
            }
            .error-icon {
                font-size: 2.5rem;
                color: #e74c3c;
                margin-right: 1rem;
            }
            .error-title {
                color: #e74c3c;
                font-size: 1.8rem;
                font-weight: 600;
                margin: 0;
            }
            .error-message {
                color: #495057;
                margin-bottom: 1.5rem;
                font-size: 1.1rem;
                line-height: 1.6;
            }
            .error-details {
                background: #f8f9fa;
                padding: 1.5rem;
                border-radius: 8px;
                margin-bottom: 1.5rem;
                border: 1px solid #e9ecef;
            }
            .error-summary {
                font-weight: 600;
                color: #495057;
                margin-bottom: 1rem;
                font-size: 1.1rem;
            }
            .error-list {
                list-style: none;
                padding: 0;
                margin: 0;
                max-height: 200px;
                overflow-y: auto;
            }
            .error-item {
                padding: 0.5rem 0;
                border-bottom: 1px solid #e9ecef;
                color: #6c757d;
                font-size: 0.95rem;
            }
            .error-item:last-child {
                border-bottom: none;
            }
            .error-item-more {
                padding: 0.5rem 0;
                color: #3498db;
                font-style: italic;
                text-align: center;
            }
            .error-tips {
                background: #e8f4fd;
                padding: 1.5rem;
                border-radius: 8px;
                margin-bottom: 2rem;
                border-left: 4px solid #3498db;
            }
            .tips-title {
                color: #2c3e50;
                font-weight: 600;
                margin-bottom: 1rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .tips-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            .tip-item {
                padding: 0.5rem 0;
                padding-left: 1.5rem;
                position: relative;
                color: #495057;
            }
            .tip-item:before {
                content: \"💡\";
                position: absolute;
                left: 0;
            }
            .error-actions {
                display: flex;
                gap: 1rem;
                justify-content: flex-end;
                flex-wrap: wrap;
            }
            .btn {
                padding: 0.75rem 1.5rem;
                border: none;
                border-radius: 6px;
                font-weight: 600;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                text-align: center;
                transition: all 0.3s ease;
                font-size: 0.95rem;
            }
            .btn-primary {
                background: #3498db;
                color: white;
            }
            .btn-primary:hover {
                background: #2980b9;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
            }
            .btn-secondary {
                background: #95a5a6;
                color: white;
            }
            .btn-secondary:hover {
                background: #7f8c8d;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(149, 165, 166, 0.3);
            }
            
            /* Scrollbar styling */
            .error-list::-webkit-scrollbar {
                width: 6px;
            }
            .error-list::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 3px;
            }
            .error-list::-webkit-scrollbar-thumb {
                background: #c1c1c1;
                border-radius: 3px;
            }
            .error-list::-webkit-scrollbar-thumb:hover {
                background: #a8a8a8;
            }
            
            /* Responsive */
            @media (max-width: 768px) {
                .error-page-body {
                    padding: 10px;
                }
                .error-container {
                    margin: 1rem;
                    padding: 1.5rem;
                }
                .error-header {
                    flex-direction: column;
                    text-align: center;
                    gap: 0.5rem;
                }
                .error-icon {
                    margin-right: 0;
                    margin-bottom: 0.5rem;
                }
                .error-actions {
                    flex-direction: column;
                }
                .btn {
                    width: 100%;
                }
            }
            
            /* Animation */
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            .error-container {
                animation: slideIn 0.3s ease-out;
            }
        </style>
    </head>
    <body class='error-page-body'>
        <div class='error-container'>
            <div class='error-header'>
                <div class='error-icon'>⚠️</div>
                <h1 class='error-title'>An Error Occurred</h1>
            </div>
            
            <div class='error-message'>
                " . htmlspecialchars($message) . "
            </div>
            
            " . $details_html . "
            
            <div class='error-tips'>
                <div class='tips-title'>
                    <span>💡</span>
                    Score Entry Tips:
                </div>
                <ul class='tips-list'>
                    <li class='tip-item'>Select complete shooter and round information</li>
                    <li class='tip-item'>Check each arrow score (0-10, X, M)</li>
                    <li class='tip-item'>Ensure archer classification and equipment are correctly selected</li>
                    <li class='tip-item'>If errors persist, contact administrator</li>
                </ul>
            </div>

            <div class='error-actions'>
                <a href='javascript:history.back()' class='btn btn-primary'>Back to Score Entry</a>
                <a href='homepage.html' class='btn btn-secondary'>Return to Homepage</a>
            </div>
        </div>
    </body>
    </html>
    ";
    exit;
}

// SUCCESS DISPLAY FUNCTION (keep same structure)
function displaySuccess($score_id, $total_score, $date_recorded, $context) {
    $score_type = ($context === 'competition') ? 'Competition' : 'Practice';
    
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Success</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
                margin: 0; padding: 20px; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            }
            .success-container { 
                background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); 
                max-width: 500px; text-align: center;
            }
            .success-icon { font-size: 4rem; margin-bottom: 20px; }
            .success-title { color: #27ae60; margin-bottom: 20px; }
            .success-message { color: #555; margin-bottom: 20px; }
            .score-info { 
                background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;
                text-align: left; border-left: 4px solid #27ae60;
            }
            .btn-group { margin: 25px 0; }
            .btn { 
                display: inline-block; padding: 12px 25px; margin: 5px; text-decoration: none; 
                border-radius: 8px; font-weight: bold; transition: all 0.3s; color: white;
            }
            .btn-primary { background: #3498db; }
            .btn-primary:hover { background: #2980b9; transform: translateY(-2px); }
            .btn-secondary { background: #95a5a6; }
            .btn-secondary:hover { background: #7f8c8d; }
        </style>
    </head>
    <body>
        <div class='success-container'>
            <div class='success-icon'>🎯</div>
            <h1 class='success-title'>Score Entry Successful!</h1>
            <div class='success-message'>
                Score has been saved to the system.
            </div>
            
            <div class='score-info'>
                <strong>Session ID:</strong> #$score_id<br>
                <strong>Total Score:</strong> $total_score<br>
                <strong>Date Recorded:</strong> $date_recorded<br>
                <strong>Score Type:</strong> $score_type
            </div>
            
            <div class='btn-group'>
                <a href='view_scores.php' class='btn btn-primary'>View All Scores</a>
                <a href='add_score_step1.php' class='btn btn-primary'>Enter New Score</a>
                <a href='homepage.html' class='btn btn-secondary'>Return to Homepage</a>
            </div>
        </div>
    </body>
    </html>
    ";
    exit;
}

// DETAILED SCORE DATA VALIDATION FUNCTION (keep same structure)
function validateScoreData($score_data) {
    $errors = [];
    
    if (empty($score_data)) {
        $errors[] = "No score data submitted";
        return $errors;
    }
    
    $total_valid_arrows = 0;
    
    foreach ($score_data as $range_id => $ends) {
        foreach ($ends as $end_num => $score_string) {
            $score_string = trim($score_string);
            
            // Check if this end is completely empty
            if (empty($score_string)) {
                $errors[] = "End $end_num (Range $range_id) is empty";
                continue;
            }
            
            $arrows = explode(',', $score_string);
            $arrow_count = 0;
            
            foreach ($arrows as $arrow_index => $arrow) {
                $arrow = strtoupper(trim($arrow));
                
                // Check score format
                if (!in_array($arrow, ['M', 'X', '10', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'])) {
                    $errors[] = "End $end_num, arrow " . ($arrow_index + 1) . ": '$arrow' is invalid (only accept 0-9, X, M)";
                    continue;
                }
                
                $arrow_count++;
                $total_valid_arrows++;
            }
            
            // Check number of arrows in end
            if ($arrow_count === 0) {
                $errors[] = "End $end_num (Range $range_id) has no valid arrows";
            }
        }
    }
    
    // Check total arrows
    if ($total_valid_arrows === 0) {
        $errors[] = "No valid arrows found in all ends";
    }
    
    return $errors;
}

// REMAINING CODE KEEPS THE SAME STRUCTURE
// ... (main execution and other functions keep same structure)

// MAIN EXECUTION
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    // DEBUG: Display all POST data
    error_log("=== FULL POST DATA ===");
    foreach ($_POST as $key => $value) {
        error_log("$key: " . (is_array($value) ? print_r($value, true) : $value));
    }

    // 1. Get data from form
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $round_category_id = isset($_POST['round_category_id']) ? intval($_POST['round_category_id']) : 0;
    $bow_category_id = isset($_POST['bow_category_id']) ? intval($_POST['bow_category_id']) : 0;
    $archer_category_id = isset($_POST['archer_category_id']) ? intval($_POST['archer_category_id']) : 0;
    $date_recorded = isset($_POST['date_recorded']) && $_POST['date_recorded'] !== '' ? $_POST['date_recorded'] : date('Y-m-d');
    $competition_id_input = isset($_POST['competition_id']) ? trim($_POST['competition_id']) : '';
    $note = isset($_POST['note']) ? trim($_POST['note']) : '';
    $context = isset($_POST['context']) ? trim($_POST['context']) : 'practice';

    // 2. STRICT BASIC DATA VALIDATION
    $basic_errors = [];
    
    if ($user_id <= 0) {
        $basic_errors[] = "Please select a shooter";
    }
    
    if ($round_category_id <= 0) {
        $basic_errors[] = "Please select a round";
    }

    if ($bow_category_id <= 0) {
        $basic_errors[] = "Please select equipment";
    }

    if ($archer_category_id <= 0) {
        $basic_errors[] = "Please select archer classification";
    }

    if (!empty($basic_errors)) {
        throw new Exception("Invalid basic data:\n• " . implode("\n• ", $basic_errors));
    }

    // 3. HANDLE COMPETITION/PRACTICE DISTINCTION
    if ($context === 'competition' && !empty($competition_id_input)) {
        $competition_id = $competition_id_input;
    } else {
        $competition_id = NULL;
    }

    // 4. COLLECT AND VALIDATE SCORE DATA
    $score_data = [];
    $total_score = 0;
    $tens_count = 0;
    $total_arrows = 0;

    // Only process scores[range][end] structure
    if (isset($_POST['scores']) && is_array($_POST['scores']) && !empty($_POST['scores'])) {
        $score_data = $_POST['scores'];
        error_log("Score data found: " . print_r($score_data, true));
        
        // DETAILED SCORE DATA VALIDATION
        $score_errors = validateScoreData($score_data);
        if (!empty($score_errors)) {
            throw new Exception("Invalid score data:\n• " . implode("\n• ", $score_errors));
        }
        
        // CALCULATE SCORE AFTER VALIDATION
        foreach ($score_data as $range_category_id => $ends) {
            foreach ($ends as $end_num => $score_string) {
                $score_string = trim($score_string);
                if (!empty($score_string)) {
                    $arrows = explode(',', $score_string);
                    
                    foreach ($arrows as $arrow) {
                        $arrow = strtoupper(trim($arrow));
                        $score = 0;
                        
                        if ($arrow === 'X' || $arrow === '10') {
                            $score = 10;
                            $tens_count++;
                        } elseif ($arrow === 'M') {
                            $score = 0;
                        } elseif (is_numeric($arrow) && $arrow >= 0 && $arrow <= 9) {
                            $score = (int)$arrow;
                        }
                        
                        $total_score += $score;
                        $total_arrows++;
                    }
                }
            }
        }
    } else {
        throw new Exception("No score data found. The form may not have been submitted correctly.");
    }

    // FINAL CHECK BEFORE SAVING
    if ($total_arrows === 0) {
        throw new Exception("No valid arrows to save. Please check the score data.");
    }

    if ($total_score === 0) {
        throw new Exception("Total score is 0. If this is correct, please confirm again.");
    }

    error_log("VALIDATION PASSED - Total arrows: $total_arrows, Total score: $total_score, Tens count: $tens_count");

    // 5. Determine round_id
    $round_id = $round_category_id;

    // 6. BEGIN TRANSACTION
    $conn->begin_transaction();

    try {
        // FINAL CHECK BEFORE INSERT
        if ($user_id <= 0 || $round_id <= 0 || $archer_category_id <= 0) {
            throw new Exception("Invalid data when preparing to save");
        }

        // STEP 1: Create record in scores table
        $sql_score = "INSERT INTO scores (user_id, round_id, competition_id, archer_category_id, total_score, is_approved) 
                      VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_score = $conn->prepare($sql_score);
        
        if (!$stmt_score) {
            throw new Exception("Error preparing scores statement: " . $conn->error);
        }
        
        $is_approved = 0; // Default pending approval
        
        $stmt_score->bind_param("iisiii", 
            $user_id, 
            $round_id, 
            $competition_id,
            $archer_category_id, 
            $total_score, 
            $is_approved
        );
        
        if (!$stmt_score->execute()) {
            throw new Exception("Error executing scores: " . $stmt_score->error);
        }
        
        $score_id = $conn->insert_id;
        $stmt_score->close();

        // STEP 2: Save additional information if score_metadata table exists
        $check_table_sql = "SHOW TABLES LIKE 'score_metadata'";
        $result = $conn->query($check_table_sql);
        if ($result->num_rows > 0) {
            $sql_metadata = "INSERT INTO score_metadata (score_id, date_recorded, bow_category_id, tens_count, total_arrows, note, context) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_metadata = $conn->prepare($sql_metadata);
            
            if ($stmt_metadata) {
                $stmt_metadata->bind_param("isiiiss", 
                    $score_id, 
                    $date_recorded, 
                    $bow_category_id, 
                    $tens_count, 
                    $total_arrows, 
                    $note, 
                    $context
                );
                if (!$stmt_metadata->execute()) {
                    throw new Exception("Error executing metadata: " . $stmt_metadata->error);
                }
                $stmt_metadata->close();
            }
        }

        // COMMIT transaction
        $conn->commit();
        
        // DISPLAY SUCCESS
        displaySuccess($score_id, $total_score, $date_recorded, $context);

    } catch (Exception $e) {
        // ROLLBACK if error occurs
        $conn->rollback();
        throw new Exception("Error saving to database: " . $e->getMessage());
    }

} catch (Exception $e) {
    displayError("Unable to save score", $e->getMessage());
}

if (isset($conn)) {
    $conn->close();
}