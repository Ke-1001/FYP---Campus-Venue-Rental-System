<?php
// File: actions/process_booking.php
session_start();
require_once '../config/db.php';
require_once '../includes/booking_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $uid = $_SESSION['uid'] ?? null;
    if (!$uid) {
        echo json_encode(['status' => 'error', 'message' => 'Authorization Fault.']);
        exit;
    }

    $vid = intval($_POST['venue_id'] ?? 0);
    $date_booked = trim($_POST['booking_date'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    // 💡 嚴格對接前端送來的 end_time
    $end_time = trim($_POST['end_time'] ?? ''); 
    $purpose = htmlspecialchars(trim($_POST['purpose'] ?? ''));

    if ($vid === 0 || empty($date_booked) || empty($start_time) || empty($end_time)) {
        echo json_encode(['status' => 'error', 'message' => 'Data Integrity Fault: Missing temporal variables.']);
        exit;
    }

    // 💡 調用已更新為 time_end 架構的衝突檢測引擎
    if (checkTimeSlotConflict($conn, $vid, $date_booked, $start_time, $end_time)) {
        echo json_encode(['status' => 'error', 'message' => 'Temporal Conflict: Subsystem locked by existing schedule.']);
        exit;
    }

    $res_v = $conn->query("SELECT deposit FROM venue WHERE vid = $vid");
    $deposit = $res_v->fetch_assoc()['deposit'] ?? 0.00;

    $conn->begin_transaction();
    try {
        // 💡 執行寫入：寫入欄位切換為 time_end
        $sql = "INSERT INTO booking (uid, vid, date_booked, time_start, time_end, purpose, status, payment_status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissss", $uid, $vid, $date_booked, $start_time, $end_time, $purpose);
        $stmt->execute();
        
        $new_bid = $conn->insert_id;
        $stmt->close();
        $conn->commit();

        echo json_encode([
            'status' => 'success', 
            'redirect_url' => "../mock_payment.php?bid={$new_bid}&amount={$deposit}"
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'State Mutation Failed: ' . $e->getMessage()]);
    }
    
    $conn->close();
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Protocol Violation.']);
    exit;
}
?>