<?php
// File path: actions/process_booking.php
ob_start(); 
session_start();

function sendJson($status, $message, $extra = []) {
    $debug_output = ob_get_clean(); 
    header('Content-Type: application/json');
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit;
}

$is_ajax = isset($_POST['is_ajax']) && $_POST['is_ajax'] === 'true';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($is_ajax) sendJson('error', 'Invalid HTTP Protocol Vector.');
    die("Error: Invalid Protocol.");
}

require_once '../config/db.php';
require_once '../includes/booking_functions.php';

// 💡 1. 型態嚴格定義 (Strict Type Casting)
$uid = $_SESSION['uid'] ?? null; // 自然鍵：學號字串 (VARCHAR)
$vid = intval($_POST['venue_id'] ?? 0); // 代理鍵：整數 (INT)
$booking_date = $_POST['booking_date'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$purpose = htmlspecialchars(trim($_POST['purpose'] ?? ''));

if (!$vid || !$booking_date || !$start_time || !$end_time || !$uid) {
    if ($is_ajax) sendJson('error', 'Data Payload Incomplete.');
    die("Error: Data Payload Incomplete.");
}

// 💡 2. 時間向量推導 ($\Delta T$)
$start_dt = new DateTime($start_time);
$end_dt = new DateTime($end_time);
$duration_minutes = ($start_dt->diff($end_dt)->h * 60) + $start_dt->diff($end_dt)->i;

// 3. 衝突檢測
if (checkTimeSlotConflict($conn, $vid, $booking_date, $start_time, $end_time)) {
    if ($is_ajax) sendJson('error', 'Temporal Conflict detected.');
    die("Error: Temporal Conflict.");
}

// 4. 押金參數提取
$sql_venue = "SELECT deposit FROM venue WHERE vid = ? AND status = 'available'";
$stmt_v = $conn->prepare($sql_venue);
$stmt_v->bind_param("i", $vid);
$stmt_v->execute();
$actual_deposit = $stmt_v->get_result()->fetch_assoc()['deposit'] ?? 0;
$stmt_v->close();

// 💡 5. 寫入交易事務 (交由 DB AUTO_INCREMENT 處理 bid)
$sql_insert = "INSERT INTO booking (uid, vid, date_booked, time_start, duration, purpose, status, payment_status) 
               VALUES (?, ?, ?, ?, ?, ?, 'pending', 'unpaid')";
$stmt_insert = $conn->prepare($sql_insert);

// 綁定型態矩陣：s(uid) i(vid) s(date) s(time) i(duration) s(purpose)
$stmt_insert->bind_param("sisiss", $uid, $vid, $booking_date, $start_time, $duration_minutes, $purpose);

if ($stmt_insert->execute()) {
    // 💡 6. 提取原生純數字識別碼 (e.g., 20000001)
    $new_bid = $conn->insert_id;
    
    $redirect_url = sprintf(
        "../mock_payment.php?bid=%d&amount=%s&type=Deposit",
        $new_bid,
        urlencode((string)$actual_deposit)
    );
    
    if ($is_ajax) {
        sendJson('success', 'Execution Successful', ['redirect_url' => $redirect_url]);
    } else {
        header("Location: " . $redirect_url);
        exit;
    }
} else {
    if ($is_ajax) sendJson('error', 'Database Execution Fault: ' . $stmt_insert->error);
}
?>