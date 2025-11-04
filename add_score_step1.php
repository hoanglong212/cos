<?php include 'connect.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm điểm - Bước 1</title>
    <!-- LIÊN KẾT ĐẾN CÁC TỆP CSS -->
    <link rel="stylesheet" href="style.css"> <!-- CSS chung -->
    <link rel="stylesheet" href="style_step1.css"> <!-- CSS riêng cho bước 1 -->
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
                    
                    <div class="form-group">
                        <label>Người bắn</label>
                        <select name="user_id" required>
                            <option value="">-- Chọn người bắn --</option>
                            <?php
                                $user_groups = [];
                                $users_result = $conn->query("SELECT user_id, first_name, last_name FROM user_table ORDER BY first_name, last_name");
                                while ($u = $users_result->fetch_assoc()) {
                                    $letter = strtoupper(substr($u['first_name'], 0, 1));
                                    if (!ctype_alpha($letter)) { $letter = '#'; }
                                    $user_groups[$letter][] = $u;
                                }
                                foreach ($user_groups as $letter => $users_in_group) {
                                    echo '<optgroup label="Nhóm ' . $letter . '">';
                                    foreach ($users_in_group as $user) {
                                        echo "<option value='" . htmlspecialchars($user['user_id']) . "'>" . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</option>";
                                    }
                                    echo '</optgroup>';
                                }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Round đã bắn</label>
                        <select name="round_category_id" required>
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

                    <div class="form-group">
                        <label>Dụng cụ đã dùng</label>
                        <select name="bow_category_id" required>
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
                        <input type="date" name="date_recorded" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Loại buổi bắn</label>
                        <select name="context" required id="context-select">
                            <option value="practice">🎯 Luyện tập</option>
                            <option value="competition">🏆 Thi đấu</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="competition-field">
                        <label>Mã cuộc thi (Competition ID)</label>
                        <input type="text" name="competition_id" placeholder="VD: comp001, tournament_2024...">
                    </div>
                    
                    <div class="form-group">
                        <label>Ghi chú thêm</label>
                        <textarea name="note" rows="3" placeholder="Nhập ghi chú về buổi bắn (thời tiết, cảm nhận, mục tiêu...)"></textarea>
                    </div>

                    <button type="submit" class="submit-btn">Tiếp tục đến Bước 2</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Xử lý hiển thị competition field với animation
        const contextSelect = document.getElementById('context-select');
        const competitionField = document.getElementById('competition-field');

        contextSelect.addEventListener('change', function() {
            if (this.value === 'competition') {
                competitionField.classList.add('show');
            } else {
                competitionField.classList.remove('show');
                document.querySelector('input[name="competition_id"]').value = '';
            }
        });

        // Kích hoạt sự kiện change khi trang load
        contextSelect.dispatchEvent(new Event('change'));

        // Hiệu ứng focus cho các input
        const inputs = document.querySelectorAll('select, input, textarea');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>