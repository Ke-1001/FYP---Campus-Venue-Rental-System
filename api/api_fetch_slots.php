<?php
// File: api/api_fetch_slots.php
require_once '../config/db.php';
header('Content-Type: application/json');

$vid = $_GET['venue_id'] ?? '';
$date = $_GET['date'] ?? '';

if (empty($vid) || empty($date)) {
    echo json_encode(['status' => 'error', 'message' => 'Validation Fault: Missing parameter vectors.']);
    exit;
}

$blocked_vectors = [];

try {
    // 💡 向量提取 1：一般學生預約集 (Dynamic Bookings)
    $stmt1 = $conn->prepare("SELECT time_start, time_end FROM booking WHERE vid = ? AND date_booked = ? AND status IN ('pending', 'approved')");
    $stmt1->bind_param("ss", $vid, $date);
    $stmt1->execute();
    $res1 = $stmt1->get_result();
    while ($row = $res1->fetch_assoc()) {
        $blocked_vectors[] = [
            'start' => substr($row['time_start'], 0, 5),
            'end' => substr($row['time_end'], 0, 5)
        ];
    }
    $stmt1->close();

    // 💡 向量提取 2：學術排他矩陣 (Academic Exclusion Matrix)
    // 再次利用 DAYNAME() 提取該日期對應的固定課表
    $stmt2 = $conn->prepare("SELECT start_time, end_time FROM academic_schedule WHERE vid = ? AND day_of_week = DAYNAME(?)");
    $stmt2->bind_param("ss", $vid, $date);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($row = $res2->fetch_assoc()) {
        $blocked_vectors[] = [
            'start' => substr($row['start_time'], 0, 5),
            'end' => substr($row['end_time'], 0, 5)
        ];
    }
    $stmt2->close();

    // 將聯集結果回傳給 booking_form.php 的 JavaScript 渲染引擎
    echo json_encode([
        'status' => 'success',
        'blocked_vectors' => $blocked_vectors
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Query Anomaly: ' . $e->getMessage()]);
}
?>