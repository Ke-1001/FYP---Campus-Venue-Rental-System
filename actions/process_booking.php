<?php
// 檔案路徑：actions/process_booking.php
session_start();
require_once("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("🚨 Invalid Protocol.");
}

// 1. 嚴格從 Session 獲取身分 (Zero-Trust Architecture)
// 假設使用者已登入，若未登入則導回
$user_id = $_SESSION['user_id'] ?? null; 
if (!$user_id) {
    die("🚨 Unauthorized Access.");
}

// 2. 獲取前端安全參數
$venue_id = (int)$_POST['venue_id'];
$booking_date = $_POST['booking_date'];
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];

// 3. 核心邏輯：從後端資料庫強制抓取真實金額 (防止前端篡改)
$sql_venue = "SELECT base_deposit FROM venues WHERE venue_id = ? AND status = 'Available'";
$stmt_venue = $conn->prepare($sql_venue);
$stmt_venue->bind_param("i", $venue_id);
$stmt_venue->execute();
$result_venue = $stmt_venue->get_result();

if ($result_venue->num_rows === 0) {
    die("🚨 Venue unavailable or manipulated.");
}
$venue_data = $result_venue->fetch_assoc();
$actual_deposit = $venue_data['base_deposit'];
$stmt_venue->close();

// 💡 4. 這裡應當插入我們提案中的「防衝突演算法 (Conflict Resolution Algorithm)」
// $R_{start} < E_{end} + 30\text{min} \land R_{end} > E_{start}
// (為了專注解決金流斷層，此處暫且略過驗證邏輯)

// 5. 將預約寫入資料庫，狀態強制設定為 'Unpaid'
$sql_insert = "INSERT INTO bookings (user_id, venue_id, booking_date, start_time, end_time, payment_status) 
               VALUES (?, ?, ?, ?, ?, 'Unpaid')";
$stmt_insert = $conn->prepare($sql_insert);
$stmt_insert->bind_param("iisss", $user_id, $venue_id, $booking_date, $start_time, $end_time);

if ($stmt_insert->execute()) {
    // 獲取剛剛生成的 booking_id
    $new_booking_id = $conn->insert_id;
    $formatted_booking_ref = "BKG-" . str_pad($new_booking_id, 4, "0", STR_PAD_LEFT);
    
    // 6. 狀態機轉移：發動 HTTP 302 導向至 Sandbox
    // 這裡就是解決你「無效請求支付」的核心！必須將變數綁在 URL 上。
    $redirect_url = sprintf(
        "../mock_payment.php?booking_id=%s&amount=%s&type=Deposit",
        urlencode($formatted_booking_ref),
        urlencode((string)$actual_deposit)
    );
    
    header("Location: " . $redirect_url);
    exit;
} else {
    echo "🚨 Database Insertion Failed: " . $conn->error;
}

$stmt_insert->close();
$conn->close();
?>