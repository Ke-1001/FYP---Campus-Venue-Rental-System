<?php
// File path: actions/process_approval.php
session_start();
require_once '../config/db.php';

// Security check: ensure booking_id and action are received
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Invalid request parameters.'];
    header("Location: ../admin/manage_bookings.php");
    exit;
}

$booking_id = intval($_GET['id']);
$action = $_GET['action'];

$conn->begin_transaction();

try {
    if ($action === 'approve') {
        $sql = "UPDATE bookings SET booking_status = 'Approved' WHERE booking_id = ? AND booking_status = 'Pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        
        $_SESSION['toast'] = [
            'type' => 'success', 
            'msg' => "Execution Successful: Order #{$booking_id} has been approved."
        ];

    } elseif ($action === 'reject') {
        $sql_booking = "UPDATE bookings SET booking_status = 'Rejected' WHERE booking_id = ? AND booking_status = 'Pending'";
        $stmt_booking = $conn->prepare($sql_booking);
        $stmt_booking->bind_param("i", $booking_id);
        $stmt_booking->execute();

        $sql_payment = "UPDATE payments SET payment_status = 'Refunded' WHERE booking_id = ?";
        $stmt_payment = $conn->prepare($sql_payment);
        $stmt_payment->bind_param("i", $booking_id);
        $stmt_payment->execute();

        $_SESSION['toast'] = [
            'type' => 'success', 
            'msg' => "Execution Successful: Order #{$booking_id} rejected. Deposit refund initiated."
        ];
    } else {
        throw new Exception("Unknown action vector.");
    }

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['toast'] = [
        'type' => 'error', 
        'msg' => 'System error: ' . $e->getMessage()
    ];
}

$conn->close();
header("Location: ../admin/manage_bookings.php");
exit;
?>