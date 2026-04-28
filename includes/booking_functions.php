<?php
// File: includes/booking_functions.php

/**
 * Check for Time Slot Conflicts (prevent double booking)
 *
 * @param mysqli $conn Database connection object
 * @param string $vid Venue ID (VARCHAR)
 * @param string $date_booked Booking date (YYYY-MM-DD)
 * @param string $new_start_time Booking start time (HH:MM:SS)
 * @param string $new_end_time Booking end time (HH:MM:SS)
 * @return bool Returns true if a conflict is found, false otherwise
 */
function checkTimeSlotConflict($conn, $vid, $date_booked, $new_start_time, $new_end_time) {
    // 💡 Logic: A conflict occurs if there exists a booking for the same venue and date where:
    // 1. existing booking's time_start < new booking's end_time
    // 2. AND existing booking's computed end_time (time_start + duration + 30 min buffer) > new booking's start_time
    
    $sql = "SELECT COUNT(*) as conflict_count 
            FROM booking 
            WHERE vid = ? 
              AND date_booked = ? 
              AND status IN ('pending', 'approved') 
              AND time_start < ? 
              AND ADDTIME(ADDTIME(time_start, SEC_TO_TIME(duration * 60)), '00:30:00') > ?"; 
              
    $stmt = $conn->prepare($sql);
    if (!$stmt) { 
        die("SQL Prepare Error: " . $conn->error); 
    }
    
    // 💡 Strict Type Binding: 's' (string) applies to all 4 parameters since `vid` is now VARCHAR
    $stmt->bind_param("ssss", $vid, $date_booked, $new_end_time, $new_start_time);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close(); 
    
    return ($row['conflict_count'] > 0);
}
?>