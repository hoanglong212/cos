<?php include 'connect.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Nhập điểm nhanh</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* CSS CƠ BẢN */
    body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
    h1 { text-align: center; }
    form { max-width: 700px; margin: 0 auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    label { display: block; margin-top: 15px; font-weight: bold; }
    input[type="text"], input[type="date"], select, textarea { width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
    button[type="submit"] { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 20px; }
    button[type="submit"]:disabled { background-color: #aaa; cursor: not-allowed; }
    
    /* CSS CHO LƯỚI ĐIỂM "GỌN GÀNG" */
    fieldset.range-group { border: 1px solid #ccc; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
    legend { font-weight: bold; padding: 0 10px; }
    .end-row {
        display: grid;
        grid-template-columns: 80px 1fr 120px; /* Nhãn | Ô Input | Tổng điểm (rộng hơn) */
        gap: 10px;
        align-items: center;
        margin-bottom: 8px;
    }
    .end-row label { margin-top: 0; }
    .end-input { font-family: 'Courier New', Courier, monospace; font-size: 1.1em; }
    .end-total { font-weight: bold; font-size: 1.0em; text-align: right; }
    #grand-total {
        text-align: right; font-size: 1.5em; font-weight: bold; 
        color: #dc3545; margin-top: 20px; padding-top: 10px; border-top: 2px solid #eee;
    }

    /* CSS CHO HƯỚNG DẪN */
    #toggle-help { font-size: 0.6em; text-decoration: none; font-weight: normal; color: #007bff; }
    #help-box {
        display:none; background: #fffbe6; border: 1px solid #ffe58f; 
        padding: 0 15px 15px 15px; border-radius: 8px; margin: 15px 0;
    }
    
    /* CSS CHO XÁC THỰC (VALIDATION) */
    .end-input.input-error {
        border: 2px solid #dc3545; /* Viền đỏ */
        background-color: #fdeeee;
    }
    .end-total.total-error { color: #dc3545; /* Chữ đỏ */ }
    .end-total.total-warning { color: #fd7e14; /* Chữ cam */ }
    .end-total.total-success { color: #28a745; /* Chữ xanh */ }
  </style>
</head>
<body>
  
  <h1>🎯 Nhập điểm nhanh 
      <a href="#" id="toggle-help">(?) Hướng dẫn</a>
  </h1>
  
  <div id="help-box" style="display:none; background: #fffbe6; border: 1px solid #ffe58f; padding: 15px; border-radius: 8px; margin: 15px 0;">
    
    <p style="margin:0; line-height: 1.6;">
        <strong>Cách nhập (cho mỗi End):</strong> Nhập 6 mũi tên, cách nhau bằng dấu phẩy (<code>,</code>).
        <br>
        Dùng <code>X</code> hoặc <code>10</code> cho 10 điểm. Dùng <code>M</code> hoặc <code>0</code> cho 0 điểm.
        <br>
        <strong>Ví dụ:</strong> <code>X,10,9,9,8,M</code> (Sẽ báo lỗi nếu thiếu/thừa hoặc sai ký tự).
        <br>
        <strong>Mẹo:</strong> Nhấn <code>Enter</code> để nhảy xuống End tiếp theo.
    </p>

</div>

  <form action="insert_score_fast.php" method="POST" id="score-form">
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
        <div>
            <label>Người bắn (User):</label>
            <select name="user_id" required>
                <option value="">-- Chọn --</option>
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
        <div>
            <label>Round Category:</label>
            <select name="round_category_id" id="round-category-select" required>
                <option value="">-- Chọn --</option>
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
        <div>
            <label>Equipment:</label>
            <select name="bow_category_id" required>
                <option value="">-- Chọn --</option>
                <?php
                $bows = $conn->query("SELECT bow_category_id, category_name FROM bow_category ORDER BY category_name");
                while ($b = $bows->fetch_assoc()) {
                    echo "<option value='" . htmlspecialchars($b['bow_category_id']) . "'>" . htmlspecialchars($b['category_name']) . "</option>";
                }
                ?>
            </select>
        </div>
        <div>
            <label>Ngày bắn:</label>
            <input type="date" name="date_recorded" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
    </div>
    
    <div id="scoring-grid-container" style="margin-top: 20px;"></div>
    
    <div id="grand-total">TỔNG ĐIỂM: 0</div>
    
    <input type="hidden" id="total-score-input" name="total_score" value="0">
    <button type="submit" id="submit-button" style="display:none;" disabled>Lưu điểm</button>
  </form>

  <script>
    // --- KHỐI JS CHO HƯỚNG DẪN ---
    document.getElementById('toggle-help').addEventListener('click', function(e) {
        e.preventDefault();
        var helpBox = document.getElementById('help-box');
        if (helpBox.style.display === 'none') {
            helpBox.style.display = 'block';
            this.textContent = '(Ẩn hướng dẫn)';
        } else {
            helpBox.style.display = 'none';
            this.textContent = '(?) Hướng dẫn';
        }
    });

    // --- KHỐI JS CHÍNH CHO VIỆC NHẬP ĐIỂM ---
    const gridContainer = document.getElementById('scoring-grid-container');
    const submitButton = document.getElementById('submit-button');

    document.getElementById('round-category-select').addEventListener('change', function() {
        const roundCatId = this.value;
        gridContainer.innerHTML = '';
        updateGrandTotal();
        submitButton.style.display = 'none';

        if (roundCatId) {
            fetch(`get_round_details.php?round_cat_id=${roundCatId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success || !data.ranges) {
                        throw new Error(data.message || 'Không thể tải chi tiết round.');
                    }
                    buildScoringGrid(data.ranges);
                    submitButton.style.display = 'block';
                    addInputListeners();
                })
                .catch(error => {
                    gridContainer.innerHTML = `<p style="color:red;">Lỗi: ${error.message}</p>`;
                });
        }
    });

    function buildScoringGrid(ranges) {
        let htmlToInsert = '';
        ranges.forEach(range => {
            htmlToInsert += `<fieldset class="range-group">`;
            htmlToInsert += `<legend>${range.range_name} (${range.num_ends} ends)</legend>`;
            for (let endNum = 1; endNum <= range.num_ends; endNum++) {
                htmlToInsert += `
                    <div class="end-row">
                        <label>End ${endNum}:</label>
                        <input 
                            type="text" 
                            name="scores[${range.range_id}][${endNum}]" 
                            class="end-input"
                            data-end-num="${endNum}"
                            data-range-id="${range.range_id}"
                            placeholder="10,9,X,8,7,M..." 
                            required 
                        />
                        <div class="end-total" id="total_r${range.range_id}_e${endNum}">Tổng: 0 (0/6)</div>
                    </div>
                `;
            }
            htmlToInsert += `</fieldset>`;
        });
        gridContainer.innerHTML = htmlToInsert;
    }

    function addInputListeners() {
        gridContainer.addEventListener('input', function(e) {
            if (e.target.classList.contains('end-input')) {
                // 1. Phân tích chuỗi nhập
                const inputString = e.target.value;
                const stats = calculateStringStats(inputString); // {total, count, valid}
                
                // 2. Cập nhật hiển thị "Tổng: X"
                const rangeId = e.target.dataset.rangeId;
                const endNum = e.target.dataset.endNum;
                const totalEl = document.getElementById(`total_r${rangeId}_e${endNum}`);
                
                totalEl.textContent = `Tổng: ${stats.total} (${stats.count}/6)`;
                
                // 3. Cập nhật CSS (Báo lỗi viền & chữ)
                totalEl.className = 'end-total'; // Reset
                e.target.classList.remove('input-error');

                if (!stats.valid) { // Ký tự lạ (vd: 'g')
                    totalEl.classList.add('total-error');
                    e.target.classList.add('input-error');
                    totalEl.textContent += " LỖI!";
                } else if (stats.count < 6) { // Đang nhập (thiếu)
                    totalEl.classList.add('total-warning');
                } else if (stats.count > 6) { // Nhập thừa
                    totalEl.classList.add('total-error');
                    e.target.classList.add('input-error');
                    totalEl.textContent += " LỖI!";
                } else { // 6/6 và hợp lệ
                    totalEl.classList.add('total-success');
                }
                
                // 4. Cập nhật tổng điểm và trạng thái nút Submit
                updateGrandTotal();
            }
        });

        // NÂNG CẤP 2: Điều hướng bằng phím Enter
        gridContainer.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.classList.contains('end-input')) {
                e.preventDefault(); // Ngăn form submit
                
                const allInputs = Array.from(gridContainer.querySelectorAll('.end-input'));
                const currentIndex = allInputs.indexOf(e.target);
                
                if (currentIndex < allInputs.length - 1) {
                    // Còn ô tiếp theo, nhảy tới
                    allInputs[currentIndex + 1].focus();
                } else {
                    // Hết ô, nhảy tới nút submit
                    submitButton.focus();
                }
            }
        });
    }
    
    /**
     * NÂNG CẤP 1: Hàm tính toán thông minh hơn
     * Trả về { total: 46, count: 6, valid: true }
     */
    function calculateStringStats(str) {
        let total = 0;
        let count = 0;
        let valid = true; // Giả sử hợp lệ
        
        if (str.trim() === '') {
            return { total: 0, count: 0, valid: true };
        }

        const arrows = str.split(',');
        
        arrows.forEach(arrow => {
            const val = arrow.trim().toUpperCase();
            if (val === '') return; // Bỏ qua dấu phẩy thừa (vd: 10,9,,8)
            
            count++;
            
            if (val === 'X' || val === '10') {
                total += 10;
            } else if (val === 'M' || val === '0') {
                total += 0;
            } else {
                const num = parseInt(val);
                if (!isNaN(num) && num >= 1 && num <= 9) {
                    total += num;
                } else {
                    valid = false; // Ký tự không hợp lệ
                }
            }
        });
        return { total, count, valid };
    }

    /**
     * NÂNG CẤP 1: Cập nhật tổng điểm VÀ khóa/mở nút Submit
     */
    function updateGrandTotal() {
        let grandTotal = 0;
        let isAllValid = true; // Giả sử tất cả hợp lệ
        
        const allEndInputs = document.querySelectorAll('.end-input');
        
        if (allEndInputs.length === 0) {
            isAllValid = false; // Chưa chọn round
        }

        allEndInputs.forEach(input => {
            const stats = calculateStringStats(input.value);
            grandTotal += stats.total;
            
            // Nếu MỘT ô không hợp lệ (sai ký tự, sai số lượng)
            if (!stats.valid || stats.count !== 6) {
                isAllValid = false;
            }
        });
        
        document.getElementById('grand-total').textContent = `TỔNG ĐIỂM: ${grandTotal}`;
        document.getElementById('total-score-input').value = grandTotal;
        
        // Khóa nút Submit nếu có bất kỳ lỗi nào
        submitButton.disabled = !isAllValid;
    }
  </script>
</body>
</html>