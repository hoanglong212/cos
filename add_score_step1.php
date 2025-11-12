<?php include 'connect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Score - Step 1</title>
    <link rel="stylesheet" href="style1.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="main-wrapper">
        <div class="form-card">
            <div class="card-header">
                <h1>🎯 Add New Score</h1>
                <p>Step 1: Set Up Shooting Session Information</p>
            </div>
            
            <div class="card-body">
                <form action="add_score_step2.php" method="POST">
                    <div class="form-section">
                        <div class="form-group">
                            <label>Shooter</label>
                            <select name="user_id" id="user_id" required onchange="updateArcherCategory()" class="form-control">
                                <option value="">-- Select Shooter --</option>
                                <?php
                                    $user_groups = [];
                                    // Get user information including gender and date_of_birth to calculate age
                                    $users_result = $conn->query("SELECT user_id, first_name, last_name, gender, birthday FROM user_table ORDER BY first_name, last_name");
                                    while ($u = $users_result->fetch_assoc()) {
                                        $letter = strtoupper(substr($u['first_name'], 0, 1));
                                        if (!ctype_alpha($letter)) { $letter = '#'; }
                                        $user_groups[$letter][] = $u;
                                    }
                                    foreach ($user_groups as $letter => $users_in_group) {
                                        echo '<optgroup label="Group ' . $letter . '">';
                                        foreach ($users_in_group as $user) {
                                            echo "<option value='" . htmlspecialchars($user['user_id']) . "' 
                                                    data-gender='" . htmlspecialchars($user['gender']) . "'
                                                    data-dob='" . htmlspecialchars($user['birthday']) . "'>" . 
                                                    htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . 
                                                 "</option>";
                                        }
                                        echo '</optgroup>';
                                    }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Round Shot</label>
                            <select name="round_category_id" required class="form-control">
                                <option value="">-- Select Round --</option>
                                <?php
                                    $round_groups = ['WA' => [], 'AA' => [], 'Aussie' => [], 'Other' => []];
                                    $rounds_cat_result = $conn->query("SELECT round_category_id, round_name FROM round_category ORDER BY round_name");
                                    while ($rc = $rounds_cat_result->fetch_assoc()) {
                                        $name = $rc['round_name'];
                                        if (strpos($name, 'WA') === 0) { $round_groups['WA'][] = $rc; }
                                        elseif (strpos($name, 'AA') === 0) { $round_groups['AA'][] = $rc; }
                                        elseif (strpos($name, 'Sydney') !== false || strpos($name, 'Brisbane') !== false || strpos($name, 'Adelaide') !== false || strpos($name, 'Perth') !== false || strpos($name, 'Hobart') !== false) { $round_groups['Aussie'][] = $rc; }
                                        else { $round_groups['Other'][] = $rc; }
                                    }
                                    foreach ($round_groups as $label => $rounds_in_group) {
                                        if (!empty($rounds_in_group)) {
                                            $group_label = $label;
                                            if ($label == 'Aussie') $group_label = 'Australian Rounds';
                                            if ($label == 'Other') $group_label = 'Other';
                                            echo '<optgroup label="' . $group_label . '">';
                                            foreach ($rounds_in_group as $round) {
                                                echo "<option value='" . htmlspecialchars($round['round_category_id']) . "'>" . htmlspecialchars($round['round_name']) . "</option>";
                                            }
                                            echo '</optgroup>';
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-group">
                            <label>Equipment Used</label>
                            <select name="bow_category_id" id="bow_category_id" required onchange="updateArcherCategory()" class="form-control">
                                <option value="">-- Select Equipment --</option>
                                <?php
                                    $bows = $conn->query("SELECT bow_category_id, category_name FROM bow_category ORDER BY category_name");
                                    while ($b = $bows->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($b['bow_category_id']) . "'>" . htmlspecialchars($b['category_name']) . "</option>";
                                    }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Shooting Date</label>
                            <input type="date" name="date_recorded" value="<?php echo date('Y-m-d'); ?>" required class="form-control">
                        </div>
                    </div>

                    <!-- Hidden field to store archer_category_id -->
                    <input type="hidden" name="archer_category_id" id="archer_category_id" value="">
                    
                    <!-- Display determined category information -->
                    <div class="form-group" id="archer-category-display" style="display: none;">
                        <label>Archer Classification</label>
                        <div class="category-info" id="category-info">
                            <!-- Category information will be displayed here -->
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-group">
                            <label>Session Type</label>
                            <select name="context" required id="context-select" class="form-control">
                                <option value="practice">🎯 Practice</option>
                                <option value="competition">🏆 Competition</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="competition-field">
                            <label>Competition</label>
                            <select name="competition_id" id="competition_id" class="form-control">
                                <option value="">-- Select Competition --</option>
                                <?php
                                    // Get competitions list from database
                                    $competitions_result = $conn->query("SELECT competition_id, competition_name, start_date FROM competitions ORDER BY start_date DESC, competition_name");
                                    while ($comp = $competitions_result->fetch_assoc()) {
                                        $display_name = htmlspecialchars($comp['competition_name']) . " (" . htmlspecialchars($comp['competition_id']) . ") - " . htmlspecialchars($comp['start_date']);
                                        echo "<option value='" . htmlspecialchars($comp['competition_id']) . "'>" . $display_name . "</option>";
                                    }
                                ?>
                            </select>
                            <small class="form-text text-muted">Select a competition from the available list</small>
                        </div>
                    </div>
                    
                    <div class="form-group full-width ghi-chu-them">
                        <label>Additional Notes</label>
                        <textarea name="note" rows="3" placeholder="Enter notes about the shooting session (weather, feelings, goals...)" class="form-control"></textarea>
                    </div>

                    <button type="submit" class="submit-btn" id="submit-btn" disabled>Continue to Step 2</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function calculateAge(birthDate) {
            if (!birthDate) return 25; // Fallback if no birthdate
            
            // Check birthday format (could be YYYY or YYYY-MM-DD)
            let birthYear;
            if (birthDate.length === 4) {
                // Only year
                birthYear = parseInt(birthDate);
                const today = new Date();
                return today.getFullYear() - birthYear;
            } else {
                // Full date
                const today = new Date();
                const birth = new Date(birthDate);
                let age = today.getFullYear() - birth.getFullYear();
                const monthDiff = today.getMonth() - birth.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                    age--;
                }
                return age;
            }
        }

        function getAgeGroup(age) {
            if (age < 14) return 'Under 14';
            if (age < 16) return 'Under 16';
            if (age < 18) return 'Under 18';
            if (age < 21) return 'Under 21';
            if (age < 50) return 'Open';
            if (age < 60) return '50+';
            if (age < 70) return '60+';
            return '70+';
        }

        function formatAgeGenderClass(ageGroup, gender) {
            // Fix word order: "Female Open" → "Open Female"
            if (ageGroup === 'Open') {
                return gender + ' ' + ageGroup;
            } else {
                return ageGroup + ' ' + gender;
            }
        }

        function updateArcherCategory() {
            const userId = document.getElementById('user_id').value;
            const bowCategoryId = document.getElementById('bow_category_id').value;
            const submitBtn = document.getElementById('submit-btn');
            const categoryDisplay = document.getElementById('archer-category-display');
            
            if (!userId || !bowCategoryId) {
                categoryDisplay.style.display = 'none';
                submitBtn.disabled = true;
                document.getElementById('archer_category_id').value = '';
                return;
            }

            // Get user information from select option
            const userOption = document.querySelector(`#user_id option[value="${userId}"]`);
            const gender = userOption.getAttribute('data-gender');
            const dob = userOption.getAttribute('data-dob');
            
            console.log('User data:', { userId, gender, dob }); // Debug
            
            // Calculate age and age group
            const age = calculateAge(dob);
            const ageGroup = getAgeGroup(age);
            
            // Create age_and_gender_class with correct order
            const ageGenderClass = formatAgeGenderClass(ageGroup, gender);
            const bowName = document.querySelector(`#bow_category_id option[value="${bowCategoryId}"]`).textContent;
            
            console.log('Calculated:', { 
                age, 
                ageGroup, 
                ageGenderClass, 
                bowCategoryId,
                dob: dob,
                dobLength: dob ? dob.length : 'null'
            }); // Debug
            
            // Display temporary information
            document.getElementById('category-info').innerHTML = `
                <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; border-left: 4px solid #ffc107;">
                    <strong>Determining...</strong><br>
                    ${ageGenderClass} - ${bowName}<br>
                    <small>Age: ${age}, Gender: ${gender}, Birthday: ${dob}</small>
                </div>
            `;
            categoryDisplay.style.display = 'block';
            submitBtn.disabled = true;
            
            // Send AJAX request to get archer_category_id
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'get_archer_category.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                console.log('AJAX Response:', xhr.responseText); // Debug
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            document.getElementById('archer_category_id').value = response.archer_category_id;
                            document.getElementById('category-info').innerHTML = `
                                <div style="background: #e8f5e8; padding: 10px; border-radius: 5px; border-left: 4px solid #28a745;">
                                    <strong>✓ Determined</strong><br>
                                    ${response.category_name}
                                </div>
                            `;
                            submitBtn.disabled = false;
                        } else {
                            let errorHtml = `<div style="background: #f8d7da; padding: 10px; border-radius: 5px; border-left: 4px solid #dc3545;">
                                <strong>✗ Error</strong><br>
                                ${response.message}`;
                            
                            if (response.debug) {
                                errorHtml += `<br><small>Requested: ${response.debug.requested.age_gender_class} - ${response.debug.requested.bow_category_id}</small>`;
                                errorHtml += `<br><small>Available: ${JSON.stringify(response.debug.available)}</small>`;
                            }
                            errorHtml += `</div>`;
                            
                            document.getElementById('category-info').innerHTML = errorHtml;
                            submitBtn.disabled = true;
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e, 'Response:', xhr.responseText);
                        submitBtn.disabled = true;
                    }
                } else {
                    console.error('AJAX error:', xhr.status);
                    submitBtn.disabled = true;
                }
            };
            xhr.onerror = function() {
                console.error('AJAX request failed');
                submitBtn.disabled = true;
            };
            
            xhr.send(`age_gender_class=${encodeURIComponent(ageGenderClass)}&bow_category_id=${bowCategoryId}`);
        }

        // Handle competition field display
        const contextSelect = document.getElementById('context-select');
        const competitionField = document.getElementById('competition-field');

        function toggleCompetitionField() {
            if (contextSelect.value === 'competition') {
                competitionField.style.maxHeight = '200px';
                competitionField.style.opacity = '1';
                competitionField.style.marginBottom = '25px';
                document.getElementById('competition_id').required = true;
            } else {
                competitionField.style.maxHeight = '0';
                competitionField.style.opacity = '0';
                competitionField.style.marginBottom = '0';
                document.getElementById('competition_id').value = '';
                document.getElementById('competition_id').required = false;
            }
        }

        contextSelect.addEventListener('change', toggleCompetitionField);

        // Trigger change event when page loads
        toggleCompetitionField();
    </script>
</body>
</html>