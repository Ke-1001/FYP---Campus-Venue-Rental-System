<?php
// File: actions/api_fetch_slots.php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

$venue_id = isset($_GET['venue_id']) ? intval($_GET['venue_id']) : 0;
$date = isset($_GET['date']) ? trim($_GET['date']) : '';

if ($venue_id === 0 || empty($date)) {
    echo json_encode(['status' => 'error', 'message' => 'Parameter Validation Fault.']);
    exit;
}

// 💡 提取當日有效訂單的時間端點
$sql = "SELECT time_start, time_end 
        FROM booking 
        WHERE vid = ? 
          AND date_booked = ? 
          AND status IN ('pending', 'approved', 'completed')";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $venue_id, $date);
$stmt->execute();
$result = $stmt->get_result();

$blocked_vectors = [];

while ($row = $result->fetch_assoc()) {
    $start = date('H:i', strtotime($row['time_start']));
    
    // 💡 企業級邏輯：為每個結束時間加上 30 分鐘的清潔緩衝 (Buffer)
    // 這確保了下一組人無法緊接著上一組的結束時間預約
    $end_with_buffer = date('H:i', strtotime($row['time_end'] . ' + 30 minutes'));
    
    $blocked_vectors[] = [
        'start' => $start,
        'end' => $end_with_buffer
    ];
}

$stmt->close();
$conn->close();

echo json_encode(['status' => 'success', 'blocked_vectors' => $blocked_vectors]);
?>