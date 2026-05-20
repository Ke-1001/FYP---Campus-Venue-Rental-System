<?php
// File: actions/process_booking_action.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bid = intval($_POST['booking_id'] ?? 0);
    $action = $_POST['action_type'] ?? ''; // approve / reject
    $admin_id = intval($_SESSION['aid'] ?? $_SESSION['user_id'] ?? 0);

    if ($bid === 0 || !in_array($action, ['approve', 'reject'], true) || $admin_id === 0) {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Protocol Violation: Missing Action Identifiers.'];
        header("Location: ../admin/pending_requests.php");
        exit;
    }

    $new_status = ($action === 'approve') ? 'approved' : 'rejected';

    $conn->begin_transaction();

    try {
        // Record which admin reviewed the booking and when it was reviewed.
        // Only paid + pending bookings can be approved/rejected from this queue.
        $sql = "
            UPDATE booking
            SET status = ?, aid = ?, approve_date = NOW()
            WHERE bid = ?
              AND status = 'pending'
              AND payment_status = 'paid'
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $new_status, $admin_id, $bid);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            throw new Exception("State Mutation Failed: Booking record either non-existent, unpaid, or already processed.");
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
}

header("Location: ../admin/manage_bookings.php");
exit;
?>