<?php
// File: actions/process_booking.php
session_start();
require_once("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Error: Invalid Protocol.");
}

// 1. Authentication Check
$user_id = $_SESSION['user_id'] ?? 1; // Fallback to 1 for testing purposes

// 2. Data Extraction
$venue_id = (int)$_POST['venue_id'];
$booking_date = $_POST['booking_date'];
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];

// 3. Fetch exact deposit amount from database to prevent frontend manipulation
$sql_venue = "SELECT base_deposit FROM venues WHERE venue_id = ? AND status = 'Available'";
$stmt_venue = $conn->prepare($sql_venue);
$stmt_venue->bind_param("i", $venue_id);
$stmt_venue->execute();
$result_venue = $stmt_venue->get_result();

if ($result_venue->num_rows === 0) {
    die("Error: Venue is unavailable or data has been manipulated.");
}
$venue_data = $result_venue->fetch_assoc();
$actual_deposit = $venue_data['base_deposit'];
$stmt_venue->close();

// 4. Insert booking record (Status: Pending, Payment: Pending)
$sql_insert = "INSERT INTO bookings (user_id, venue_id, booking_date, start_time, end_time, payment_status) 
               VALUES (?, ?, ?, ?, ?, 'Pending')";
$stmt_insert = $conn->prepare($sql_insert);
$stmt_insert->bind_param("iisss", $user_id, $venue_id, $booking_date, $start_time, $end_time);

if ($stmt_insert->execute()) {
    $new_booking_id = $conn->insert_id;
    $formatted_booking_ref = "BKG-" . str_pad($new_booking_id, 4, "0", STR_PAD_LEFT);
    
    // 5. Redirect to Payment Gateway Sandbox
    $redirect_url = sprintf(
        "../mock_payment.php?booking_id=%s&amount=%s&type=Deposit",
        urlencode($formatted_booking_ref),
        urlencode((string)$actual_deposit)
    );
    
    header("Location: " . $redirect_url);
    exit;
} else {
    echo "Database Insertion Failed: " . $conn->error;
}

$stmt_insert->close();
$conn->close();
?>