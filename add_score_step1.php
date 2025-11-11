<?php include 'connect.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm điểm - Bước 1</title>

    <link rel="stylesheet" href="style1.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="main-wrapper">
        <div class="form-card">
            <div class="card-header">
                <h1>🎯 Thêm điểm mới</h1>
                <p>Bước 1: Thiết lập thông tin buổi bắn</p>
            </div>
            
            <div class="card-body">
                <form action="add_score_step2.php" method="POST">
                    <div class="form-section">
                        <div class="form-group">
                            <label>Người bắn</label>
                            <select name="user_id" id="user_id" required onchange="updateArcherCategory()" class="form-control">
                                <option value="">-- Chọn người bắn --</option>
                                <?php
                                    $user_groups = [];
                                    // Lấy thêm thông tin gender và date_of_birth để tính tuổi
                                    $users_result = $conn->query("SELECT user_id, first_name, last_name, gender, birthday FROM user_table ORDER BY first_name, last_name");
                                    while ($u = $users_result->fetch_assoc()) {
                                        $letter = strtoupper(substr($u['first_name'], 0, 1));
                                        if (!ctype_alpha($letter)) { $letter = '#'; }
                                        $user_groups[$letter][] = $u;
                                    }
                                    foreach ($user_groups as $letter => $users_in_group) {
                                        echo '<optgroup label="Nhóm ' . $letter . '">';
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
                            <label>Round đã bắn</label>
                            <select name="round_category_id" required class="form-control">
                                <option value="">-- Chọn round --</option>
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
                                            if ($label == 'Aussie') $group_label = 'Các Round của Úc';
                                            if ($label == 'Other') $group_label = 'Khác';
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
                            <label>Dụng cụ đã dùng</label>
                            <select name="bow_category_id" id="bow_category_id" required onchange="updateArcherCategory()" class="form-control">
                                <option value="">-- Chọn dụng cụ --</option>
                                <?php
                                    $bows = $conn->query("SELECT bow_category_id, category_name FROM bow_category ORDER BY category_name");
                                    while ($b = $bows->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($b['bow_category_id']) . "'>" . htmlspecialchars($b['category_name']) . "</option>";
                                    }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Ngày bắn</label>
                            <input type="date" name="date_recorded" value="<?php echo date('Y-m-d'); ?>" required class="form-control">
                        </div>
                    </div>

                    <!-- Hidden field để lưu archer_category_id -->
                    <input type="hidden" name="archer_category_id" id="archer_category_id" value="">
                    
                    <!-- Hiển thị thông tin category đã xác định -->
                    <div class="form-group" id="archer-category-display" style="display: none;">
                        <label>Phân loại cung thủ</label>
                        <div class="category-info" id="category-info">
                            <!-- Hiển thị thông tin category ở đây -->
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-group">
                            <label>Loại buổi bắn</label>
                            <select name="context" required id="context-select" class="form-control">
                                <option value="practice">🎯 Luyện tập</option>
                                <option value="competition">🏆 Thi đấu</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="competition-field">
                            <label>Cuộc thi</label>
                            <select name="competition_id" id="competition_id" class="form-control">
                                <option value="">-- Chọn cuộc thi --</option>
                                <?php
                                    // Lấy danh sách competitions từ database
                                    $competitions_result = $conn->query("SELECT competition_id, competition_name, start_date FROM competitions ORDER BY start_date DESC, competition_name");
                                    while ($comp = $competitions_result->fetch_assoc()) {
                                        $display_name = htmlspecialchars($comp['competition_name']) . " (" . htmlspecialchars($comp['competition_id']) . ") - " . htmlspecialchars($comp['start_date']);
                                        echo "<option value='" . htmlspecialchars($comp['competition_id']) . "'>" . $display_name . "</option>";
                                    }
                                ?>
                            </select>
                            <small class="form-text text-muted">Chọn cuộc thi từ danh sách có sẵn</small>
                        </div>
                    </div>
                    
                    <div class="form-group full-width ghi-chu-them">
                        <label>Ghi chú thêm</label>
                        <textarea name="note" rows="3" placeholder="Nhập ghi chú về buổi bắn (thời tiết, cảm nhận, mục tiêu...)" class="form-control"></textarea>
                    </div>

                    <button type="submit" class="submit-btn" id="submit-btn" disabled>Tiếp tục đến Bước 2</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function calculateAge(birthDate) {
            if (!birthDate) return 25; // Fallback nếu không có ngày sinh
            
            // Kiểm tra định dạng birthday (có thể là YYYY hoặc YYYY-MM-DD)
            let birthYear;
            if (birthDate.length === 4) {
                // Chỉ có năm
                birthYear = parseInt(birthDate);
                const today = new Date();
                return today.getFullYear() - birthYear;
            } else {
                // Có đầy đủ ngày tháng năm
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
            // Sửa thứ tự từ: "Female Open" → "Open Female"
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

            // Lấy thông tin user từ select option
            const userOption = document.querySelector(`#user_id option[value="${userId}"]`);
            const gender = userOption.getAttribute('data-gender');
            const dob = userOption.getAttribute('data-dob');
            
            console.log('User data:', { userId, gender, dob }); // Debug
            
            // Tính tuổi và age group
            const age = calculateAge(dob);
            const ageGroup = getAgeGroup(age);
            
            // Tạo age_and_gender_class với thứ tự đúng
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
            
            // Hiển thị thông tin tạm thời
            document.getElementById('category-info').innerHTML = `
                <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; border-left: 4px solid #ffc107;">
                    <strong>Đang xác định...</strong><br>
                    ${ageGenderClass} - ${bowName}<br>
                    <small>Tuổi: ${age}, Giới tính: ${gender}, Birthday: ${dob}</small>
                </div>
            `;
            categoryDisplay.style.display = 'block';
            submitBtn.disabled = true;
            
            // Gửi AJAX request để lấy archer_category_id
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
                                    <strong>✓ Đã xác định</strong><br>
                                    ${response.category_name}
                                </div>
                            `;
                            submitBtn.disabled = false;
                        } else {
                            let errorHtml = `<div style="background: #f8d7da; padding: 10px; border-radius: 5px; border-left: 4px solid #dc3545;">
                                <strong>✗ Lỗi</strong><br>
                                ${response.message}`;
                            
                            if (response.debug) {
                                errorHtml += `<br><small>Yêu cầu: ${response.debug.requested.age_gender_class} - ${response.debug.requested.bow_category_id}</small>`;
                                errorHtml += `<br><small>Có sẵn: ${JSON.stringify(response.debug.available)}</small>`;
                            }
                            errorHtml += `</div>`;
                            
                            document.getElementById('category-info').innerHTML = errorHtml;
                            submitBtn.disabled = true;
                        }
                    } catch (e) {
                        console.error('Lỗi parse JSON:', e, 'Response:', xhr.responseText);
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

        // Xử lý hiển thị competition field
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

        // Kích hoạt sự kiện change khi trang load
        toggleCompetitionField();
    </script>
</body>
</html>