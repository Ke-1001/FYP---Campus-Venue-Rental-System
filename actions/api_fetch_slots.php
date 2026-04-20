<?php
// File path: actions/api_fetch_slots.php
header('Content-Type: application/json');
require_once '../config/db.php';

// 💡 1. Automated Garbage Collection (Sweeper)
// Terminate abandoned allocations older than 15 minutes to free up venue nodes
$sweep_sql = "DELETE FROM bookings WHERE payment_status = 'Pending' AND created_at < (NOW() - INTERVAL 15 MINUTE)";
$conn->query($sweep_sql);

if (!isset($_GET['venue_id']) || !isset($_GET['date'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid temporal parameters.']);
    exit;
}

$venue_id = (int)$_GET['venue_id'];
$date = $_GET['date'];

$sql = "SELECT start_time, end_time 
        FROM bookings 
        WHERE venue_id = ? 
        AND booking_date = ? 
        AND booking_status IN ('Pending', 'Approved')";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $venue_id, $date);
$stmt->execute();
$result = $stmt->get_result();

$booked_slots = [];
while ($row = $result->fetch_assoc()) {
    $end_time_obj = new DateTime($row['end_time']);
    $end_time_obj->modify('+30 minutes');
    
    $booked_slots[] = [
        'start' => substr($row['start_time'], 0, 5), 
        'end' => $end_time_obj->format('H:i')
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    'status' => 'success',
    'date' => $date,
    'blocked_vectors' => $booked_slots
]);
?>