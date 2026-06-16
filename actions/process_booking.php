<?php
// This section checks booking request details and saves the booking.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/booking_functions.php';
header('Content-Type: application/json');
function respond_json($status, $message = '', $extra = [])
{
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $extra));
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    respond_json('error', 'Invalid request method.');
}
if (!isset($_SESSION['uid']) || trim((string)$_SESSION['uid']) === '')
{
    respond_json('error', 'Please login before booking.');
}
if (!isset($_POST['agree_rules']) || $_POST['agree_rules'] !== '1')
{
    respond_json('error', 'You must read and agree to the Rules and Regulations before requesting a booking.');
}
$uid = trim((string)$_SESSION['uid']);
$vid = trim($_POST['vid'] ?? '');
$date_booked = trim($_POST['date_booked'] ?? '');
$start_time = trim($_POST['time_start'] ?? '');
$end_time = trim($_POST['time_end'] ?? '');
$purpose = trim($_POST['purpose'] ?? '');
if ($vid === '' || $date_booked === '' || $start_time === '' || $end_time === '' || $purpose === '')
{
    respond_json('error', 'Missing booking information. Please select date, time and purpose.');
}
if (!preg_match('/^[A-Za-z0-9_-]+$/', $vid))
{
    respond_json('error', 'Invalid venue ID.');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_booked))
{
    respond_json('error', 'Invalid booking date.');
}
if (!preg_match('/^\d{2}:\d{2}$/', $start_time) || !preg_match('/^\d{2}:\d{2}$/', $end_time))
{
    respond_json('error', 'Invalid booking time.');
}
if (strtotime($end_time) <= strtotime($start_time))
{
    respond_json('error', 'End time must be later than start time.');
}
$duration_minutes = (strtotime($end_time) - strtotime($start_time)) / 60;
if ($duration_minutes < 30)
{
    respond_json('error', 'Please select at least two time slots.');
}
if (strlen($purpose) > 100)
{
    respond_json('error', 'Booking purpose cannot exceed 100 characters.');
}
try
{
    $user_stmt = $conn->prepare("
        SELECT uid, account_status
        FROM user
        WHERE uid = ?
        LIMIT 1
    ");
    $user_stmt->bind_param("s", $uid);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    if (!$user_result || $user_result->num_rows === 0)
    {
        respond_json('error', 'Invalid user session. Please logout and login again.');
    }
    $user = $user_result->fetch_assoc();
    $user_stmt->close();
    if (strtolower($user['account_status']) !== 'active')
    {
        respond_json('error', 'Your account is not active for booking.');
    }
    $venue_stmt = $conn->prepare("
        SELECT vid, deposit, status
        FROM venue
        WHERE vid = ?
        LIMIT 1
    ");
    $venue_stmt->bind_param("s", $vid);
    $venue_stmt->execute();
    $venue_result = $venue_stmt->get_result();
    if (!$venue_result || $venue_result->num_rows === 0)
    {
        respond_json('error', 'Venue not found.');
    }
    $venue = $venue_result->fetch_assoc();
    $venue_stmt->close();
    if (strtolower($venue['status']) !== 'available')
    {
        respond_json('error', 'This venue is currently not available for booking.');
    }
    if (checkTimeSlotConflict($conn, $vid, $date_booked, $start_time, $end_time))
    {
        respond_json('error', 'This selected time conflicts with an existing booking, academic schedule, or inspection time.');
    }
    $conn->begin_transaction();
    $sql = "
        INSERT INTO booking
            (uid, vid, date_booked, time_start, time_end, purpose, status, payment_status, payment_due_at)
        VALUES
            (?, ?, ?, ?, ?, ?, 'pending', 'unpaid', DATE_ADD(NOW(), INTERVAL 15 MINUTE))
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $uid, $vid, $date_booked, $start_time, $end_time, $purpose);
    $stmt->execute();
    $new_bid = $conn->insert_id;
    $stmt->close();
    $conn->commit();
    respond_json('success', 'Booking request created successfully.', [
        'redirect_url' => 'mock_payment.php?bid=' . urlencode($new_bid)
    ]);
} catch (Throwable $e)
{
    if (isset($conn) && $conn instanceof mysqli)
    {
        try
        {
            $conn->rollback();
        } catch (Throwable $rollbackError)
        {
        }
    }
    respond_json('error', 'Booking failed: ' . $e->getMessage());
}
