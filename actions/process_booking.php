<?php
// File path: actions/process_booking.php

// 1. Output Buffering to protect JSON payload integrity
ob_start(); 
session_start();

function sendJson($status, $message, $extra = []) {
    $debug_output = ob_get_clean(); 
    header('Content-Type: application/json');
    
    $payload = ['status' => $status, 'message' => $message];
    if (!empty($debug_output) && $status === 'error') {
        $payload['debug_trace'] = strip_tags($debug_output);
    }
    
    echo json_encode(array_merge($payload, $extra));
    exit;
}

$is_ajax = isset($_POST['is_ajax']) && $_POST['is_ajax'] === 'true';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($is_ajax) sendJson('error', 'Invalid HTTP Protocol Vector.');
    die("Error: Invalid Protocol.");
}

// 2. Smart Path Resolution
if (file_exists('../config/db.php')) {
    require_once '../config/db.php';
} else {
    if ($is_ajax) sendJson('error', 'Path Fault: Cannot locate config/db.php.');
    die("Path Fault: Cannot locate config/db.php");
}

if (file_exists('booking_functions.php')) {
    require_once 'booking_functions.php';
} elseif (file_exists('../includes/booking_functions.php')) {
    require_once '../includes/booking_functions.php';
} else {
    if ($is_ajax) sendJson('error', 'Path Fault: Cannot locate booking_functions.php module.');
    die("Path Fault: Cannot locate booking_functions.php module.");
}

// 3. Payload Extraction (適配新版 VARCHAR 鍵值)
// 💡 注意：這裡假設前端 User 登入時會將 uid 存入 $_SESSION['uid']。若你還在用 user_id，請稍後將 User 端登入邏輯對齊。
$uid = $_SESSION['uid'] ?? ($_SESSION['user_id'] ?? null); 
$vid = htmlspecialchars(trim($_POST['venue_id'] ?? ''));
$date_booked = $_POST['booking_date'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$purpose = htmlspecialchars(trim($_POST['purpose'] ?? ''));

if (!$vid || !$date_booked || !$start_time || !$end_time || !$uid) {
    if ($is_ajax) sendJson('error', 'Data Payload Incomplete or User Node missing.');
    die("Error: Data Payload Incomplete.");
}

// 💡 4. Temporal Duration Calculus (時間差計算)
// $\Delta T = T_{end} - T_{start}$
$start_dt = new DateTime($start_time);
$end_dt = new DateTime($end_time);
$interval = $start_dt->diff($end_dt);
$duration_minutes = ($interval->h * 60) + $interval->i;

if ($duration_minutes <= 0) {
    if ($is_ajax) sendJson('error', 'Temporal Anomaly: End time must be strictly greater than start time.');
    die("Error: Temporal Anomaly.");
}

// 5. Temporal Conflict Detection (使用新版函數)
if (checkTimeSlotConflict($conn, $vid, $date_booked, $start_time, $end_time)) {
    if ($is_ajax) {
        sendJson('error', 'Temporal Conflict: The requested vector overlaps with an existing reservation or its buffer time.');
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Temporal Conflict detected.'];
        header("Location: ../user/booking_form.php?venue_id=" . urlencode($vid));
        exit;
    }
}

// 6. Fetch Deposit Parameter (適配新版 venue 表)
$sql_venue = "SELECT deposit FROM venue WHERE vid = ? AND status = 'available'";
$stmt_venue = $conn->prepare($sql_venue);
$stmt_venue->bind_param("s", $vid);
$stmt_venue->execute();
$result_venue = $stmt_venue->get_result();

if ($result_venue->num_rows === 0) {
    if ($is_ajax) sendJson('error', 'Venue anomaly detected or infrastructure offline.');
    die("Error: Venue anomaly detected.");
}
$venue_data = $result_venue->fetch_assoc();
$actual_deposit = $venue_data['deposit'];
$stmt_venue->close();

// 💡 7. Generate Cryptographic Primary Key for `bid` (VARCHAR(12))
// 格式: BKG- + 8位隨機大寫字母與數字
$bid = 'BKG-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));

// 💡 8. Database Insertion (對齊 booking 表構型)
$sql_insert = "INSERT INTO booking (bid, uid, vid, date_booked, time_start, duration, purpose, status, payment_status) 
               VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid')";
$stmt_insert = $conn->prepare($sql_insert);

if (!$stmt_insert) {
    if ($is_ajax) sendJson('error', 'SQL Prepare Fault: ' . $conn->error);
    die("SQL Prepare Fault: " . $conn->error);
}

// "sssssis" -> 5 Strings, 1 Integer (duration), 1 String (purpose)
$stmt_insert->bind_param("sssssis", $bid, $uid, $vid, $date_booked, $start_time, $duration_minutes, $purpose);

if ($stmt_insert->execute()) {
    
    // 💡 路由轉向 mock_payment，傳遞新的 bid
    $redirect_url = sprintf(
        "../mock_payment.php?bid=%s&amount=%s&type=Deposit",
        urlencode($bid),
        urlencode((string)$actual_deposit)
    );
    
    if ($is_ajax) {
        sendJson('success', 'Execution Successful', [
            'booking_ref' => $bid,
            'redirect_url' => $redirect_url
        ]);
    } else {
        header("Location: " . $redirect_url);
        exit;
    }
} else {
    if ($is_ajax) sendJson('error', 'Database Execution Fault: ' . $stmt_insert->error);
    die("Database Fault: " . $stmt_insert->error);
}

$stmt_insert->close();
$conn->close();
?>