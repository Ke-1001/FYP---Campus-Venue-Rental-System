<?php


/**
 * Check for Time Slot Conflicts (prevent double booking)
 *
 * @param mysqli $conn Database connection object
 * @param int $venue_id Venue ID
 * @param string $booking_date Booking date (YYYY-MM-DD)
 * @param string $new_start_time Booking start time (HH:MM:SS)
 * @param string $new_end_time Booking end time (HH:MM:SS)
 * @return bool Returns true if a conflict is found, false otherwise
 */


function checkTimeSlotConflict($conn, $venue_id, $booking_date, $new_start_time, $new_end_time) {
    // logic: A conflict occurs if there exists a booking for the same venue and date where:
    // existing booking's start_time < new booking's end_time
    $sql = "SELECT COUNT(*) as conflict_count 
            FROM bookings 
            WHERE venue_id = ? 
              AND booking_date = ? 
              AND booking_status IN ('Pending', 'Approved') 
              AND start_time < ? 
              AND ADDTIME(end_time, '00:30:00') > ?"; // add 30 minutes buffer time to end_time for conflict checking
              
    $stmt = $conn->prepare($sql);
    if (!$stmt) { die("SQL Prepare Error: " . $conn->error); }
    
    $stmt->bind_param("isss", $venue_id, $booking_date, $new_end_time, $new_start_time);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close(); 
    
    return ($row['conflict_count']