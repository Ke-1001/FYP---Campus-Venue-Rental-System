<?php
// File path: actions/api_fetch_slots.php
header('Content-Type: application/json');
require_once '../config/db.php';

// 💡 1. 自動垃圾回收機制 (Automated Garbage Collection)
// 適配新架構：刪除超過 15 分鐘且狀態為 'unpaid' 的幽靈訂單
$sweep_sql = "DELETE FROM booking WHERE payment_status = 'unpaid' AND created_at < (NOW() - INTERVAL 15 MINUTE)";
$conn->query($sweep_sql);

// 驗證前端傳來的時間參數
if (!isset($_GET['venue_id']) || !isset($_GET['date'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid temporal parameters.']);
    exit;
}

// 💡 適配新架構：vid 為 VARCHAR，保留為字串處理
$vid = $_GET['venue_id'];
$date = $_GET['date'];

// 💡 2. 核心查詢：使用 vid, date_booked, time_start, duration，且狀態為 pending/approved
$sql = "SELECT time_start, duration 
        FROM booking 
        WHERE vid = ? 
        AND date_booked = ? 
        AND status IN ('pending', 'approved')";

$stmt = $conn->prepare($sql);
// 綁定型態改為 "ss" (String, String)
$stmt->bind_param("ss", $vid, $date);
$stmt->execute();
$result = $stmt->get_result();

$booked_slots = [];
while ($row = $result->fetch_assoc()) {
    // 解析起始時間
    $start_time_obj = new DateTime($row['time_start']);
    
    // 💡 3. 時間向量推導：End Time = Start Time + Duration (分鐘) + 30 分鐘緩衝期
    $end_time_obj = clone $start_time_obj;
    $total_buffer_minutes = (int)$row['duration'] + 30;
    $end_time_obj->modify("+{$total_buffer_minutes} minutes");
    
    $booked_slots[] = [
        'start' => $start_time_obj->format('H:i'), 
        'end' => $end_time_obj->format('H:i')
    ];
}

$stmt->close();
$conn->close();

// 回傳 JSON 給前端的 Time Grid 狀態機
echo json_encode([
    'status' => 'success',
    'date' => $date,
    'blocked_vectors' => $booked_slots
]);
?>