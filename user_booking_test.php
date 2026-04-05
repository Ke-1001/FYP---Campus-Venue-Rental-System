<?php
// 檔案路徑：user_booking_test.php
session_start();
require_once 'config/db.php'; // 確保路徑正確

// 💡 為了測試方便，我們強制模擬一個「已登入的學生」身分
// 如果你的系統還沒做學生登入，這行能保證 API 不會報錯
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // 假設資料庫裡 user_id = 1 是一位普通學生
    $_SESSION['role'] = 'User';
}

// 動態抓取資料庫中「可租借 (Available)」的場地
$venues_query = "SELECT venue_id, venue_name, category, base_deposit FROM venues WHERE status = 'Available'";
$venues_result = $conn->query($venues_query);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>Student Booking Portal (Test Phase) - CVBMS</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .form-container { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 500px; border-top: 5px solid #28a745; }
        .header { text-align: center; margin-bottom: 25px; }
        .header h2 { margin: 0; color: #333; }
        .header p { color: #6c757d; font-size: 14px; margin-top: 5px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #495057; font-weight: bold; font-size: 14px; }
        select, input[type="date"], input[type="time"], textarea { width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 6px; box-sizing: border-box; font-size: 15px; }
        textarea { resize: vertical; height: 100px; }
        .btn-submit { width: 100%; padding: 14px; background-color: #28a745; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background-color: #218838; }
        .dev-badge { text-align: center; margin-top: 15px; font-size: 12px; color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>

<div class="form-container">
    <div class="header">
        <h2>📅 場地預約申請 (Venue Booking)</h2>
        <p>CVBMS Student Portal - Prototype</p>
    </div>

    <form action="actions/process_booking.php" method="POST" id="bookingForm">
        
        <div class="form-group">
            <label>選擇場地 (Select Venue):</label>
            <select name="venue_id" required>
                <option value="">-- 請選擇可用場地 --</option>
                <?php
                if ($venues_result && $venues_result->num_rows > 0) {
                    while ($row = $venues_result->fetch_assoc()) {
                        echo "<option value='" . $row['venue_id'] . "'>" . htmlspecialchars($row['venue_name']) . " (" . $row['category'] . ") - 押金 RM" . $row['base_deposit'] . "</option>";
                    }
                } else {
                    echo "<option value='' disabled>目前無可用場地</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label>預約日期 (Booking Date):</label>
            <input type="date" name="booking_date" min="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div style="display: flex; gap: 15px;">
            <div class="form-group" style="flex: 1;">
                <label>開始時間 (Start Time):</label>
                <input type="time" name="start_time" id="start_time" required>
            </div>
            <div class="form-group" style="flex: 1;">
                <label>結束時間 (End Time):</label>
                <input type="time" name="end_time" id="end_time" required onchange="validateTime()">
            </div>
        </div>

        <div class="form-group">
            <label>借用事由 (Purpose of Booking):</label>
            <textarea name="purpose" placeholder="請簡述您的活動內容..." required></textarea>
        </div>

        <button type="submit" class="btn-submit" id="submitBtn">送出預約申請 (Submit Request)</button>
    </form>

    <div class="dev-badge">
        ⚠️ 內部測試用端點 (Internal Test Stub)
    </div>
</div>

<script>
// 💡 前端時間邏輯防呆：結束時間必須大於開始時間
function validateTime() {
    const start = document.getElementById('start_time').value;
    const end = document.getElementById('end_time').value;
    const btn = document.getElementById('submitBtn');

    if (start && end) {
        if (end <= start) {
            alert('❌ 錯誤：結束時間必須晚於開始時間！');
            btn.disabled = true;
            btn.style.backgroundColor = '#6c757d';
        } else {
            btn.disabled = false;
            btn.style.backgroundColor = '#28a745';
        }
    }
}
</script>

</body>
</html>
<?php $conn->close(); ?>