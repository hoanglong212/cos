<?php
// 1. PHẦN LOGIC: Tải dữ liệu trước
include 'connect.php';

$users_list = [];
$rounds_list = [];
$categories_list = []; // DANH SÁCH MỚI
$error_message = '';

// 1.1. Tải danh sách người bắn (users)
$users_result = $conn->query("SELECT user_id, first_name, last_name FROM user_table ORDER BY first_name, last_name");
if ($users_result) {
    while ($u = $users_result->fetch_assoc()) {
        $users_list[] = $u;
    }
} else {
    $error_message .= "Lỗi: không thể tải danh sách người bắn. " . $conn->error . "<br>";
}

// 1.2. Tải danh sách round
$rounds_result = $conn->query("SELECT r.round_id, rc.name AS round_name, r.location, r.round_date 
                               FROM rounds r
                               JOIN round_category rc ON r.round_category_id = rc.round_category_id
                               ORDER BY r.round_id DESC");
if ($rounds_result) {
    while ($r = $rounds_result->fetch_assoc()) {
        $rounds_list[] = $r;
    }
} else {
    $error_message .= "Lỗi: không thể tải danh sách round. " . $conn->error . "<br>";
}

// 1.3. Tải danh sách hạng mục (Archer Categories) - MỚI
$categories_result = $conn->query("SELECT archer_category_id, category_name FROM archer_category WHERE status = 'active' ORDER BY category_name");
if ($categories_result) {
    while ($c = $categories_result->fetch_assoc()) {
        $categories_list[] = $c;
    }
} else {
    $error_message .= "Lỗi: không thể tải danh sách hạng mục. " . $conn->error . "<br>";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nhập điểm mới (Nâng cao)</title>
  <link rel="stylesheet" href="style.css">
  
  <style>
    /* CSS cho form động */
    .mainboard {
        text-align: left; 
        max-width: 800px; /* Cần rộng hơn để chứa form điểm */
    }
    .mainboard h1 {
        text-align: center;
    }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
    .form-group input[type="text"],
    .form-group select {
      width: 100%; padding: 10px; box-sizing: border-box; 
      border: 1px solid #ccc; border-radius: 5px; font-size: 1rem;
    }
    .form-actions { text-align: right; margin-top: 20px; }
    .error-message {
      background-color: #f8d7da; color: #721c24; padding: 10px;
      border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 20px;
    }
    
    /* CSS cho form nhập điểm động */
    #score-entry-placeholder {
        margin-top: 20px;
        border-top: 2px solid #ccc;
        padding-top: 20px;
    }
    .range-fieldset {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        background: #fdfdfd;
    }
    .range-fieldset legend {
        font-size: 1.2em;
        font-weight: bold;
        color: #2e4ba4;
        padding: 0 10px;
    }
    .end-group {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }
    .end-group label {
        font-weight: bold;
        margin-right: 10px;
        min-width: 60px;
    }
    /* Các ô nhập điểm mũi tên */
    .arrow-input {
        width: 45px !important; /* Ghi đè */
        padding: 8px !important;
        text-align: center;
        margin-right: 5px;
        font-size: 0.9em !important;
        text-transform: uppercase;
    }
  </style>
</head>
<body>
  
  <div class="mainboard">
    <h1>🎯 Nhập điểm (Nâng cao)</h1>

    <?php if (!empty($error_message)): ?>
      <div class="error-message">
        <strong>Đã xảy ra lỗi khi tải dữ liệu:</strong><br>
        <?php echo $error_message; ?>
      </div>
    <?php endif; ?>

    <form action="insert_score_advanced.php" method="POST" id="score-form">
      
      <div class="form-group">
        <label for="user_id">Người bắn (User):</label>
        <select name="user_id" id="user_id" required>
          <option value="">-- Chọn --</option>
          <?php foreach ($users_list as $u): ?>
            <option value="<?php echo htmlspecialchars($u['user_id']); ?>">
              <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="archer_category_id">Hạng mục (Tuổi, Giới tính, Loại cung):</label>
        <select name="archer_category_id" id="archer_category_id" required>
          <option value="">-- Chọn hạng mục --</option>
          <?php foreach ($categories_list as $c): ?>
            <option value="<?php echo htmlspecialchars($c['archer_category_id']); ?>">
              <?php echo htmlspecialchars($c['category_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="round_id">Round (Chọn để tải form điểm):</label>
        <select name="round_id" id="round_id" required>
          <option value="">-- Chọn Round --</option>
          <?php foreach ($rounds_list as $r): ?>
            <?php
              $display_date = '';
              if (isset($r['round_date']) && !empty($r['round_date'])) {
                  $display_date = date(" (d/m/Y)", strtotime($r['round_date']));
              }
            ?>
            <option value="<?php echo htmlspecialchars($r['round_id']); ?>">
              <?php echo htmlspecialchars($r['round_name'] . ' - ' . $r['location'] . $display_date); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="competition_id">Competition ID (nếu có):</label>
        <input type="text" name="competition_id" id="competition_id" placeholder="VD: comp001">
      </div>

      <div id="score-entry-placeholder">
        <p style="text-align:center; color: #777;">Vui lòng chọn một Round để hiển thị ô nhập điểm.</p>
      </div>

      <div class="form-actions">
        <a href="index.php" class="btn btn-back">⬅ Quay lại</a>
        <button type="submit" class="btn" id="submit-button" disabled>Lưu điểm</button>
      </div>
    </form>
  </div> <script>
    // Lắng nghe sự kiện 'change' trên dropdown #round_id
    document.getElementById('round_id').addEventListener('change', function() {
        const roundId = this.value;
        const placeholder = document.getElementById('score-entry-placeholder');
        const submitButton = document.getElementById('submit-button');

        if (!roundId) {
            placeholder.innerHTML = '<p style="text-align:center; color: #777;">Vui lòng chọn một Round để hiển thị ô nhập điểm.</p>';
            submitButton.disabled = true;
            return;
        }

        // Hiển thị loading...
        placeholder.innerHTML = '<p style="text-align:center; color: #333;">Đang tải cấu trúc round...</p>';
        submitButton.disabled = true;

        // Gọi file get_round_details.php bằng AJAX (Fetch API)
        fetch('get_round_details.php?round_id=' + roundId)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(ranges => {
                // Xây dựng form HTML từ dữ liệu JSON
                buildScoreForm(ranges, placeholder);
                submitButton.disabled = false; // Bật nút submit
            })
            .catch(error => {
                console.error('Lỗi khi tải chi tiết round:', error);
                placeholder.innerHTML = '<p style="text-align:center; color: red;">Lỗi: Không thể tải được cấu trúc của round này.</p>';
                submitButton.disabled = true;
            });
    });

    /**
     * Hàm này xây dựng HTML cho form nhập điểm
     * @param {Array} ranges - Mảng các đối tượng range
     * @param {HTMLElement} placeholder - Nơi để chèn HTML
     */
    function buildScoreForm(ranges, placeholder) {
        let html = '';
        const ARROWS_PER_END = 6; [cite_start]// Theo brief [cite: 8]

        if (ranges.length === 0) {
             placeholder.innerHTML = '<p style="text-align:center; color: red;">Round này chưa được định nghĩa (không có cự ly nào).</p>';
             return;
        }

        // Lặp qua từng RANGE (cự ly)
        ranges.forEach((range, rangeIndex) => {
            // Dùng range_category_id làm key, rất quan trọng cho PHP
            const rangeKey = range.range_category_id; 

            html += `
                <fieldset class="range-fieldset">
                    <legend>${range.range_name} (${range.distance}m, ${range.number_of_ends} ends)</legend>
            `;

            // Lặp qua từng END (lượt bắn) trong range
            for (let end = 1; end <= range.number_of_ends; end++) {
                html += `<div class="end-group">`;
                html += `<label for="range_${rangeKey}_end_${end}">End ${end}:</label>`;
                
                // Lặp 6 lần để tạo 6 ô ARROW (mũi tên)
                for (let arrow = 1; arrow <= ARROWS_PER_END; arrow++) {
                    // Tên input rất quan trọng:
                    // PHP sẽ nhận được một mảng đa chiều
                    // vd: ranges[123][ends][1][arrows][1]
                    const inputName = `ranges[${rangeKey}][ends][${end}][arrows][${arrow}]`;
                    html += `
                        <input type="text" 
                               name="${inputName}"
                               id="range_${rangeKey}_end_${end}_arrow_${arrow}"
                               class="arrow-input" 
                               maxlength="2"
                               required
                               pattern="([0-9]|10|[XxMm])"
                               title="Nhập 0-10, X, hoặc M">
                    `;
                }
                html += `</div>`; // .end-group
            }
            html += `</fieldset>`; // .range-fieldset
        });

        placeholder.innerHTML = html;
    }
  </script>

</body>
</html>