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
    // 💡 向量提取 1：一般學生預約集
    $stmt1 = $conn->prepare("SELECT time_start, time_end FROM booking WHERE vid = ? AND date_booked = ? AND status IN ('pending', 'approved')");
    $stmt1->bind_param("ss", $vid, $date);
    $stmt1->execute();
    $res1 = $stmt1->get_result();
    while ($row = $res1->fetch_assoc()) {
        $blocked_vectors[] = [
            'start' => substr($row['time_start'], 0, 5),

            // Displayed booking time is stored normally.
            // But slot blocking includes 30 minutes inspection time.
            'end' => date("H:i", strtotime($row['time_end'] . " +30 minutes"))
        ];
    }
    $stmt1->close();

    // 💡 向量提取 2：特定學期的學術排他矩陣
    $sql2 = "SELECT a.start_time, a.end_time 
             FROM academic_schedule a
             JOIN semester_config s ON a.sem_id = s.sem_id
             WHERE a.vid = ? 
               AND ? BETWEEN s.start_date AND s.end_date
               AND a.day_of_week = DAYNAME(?)";
               
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("sss", $vid, $date, $date);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($row = $res2->fetch_assoc()) {
        $blocked_vectors[] = [
            'start' => substr($row['start_time'], 0, 5),
            'end' => substr($row['end_time'], 0, 5)
        ];
    }
    $stmt2->close();

    echo json_encode(['status' => 'success', 'blocked_vectors' => $blocked_vectors]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Query Anomaly: ' . $e->getMessage()]);
}
?>