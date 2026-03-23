<?php
session_start();

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method!");
}

$booking_id_to_inspect = intval($_POST['booking_id']);
$inspection_status = $_POST['inspection_status']; // Good, Dirty, Damaged
$damage_desc = htmlspecialchars($_POST['damage_description']);
$assessed_penalty = floatval($_POST['assessed_penalty']);

$admin_id = $_SESSION['user_id'];

// Query the deposit amount and venue ID for this booking
// This is needed later to identify which venue to lock if damage is found
$sql_check = "SELECT p.deposit_paid, b.venue_id 
              FROM payments p 
              JOIN bookings b ON p.booking_id = b.booking_id 
              WHERE p.booking_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $booking_id_to_inspect);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows === 0) {
    die("<p style='color:red;'>Error: Booking not found or data anomaly.</p>");
}

$row = $result->fetch_assoc();
$deposit_paid = $row['deposit_paid']; 
$venue_id = $row['venue_id']; // get venue ID

// settle payment status based on assessed penalty
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
    // Action A: Insert into inspections table

    $sql_inspect = "INSERT INTO inspections (booking_id, inspected_by, inspection_status, damage_description, assessed_penalty) 
                    VALUES (?, ?, ?, ?, ?)";
    $stmt_inspect = $conn->prepare($sql_inspect);
    $stmt_inspect->bind_param("iissd", $booking_id_to_inspect, $admin_id, $inspection_status, $damage_desc, $assessed_penalty);
    $stmt_inspect->execute();

    // Action B: Update payments table

    $sql_payment = "UPDATE payments SET final_deduction = ?, payment_status = ? WHERE booking_id = ?";
    $stmt_payment = $conn->prepare($sql_payment);
    $stmt_payment->bind_param("dsi", $assessed_penalty, $payment_status, $booking_id_to_inspect);
    $stmt_payment->execute();

    // Action C: Update bookings table

    $sql_booking = "UPDATE bookings SET booking_status = 'Completed' WHERE booking_id = ?";
    $stmt_booking = $conn->prepare($sql_booking);
    $stmt_booking->bind_param("i", $booking_id_to_inspect);
    $stmt_booking->execute();

    // If inspection status is "Damaged", automatically set venue to maintenance and activate cascade cancellation
    // Distinguish between minor damage and major damage for subsequent actions
    $venue_status_msg = "";
    
    if ($inspection_status === 'Minor_Damage') {
        // Minor damage: warning only, no venue closure, no cancellation of subsequent bookings
        $venue_status_msg = "<li style='color:#856404; background-color:#fff3cd; padding:10px; border-radius:4px; margin-top:10px;'>
            <strong>Caution: Minor damage has been recorded and penalty deducted. Venue main functions are not affected, subsequent bookings will proceed normally.</strong></li>";
            
    } elseif ($inspection_status === 'Major_Damage') {
        // Major damage: activate venue closure and cascade cancellation mechanism
        
        // 1. Set venue status to Maintenance (this will block all future bookings for this venue until manually resolved by admin)
        $sql_venue = "UPDATE venues SET status = 'Maintenance' WHERE venue_id = ?";
        $stmt_venue = $conn->prepare($sql_venue);
        $stmt_venue->bind_param("i", $venue_id);
        $stmt_venue->execute();

        // 2. Find future affected bookings
        $sql_future = "SELECT booking_id FROM bookings 
                       WHERE venue_id = ? 
                       AND booking_status IN ('Pending', 'Approved')";
        $stmt_future = $conn->prepare($sql_future);
        $stmt_future->bind_param("i", $venue_id);
        $stmt_future->execute();
        $future_result = $stmt_future->get_result();
        
        $cancelled_count = 0;
        
        // 3. Execute cascade cancellation and refunds
        while ($future_row = $future_result->fetch_assoc()) {
            $f_bid = $future_row['booking_id'];
            $conn->query("UPDATE payments SET payment_status = 'Refunded', final_deduction = 0 WHERE booking_id = $f_bid");
            $conn->query("UPDATE bookings SET booking_status = 'Cancelled' WHERE booking_id = $f_bid");
            $cancelled_count++;
        }
        
        $venue_status_msg = "<li style='color:#721c24; background-color:#f8d7da; padding:10px; border-radius:4px; margin-top:10px;'>
            <strong>System Alert: Due to severe damage, the venue has been automatically switched to \"Under Maintenance\".</strong>";
            
        if ($cancelled_count > 0) {
            $venue_status_msg .= "<br><strong>Cascade Cancellation Activated: Automatically cancelled and fully refunded {$cancelled_count} subsequent bookings!</strong>";
        }
        $venue_status_msg .= "</li>";
    }


    $conn->commit();

    // Display alert message to admin
    echo "<div style='background-color:#d4edda; color:#155724; padding:20px; border-radius:5px; margin:20px 0; font-family:Arial;'>";
    echo "<h3>Inspection Complete! Booking #$booking_id_to_inspect has been finalized.</h3>";
    echo "<ul>";
    echo "<li>Deposit Paid: RM " . number_format($deposit_paid, 2) . "</li>";
    echo "<li>Assessed Penalty: RM " . number_format($assessed_penalty, 2) . "</li>";

    if ($payment_status == 'Outstanding_Balance') {
        $owed = $assessed_penalty - $deposit_paid;
        echo "<li style='color:red;'>Deposit insufficient for deduction! User needs to make up the outstanding amount: RM " . number_format($owed, 2) . "</li>";
    } else {
        $refund = $deposit_paid - $assessed_penalty;
        echo "<li style='color:blue;'>Refund amount to be returned to user: RM " . number_format($refund, 2) . "</li>";
    }
    
    // Display automatic venue lockout alert
    echo $venue_status_msg;
    
    echo "</ul>";
    echo "<a href='../admin/inspections.php' style='text-decoration:none; color:#0056b3; font-weight:bold;'>Return to Pending Inspections List</a>";
    echo "</div>";

} catch (Exception $e) {
    $conn->rollback();
    echo "<p style='color:red;'>System settlement failed: " . $e->getMessage() . "</p>";
}

$conn->close();
?>