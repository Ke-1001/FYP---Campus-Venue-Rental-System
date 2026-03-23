<?php
// file path: actions/process_booking.php

require_once '../config/db.php';
require_once '../includes/booking_functions.php';

// Security check: Ensure this API can only be called via POST form submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method! Please submit the booking form through the official system.");
}

// Receive real dynamic data transmitted from the User end (frontend form)
// Note: After implementing the Login system in the future, $user_id should be retrieved from $_SESSION['user_id'] for security.
// For now, during the development handover phase, let teammates use a hidden input field to transmit user_id for testing.
$user_id = intval($_POST['user_id']);
$venue_id = intval($_POST['venue_id']);
$booking_date = $_POST['booking_date']; // format: YYYY-MM-DD
$start_time = $_POST['start_time'];     // format: HH:MM:SS or HH:MM
$end_time = $_POST['end_time'];         // format: HH:MM:SS or HH:MM
$deposit_to_pay = floatval($_POST['deposit_paid']); // format: decimal

echo "<h2>Processing booking request...</h2>";

// 3. Call conflict checking function
$is_conflict = checkTimeSlotConflict($conn, $venue_id, $booking_date, $start_time, $end_time);

if ($is_conflict) {
    die("<p style='color:red;'>Booking failed: The time slot is already booked by another student! Please choose a different time.</p>");
}

// 4. Start a database transaction
// Ensure that writes to both bookings and payments tables are successful
$conn->begin_transaction();

try {
    // --- Action A: Insert into bookings table ---
    $sql_booking = "INSERT INTO bookings (user_id, venue_id, booking_date, start_time, end_time, booking_status) 
                    VALUES (?, ?, ?, ?, ?, 'Pending')"; // Default status is Pending
    $stmt_booking = $conn->prepare($sql_booking);
    $stmt_booking->bind_param("iisss", $user_id, $venue_id, $booking_date, $start_time, $end_time);
    $stmt_booking->execute();
    
    // Get the newly inserted booking ID (this is essential because the payments table needs it!)

    $new_booking_id = $conn->insert_id; 

    // --- Action B: Insert into payments table (deposit record) --- [cite: 258]
    $sql_payment = "INSERT INTO payments (booking_id, deposit_paid, payment_status) 
                    VALUES (?, ?, 'Deposit_Held')"; // Status is Deposit Held
    $stmt_payment = $conn->prepare($sql_payment);
    $stmt_payment->bind_param("id", $new_booking_id, $deposit_to_pay); // i = int, d = double/decimal
    $stmt_payment->execute();

    // 5. If the above two actions are successful, commit (submit) to the database
    $conn->commit();
    
    echo "<p style='color:green;'>Booking successful! Your booking ID is: #$new_booking_id</p>";
    echo "<p>Deposit recorded successfully: RM " . number_format($deposit_to_pay, 2) . "</p>";

} catch (Exception $e) {
    // 6. If any error occurs, rollback , to prevent orphaned data
    $conn->rollback();
    echo "<p style='color:red;'>System error occurred, booking failed: " . $e->getMessage() . "</p>";
}

// Close the connection
$conn->close();
?>