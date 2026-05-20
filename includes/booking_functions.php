<?php
// File: includes/booking_functions.php

function checkTimeSlotConflict($conn, $vid, $date_booked, $new_start_time, $new_end_time) {
    // 💡 驗證層 1：動態預約碰撞檢測 (Dynamic Booking Collision)
    $sql1 = "SELECT COUNT(*) as conflict_count 
            FROM booking 
            WHERE vid = ? 
              AND date_booked = ? 
              AND status IN ('pending', 'approved') 
              AND (time_start < ? AND time_end > ?)"; 
              
    $stmt1 = $conn->prepare($sql1);
    $stmt1->bind_param("ssss", $vid, $date_booked, $new_end_time, $new_start_time);
    $stmt1->execute();
    $res1 = $stmt1->get_result()->fetch_assoc();
    $stmt1->close(); 
    
    if ($res1['conflict_count'] > 0) return true;

    // 💡 驗證層 2：學術排他矩陣映射 (Semester-Mapped Exclusion Matrix)
    // 數學邏輯：只擷取「該預約日期所屬學期」的課表進行碰撞比對
    $sql2 = "SELECT COUNT(*) as conflict_count 
             FROM academic_schedule a
             JOIN semester_config s ON a.sem_id = s.sem_id
             WHERE a.vid = ? 
               AND ? BETWEEN s.start_date AND s.end_date
               AND a.day_of_week = DAYNAME(?) 
               AND (a.start_time < ? AND a.end_time > ?)";

    $stmt2 = $conn->prepare($sql2);
    // Bind: vid, date_booked, date_booked, new_end_time, new_start_time
    $stmt2->bind_param("sssss", $vid, $date_booked, $date_booked, $new_end_time, $new_start_time);
    $stmt2->execute();
    $res2 = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();

    return ($res2['conflict_count'] > 0);
}
?>