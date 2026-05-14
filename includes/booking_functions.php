<?php
// File: includes/booking_functions.php

function checkTimeSlotConflict($conn, $vid, $date_booked, $new_start_time, $new_end_time) {
    // 💡 驗證層 1：動態預約碰撞檢測 (Dynamic Booking Collision)
    // 數學定理：若兩區間 [S1, E1] 與 [S2, E2] 重疊，則必滿足 S1 < E2 且 E1 > S2
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
    
    // 若在 booking 表中發現衝突，直接拋出異常
    if ($res1['conflict_count'] > 0) {
        return true;
    }

    // 💡 驗證層 2：學術排他矩陣碰撞檢測 (Academic Exclusion Matrix Collision)
    // 利用 DAYNAME() 函數將絕對日期 (如 2026-05-12) 投影至週期性特徵空間 (如 'Tuesday')
    $sql2 = "SELECT COUNT(*) as conflict_count 
             FROM academic_schedule 
             WHERE vid = ? 
               AND day_of_week = DAYNAME(?) 
               AND (start_time < ? AND end_time > ?)";

    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("ssss", $vid, $date_booked, $new_end_time, $new_start_time);
    $stmt2->execute();
    $res2 = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();

    // 若在 academic_schedule 中發現衝突，同樣攔截
    return ($res2['conflict_count'] > 0);
}
?>