<?php
// File: actions/process_booking_action.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 💡 1. 接收行政決策參數 (不需要時間向量)
    $bid = intval($_POST['booking_id'] ?? 0);
    $action = $_POST['action_type'] ?? ''; // 'approve' 或 'reject'

    if ($bid === 0 || empty($action)) {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Protocol Violation: Missing Action Identifiers.'];
        header("Location: ../admin/pending_requests.php");
        exit;
    }

    // 💡 2. 映射狀態機突變量
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';

    $conn->begin_transaction();

    try {
        // 執行狀態更新
        $sql = "UPDATE booking SET status = ? WHERE bid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_status, $bid);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            throw new Exception("State Mutation Failed: Booking record either non-existent or already processed.");
        }

        $stmt->close();
        $conn->commit();

        $_SESSION['toast'] = [
            'type' => 'success', 
            'msg' => "Execution Success: Booking #{$bid} has been " . strtoupper($new_status) . "."
        ];

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Transaction Fault: ' . $e->getMessage()];
    }

    $conn->close();
    header("Location: ../admin/pending_requests.php");
    exit;
} else {
    header("Location: ../admin/manage_bookings.php");
    exit;
}
?>