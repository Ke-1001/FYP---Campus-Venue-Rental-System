<?php
session_start();
require_once '../config/db.php';
require_once '../includes/booking_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Protocol Violation.']);
    exit;
}

$uid = $_SESSION['uid'] ?? null;
if (!$uid) {
    echo json_encode(['status' => 'error', 'message' => 'Authorization Fault.']);
    exit;
}

/* ✅ FIX 1: POST source */
$vid = $_POST['venue_id'] ?? '';

/* ❗ strict validation */
if (!preg_match('/^[A-Za-z0-9]+$/', $vid)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Venue Identifier.']);
    exit;
}

/* ✅ FIX 2: correct field names */
$date_booked = trim($_POST['booking_date'] ?? '');
$start_time = trim($_POST['start_time'] ?? '');
$end_time = trim($_POST['end_time'] ?? '');
$purpose = htmlspecialchars(trim($_POST['purpose'] ?? ''));

if (empty($vid) || empty($date_booked) || empty($start_time) || empty($end_time)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing temporal variables.']);
    exit;
}

/* conflict check safety */
try {
    if (checkTimeSlotConflict($conn, $vid, $date_booked, $start_time, $end_time)) {
        echo json_encode(['status' => 'error', 'message' => 'Temporal Conflict.']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Conflict engine failure.']);
    exit;
}

/* ✅ FIX 3: prepared statement */
$stmt = $conn->prepare("SELECT deposit FROM venue WHERE vid = ?");
$stmt->bind_param("s", $vid);
$stmt->execute();
$res_v = $stmt->get_result();
$deposit = $res_v->fetch_assoc()['deposit'] ?? 0.00;
$stmt->close();

$conn->begin_transaction();

try {
    $sql = "INSERT INTO booking 
        (uid, vid, date_booked, time_start, time_end, purpose, status, payment_status, payment_due_at)
        VALUES (?, ?, ?, ?, ?, ?, 'pending', 'unpaid', DATE_ADD(NOW(), INTERVAL 15 MINUTE))";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $uid, $vid, $date_booked, $start_time, $end_time, $purpose);
    $stmt->execute();

    $new_bid = $conn->insert_id;
    $stmt->close();

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'redirect_url' => "../User/mock_payment.php?bid={$new_bid}"
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'status' => 'error',
        'message' => 'State Mutation Failed'
    ]);
}

$conn->close();