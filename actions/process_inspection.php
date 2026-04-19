<?php
// File path: actions/process_inspection.php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Invalid request protocol.'];
    header("Location: ../admin/inspections.php");
    exit;
}

$booking_id_to_inspect = intval($_POST['booking_id']);
$inspection_status = $_POST['inspection_status']; // Good, Dirty, Minor_Damage, Major_Damage
$damage_desc = htmlspecialchars($_POST['damage_description']);
$assessed_penalty = floatval($_POST['assessed_penalty']);

$admin_id = $_SESSION['user_id'] ?? 1; // Fallback for testing, ensure session is valid

// Query the deposit amount and venue ID
$sql_check = "SELECT p.deposit_paid, b.venue_id FROM payments p JOIN bookings b ON p.booking_id = b.booking_id WHERE p.booking_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $booking_id_to_inspect);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows === 0) {
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Critical Error: Booking payload not found.'];
    header("Location: ../admin/inspections.php");
    exit;
}

$row = $result->fetch_assoc();
$deposit_paid = $row['deposit_paid']; 
$venue_id = $row['venue_id']; 

// Settle payment status
$payment_status = '';
if ($assessed_penalty == 0) {
    $payment_status = 'Refunded'; 
} elseif ($assessed_penalty <= $deposit_paid) {
    $payment_status = 'Settled';  
} else {
    $payment_status = 'Outstanding_Balance'; 
}

$conn->begin_transaction();

try {
    // Action A: Insert into inspections
    $sql_inspect = "INSERT INTO inspections (booking_id, inspected_by, inspection_status, damage_description, assessed_penalty) VALUES (?, ?, ?, ?, ?)";
    $stmt_inspect = $conn->prepare($sql_inspect);
    $stmt_inspect->bind_param("iissd", $booking_id_to_inspect, $admin_id, $inspection_status, $damage_desc, $assessed_penalty);
    $stmt_inspect->execute();

    // Action B: Update payments
    $sql_payment = "UPDATE payments SET final_deduction = ?, payment_status = ? WHERE booking_id = ?";
    $stmt_payment = $conn->prepare($sql_payment);
    $stmt_payment->bind_param("dsi", $assessed_penalty, $payment_status, $booking_id_to_inspect);
    $stmt_payment->execute();

    // Action C: Update bookings
    $sql_booking = "UPDATE bookings SET booking_status = 'Completed' WHERE booking_id = ?";
    $stmt_booking = $conn->prepare($sql_booking);
    $stmt_booking->bind_param("i", $booking_id_to_inspect);
    $stmt_booking->execute();

    $toast_msg = "Booking #{$booking_id_to_inspect} finalized. Penalty: RM " . number_format($assessed_penalty, 2) . ". ";

    // Major Damage handling
    if ($inspection_status === 'Major_Damage') {
        $sql_venue = "UPDATE venues SET status = 'Maintenance' WHERE venue_id = ?";
        $stmt_venue = $conn->prepare($sql_venue);
        $stmt_venue->bind_param("i", $venue_id);
        $stmt_venue->execute();

        $sql_future = "SELECT booking_id FROM bookings WHERE venue_id = ? AND booking_status IN ('Pending', 'Approved')";
        $stmt_future = $conn->prepare($sql_future);
        $stmt_future->bind_param("i", $venue_id);
        $stmt_future->execute();
        $future_result = $stmt_future->get_result();
        
        $cancelled_count = 0;
        while ($future_row = $future_result->fetch_assoc()) {
            $f_bid = $future_row['booking_id'];
            $conn->query("UPDATE payments SET payment_status = 'Refunded', final_deduction = 0 WHERE booking_id = $f_bid");
            $conn->query("UPDATE bookings SET booking_status = 'Cancelled' WHERE booking_id = $f_bid");
            $cancelled_count++;
        }
        $toast_msg .= "WARNING: Venue Locked. {$cancelled_count} future bookings cancelled.";
    }

    $conn->commit();
    $_SESSION['toast'] = ['type' => 'success', 'msg' => $toast_msg];

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'System settlement failed: ' . $e->getMessage()];
}

$conn->close();
header("Location: ../admin/inspections.php");
exit;
?>