<?php
// File path: actions/process_approval.php

require_once '../config/db.php';

// Security check: ensure booking_id and action are received
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    die("Invalid request parameters!");
}

$booking_id = intval($_GET['id']);
$action = $_GET['action']; // 'approve' or 'reject'

// Start database transaction to ensure bookings and payments are updated synchronously
$conn->begin_transaction();

try {
    if ($action === 'approve') {
        // Action 1: Approve booking
        $sql = "UPDATE bookings SET booking_status = 'Approved' WHERE booking_id = ? AND booking_status = 'Pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        
        $msg = "Order #$booking_id has been successfully approved! This order will now appear in the 'Pending Inspection List'.";

    } elseif ($action === 'reject') {
        // Action 2: Reject booking
        $sql_booking = "UPDATE bookings SET booking_status = 'Rejected' WHERE booking_id = ? AND booking_status = 'Pending'";
        $stmt_booking = $conn->prepare($sql_booking);
        $stmt_booking->bind_param("i", $booking_id);
        $stmt_booking->execute();

        // Action 3: Refund deposit (update payments table)
        // Because the booking was rejected, the prepaid deposit must be refunded
        $sql_payment = "UPDATE payments SET payment_status = 'Refunded' WHERE booking_id = ?";
        $stmt_payment = $conn->prepare($sql_payment);
        $stmt_payment->bind_param("i", $booking_id);
        $stmt_payment->execute();

        $msg = "Order #$booking_id has been rejected, and the system has marked the deposit for refund.";
    } else {
        throw new Exception("Unknown action type.");
    }

    // Commit transaction
    $conn->commit();

    // Display success message and provide a return link
    echo "<div style='font-family: Arial; padding: 20px; text-align: center; margin-top: 50px;'>";
    echo "<h2>Processing Complete</h2>";
    echo "<p>{$msg}</p>";
    echo "<br><a href='../admin/manage_bookings.php' style='padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Return to Approval Center</a>";
    echo "</div>";

} catch (Exception $e) {
    $conn->rollback();
    echo "<p style='color:red;'>System error: " . $e->getMessage() . "</p>";
}

$conn->close();
?>