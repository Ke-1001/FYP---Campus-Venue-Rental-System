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

// 💡 2. Smart Path Resolution (Dynamic Dependency Injection)
// Resolve db.php
if (file_exists('../config/db.php')) {
    require_once '../config/db.php';
} else {
    if ($is_ajax) sendJson('error', 'Path Fault: Cannot locate config/db.php. Ensure script is in actions/ folder.');
    die("Path Fault: Cannot locate config/db.php");
}

// Resolve booking_functions.php (Scans multiple directories to prevent path errors)
if (file_exists('booking_functions.php')) {
    require_once 'booking_functions.php';
} elseif (file_exists('../includes/booking_functions.php')) {
    require_once '../includes/booking_functions.php';
} elseif (file_exists('../booking_functions.php')) {
    require_once '../booking_functions.php';
} else {
    if ($is_ajax) sendJson('error', 'Path Fault: Cannot locate booking_functions.php module.');
    die("Path Fault: Cannot locate booking_functions.php module.");
}

// 3. Payload Extraction
$user_id = $_SESSION['user_id'] ?? 1;
$venue_id = (int)($_POST['venue_id'] ?? 0);
$booking_date = $_POST['booking_date'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$purpose = htmlspecialchars(trim($_POST['purpose'] ?? ''));

if (!$venue_id || !$booking_date || !$start_time || !$end_time) {
    if ($is_ajax) sendJson('error', 'Data Payload Incomplete.');
    die("Error: Data Payload Incomplete.");
}

// 4. Temporal Conflict Detection
if (checkTimeSlotConflict($conn, $venue_id, $booking_date, $start_time, $end_time)) {
    if ($is_ajax) {
        sendJson('error', 'Temporal Conflict: The requested vector overlaps with an existing reservation or its buffer time.');
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Temporal Conflict detected.'];
        header("Location: ../user/booking_form.php?venue_id=" . $venue_id);
        exit;
    }
}

// 5. Fetch Deposit Parameter
$sql_venue = "SELECT base_deposit FROM venues WHERE venue_id = ? AND status = 'Available'";
$stmt_venue = $conn->prepare($sql_venue);
$stmt_venue->bind_param("i", $venue_id);
$stmt_venue->execute();
$result_venue = $stmt_venue->get_result();

if ($result_venue->num_rows === 0) {
    if ($is_ajax) sendJson('error', 'Venue anomaly detected or infrastructure offline.');
    die("Error: Venue anomaly detected.");
}
$venue_data = $result_venue->fetch_assoc();
$actual_deposit = $venue_data['base_deposit'];
$stmt_venue->close();

// 6. Database Insertion
$sql_insert = "INSERT INTO bookings (user_id, venue_id, booking_date, start_time, end_time, purpose, payment_status) 
               VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
$stmt_insert = $conn->prepare($sql_insert);

if (!$stmt_insert) {
    if ($is_ajax) sendJson('error', 'SQL Prepare Fault: ' . $conn->error);
    die("SQL Prepare Fault: " . $conn->error);
}

$stmt_insert->bind_param("iissss", $user_id, $venue_id, $booking_date, $start_time, $end_time, $purpose);

if ($stmt_insert->execute()) {
    $new_booking_id = $conn->insert_id;
    $formatted_booking_ref = "BKG-" . str_pad($new_booking_id, 4, "0", STR_PAD_LEFT);
    
    $redirect_url = sprintf(
        "../mock_payment.php?booking_id=%s&amount=%s&type=Deposit",
        urlencode($formatted_booking_ref),
        urlencode((string)$actual_deposit)
    );
    
    if ($is_ajax) {
        sendJson('success', 'Execution Successful', [
            'booking_ref' => $formatted_booking_ref,
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