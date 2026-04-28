<?php
// File: includes/booking_functions.php

function checkTimeSlotConflict($conn, $vid, $date_booked, $new_start_time, $new_end_time) {
    // 💡 數學定理：若兩區間 [S1, E1] 與 [S2, E2] 重疊，則必滿足 S1 < E2 且 E1 > S2
    $sql = "SELECT COUNT(*) as conflict_count 
            FROM booking 
            WHERE vid = ? 
              AND date_booked = ? 
              AND status IN ('pending', 'approved') 
              AND (
                  time_start < ? AND time_end > ?
              )"; 
              
    $stmt = $conn->prepare($sql);
    // 💡 對應順序：$new_end_time (E2) 比較 time_start (S1)；$new_start_time (S2) 比較 time_end (E1)
    $stmt->bind_param("isss", $vid, $date_booked, $new_end_time, $new_start_time);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close(); 
    
    return ($row['conflict_count'] > 0);
}
?>