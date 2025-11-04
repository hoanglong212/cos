<?php
// --- BƯỚC 1: BẬT HIỂN THỊ LỖI ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. PHẦN LOGIC: Tải dữ liệu trước
include 'connect.php';

$users_list = [];
$rounds_list = []; // Dữ liệu sẽ được nạp bởi câu SQL đã sửa
$categories_list = [];
$debug_message = "<h3>Trạng thái tải dữ liệu (Dropdown Cải tiến):</h3><ul>";
$loop_ran_check = false; // Biến kiểm tra vòng lặp

// 2.1. Tải 'user_table'
$users_result = $conn->query("SELECT user_id, first_name, last_name FROM user_table ORDER BY first_name, last_name");
if ($users_result) {
    while ($u = $users_result->fetch_assoc()) { $users_list[] = $u; }
    $debug_message .= "<li>✅ Tải 'user_table' thành công. Tìm thấy: <strong>" . count($users_list) . " người bắn</strong>.</li>";
} else {
    $debug_message .= "<li class='error'>❌ Lỗi khi tải 'user_table': " . $conn->error . "</li>";
}

// 2.2. Tải 'rounds' (Sử dụng LEFT JOIN)
$sql_rounds = "SELECT 
                    r.round_id, 
                    r.location, 
                    r.date_recorded,
                    rc.round_name 
                FROM 
                    rounds r
                LEFT JOIN 
                    round_category rc ON r.round_category_id = rc.round_category_id
                ORDER BY 
                    r.date_recorded DESC, r.location ASC";

$rounds_result = $conn->query($sql_rounds);
if ($rounds_result) {
    while ($r = $rounds_result->fetch_assoc()) { $rounds_list[] = $r; }
    $debug_message .= "<li>✅ Tải 'rounds' (với JOIN) thành công. Tìm thấy: <strong>" . count($rounds_list) . " round</strong>.</li>";
} else {
    $debug_message .= "<li class='error'>❌ Lỗi khi tải 'rounds': " . $conn->error . "</li>";
}

// 2.3. Tải 'archer_category'
$categories_result = $conn->query("SELECT archer_category_id, category_name FROM archer_category ORDER BY category_name");
if ($categories_result) {
    while ($c = $categories_result->fetch_assoc()) { $categories_list[] = $c; }
    $debug_message .= "<li>✅ Tải 'archer_category' thành công. Tìm thấy: <strong>" . count($categories_list) . " hạng mục</strong>.</li>";
} else {
    $debug_message .= "<li class='error'>❌ Lỗi khi tải 'archer_category': " . $conn->error . "</li>";
}

// --- BƯỚC 3: TẠO TRƯỚC HTML CHO DROPDOWN 'ROUNDS' ---
$rounds_dropdown_html = ""; // Khởi tạo chuỗi rỗng
try {
    if (isset($rounds_list) && is_array($rounds_list) && count($rounds_list) > 0) {
        foreach ($rounds_list as $r) {
            $loop_ran_check = true; // Đánh dấu là vòng lặp đã chạy
            
            $round_id_val = $r['round_id'];
            
            // Lấy giá trị, cung cấp giá trị mặc định nếu bị NULL
            $location_name = $r['location'] ?? 'Round không tên';
            $category_name = $r['round_name'] ?? 'CHƯA LIÊN KẾT'; // Tên từ bảng 'round_category'
            $date_str = $r['date_recorded'] ? date(" (d/m/Y)", strtotime($r['date_recorded'])) : '';

            // Ví dụ: "Perth (31/08/2025) [Định nghĩa: WA70/720]"
            $display_text = "{$location_name}{$date_str} [Định nghĩa: {$category_name}]";
            
            // Mã hóa an toàn
            $display_id_safe = htmlspecialchars($round_id_val, ENT_QUOTES, 'UTF-8');
            $display_name_safe = htmlspecialchars($display_text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            
            $rounds_dropdown_html .= "<option value=\"{$display_id_safe}\">{$display_name_safe}</option>\n";
        }
        $debug_message .= "<li>✅ Vòng lặp 'foreach' cho 'rounds' đã chạy thành công.</li>";
    }
} catch (Exception $e) {
    $rounds_dropdown_html = "<option value='' disabled>LỖI KHI TẠO: " . htmlspecialchars($e->getMessage()) . "</option>";
    $debug_message .= "<li class='error'>❌ Lỗi nghiêm trọng khi lặp mảng 'rounds': " . htmlspecialchars($e->getMessage()) . "</li>";
}

if ($loop_ran_check === false && count($rounds_list) > 0) {
    $debug_message .= "<li class='error'>❌ CẢNH BÁO: \$rounds_list có " . count($rounds_list) . " mục, nhưng vòng lặp foreach KHÔNG chạy.</li>";
}

$debug_message .= "</ul>"; // Đóng thẻ <ul> của debug
$conn->close();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nhập điểm mới (Cải tiến)</title>
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
    
    .debug-message {
      background-color: #e6f7ff; color: #0056b3; padding: 15px;
      border: 1px solid #b3e0ff; border-radius: 4px; margin-bottom: 20px; text-align: left;
    }
    .debug-message h3 { margin-top: 0; color: #004085; }
    .debug-message ul { margin: 0; padding-left: 20px; }
    .debug-message li { margin-bottom: 5px; }
    .debug-message li.error { color: #721c24; background: #f8d7da; padding: 2px 5px; list-style-type: none;}

    #score-entry-placeholder { margin-top: 20px; border-top: 2px solid #ccc; padding-top: 20px; }
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
    <h1>🎯 Nhập điểm (Cải tiến)</h1>

    <div class="debug-message">
      <?php echo $debug_message; ?>
    </div>
    
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
        <label for="round_id">Round (Phiên bản [Định nghĩa]):</label>
        <select name="round_id" id="round_id" required>
          <option value="">-- Chọn Round --</option>
          <?php
            // Echo chuỗi HTML đã tạo an toàn ở trên
            echo $rounds_dropdown_html; 
          ?>
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
    document.getElementById('round_id').addEventListener('change', function() {
        const roundId = this.value;
        const placeholder = document.getElementById('score-entry-placeholder');
        const submitButton = document.getElementById('submit-button');
        if (!roundId) {
            placeholder.innerHTML = '<p style="text-align:center; color: #777;">Vui lòng chọn một Round để hiển thị ô nhập điểm.</p>';
            submitButton.disabled = true;
            return;
        }
        placeholder.innerHTML = '<p style="text-align:center; color: #333;">Đang tải cấu trúc round...</p>';
        submitButton.disabled = true;
        
        fetch('get_round_details.php?round_id=' + roundId)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Lỗi máy chủ khi tải chi tiết round. Mã lỗi: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) { throw new Error(data.error); }
                buildScoreForm(data, placeholder);
                submitButton.disabled = false;
            })
            .catch(error => {
                console.error('Lỗi khi tải chi tiết round:', error);
                // Thêm thông báo lỗi chi tiết cho người dùng
                let errorMsg = error.message.includes('404') 
                    ? 'Lỗi: Không tìm thấy file (404). Đảm bảo file "get_round_details.php" tồn tại.'
                    : error.message;
                
                placeholder.innerHTML = '<p style="text-align:center; color: red;">' + errorMsg + '</p>';
                submitButton.disabled = true;
            });
    });

    function buildScoreForm(ranges, placeholder) {
        let html = '';
        const ARROWS_PER_END = 6; // Theo CSDL, 1 end = 6 arrows
        if (!ranges || ranges.length === 0) {
             placeholder.innerHTML = '<p style="text-align:center; color: red; font-weight: bold;">Lỗi: Round này chưa được định nghĩa (không có cự ly nào). Vui lòng dùng trang "Quản lý Cấu trúc Round" để định nghĩa nó.</p>';
             return;
        }
        // Lặp qua từng cự ly (range)
        ranges.forEach((range, rangeIndex) => {
            const rangeKey = range.range_category_id; 
            html += `<fieldset class="range-fieldset"><legend>${range.range_name} (${range.distance}m, ${range.number_of_ends} ends)</legend>`;
            // Lặp qua từng lượt (end)
            for (let end = 1; end <= range.number_of_ends; end++) {
                html += `<div class="end-group"><label for="range_${rangeKey}_end_${end}">End ${end}:</label>`;
                // Lặp 6 lần cho 6 mũi tên (arrow)
                for (let arrow = 1; arrow <= ARROWS_PER_END; arrow++) {
                    const inputName = `ranges[${rangeKey}][ends][${end}][arrows][${arrow}]`;
                    html += `<input type="text" name="${inputName}" id="range_${rangeKey}_end_${end}_arrow_${arrow}" class="arrow-input" maxlength="2" required pattern="([0-9]|10|[XxMm])" title="Nhập 0-10, X, hoặc M">`;
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