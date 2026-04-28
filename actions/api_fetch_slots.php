<?php
// File path: actions/api_fetch_slots.php
header('Content-Type: application/json');
require_once '../config/db.php';

// 垃圾回收：清除 15 分鐘未付款的幽靈訂單
$conn->query("DELETE FROM booking WHERE payment_status = 'unpaid' AND created_at < (NOW() - INTERVAL 15 MINUTE)");

if (!isset($_GET['venue_id']) || !isset($_GET['date'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
    exit;
}

// 💡 強制型態轉換：vid 為整數
$vid = intval($_GET['venue_id']);
$date = $_GET['date'];

$sql = "SELECT time_start, duration 
        FROM booking 
        WHERE vid = ? 
        AND date_booked = ? 
        AND status IN ('pending', 'approved')";

$stmt = $conn->prepare($sql);
// 💡 綁定型態：i(vid), s(date)
$stmt->bind_param("is", $vid, $date);
$stmt->execute();
$result = $stmt->get_result();

$booked_slots = [];
while ($row = $result->fetch_assoc()) {
    $start_time_obj = new DateTime($row['time_start']);
    $end_time_obj = clone $start_time_obj;
    $end_time_obj->modify("+" . ((int)$row['duration'] + 30) . " minutes");
    
    $booked_slots[] = [
        'start' => $start_time_obj->format('H:i'), 
        'end' => $end_time_obj->format('H:i')
    ];
}
echo json_encode(['status' => 'success', 'date' => $date, 'blocked_vectors' => $booked_slots]);
?>