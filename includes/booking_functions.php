<?php

// This section provides shared booking functions logic or layout.
function expireUnpaidBookings($conn)
{
    $sql = "
        UPDATE booking
        SET
            status = 'cancelled',
            cancelled_at = NOW(),
            cancel_reason = 'Payment deadline expired'
        WHERE status = 'pending'
          AND payment_status = 'unpaid'
          AND payment_due_at IS NOT NULL
          AND payment_due_at < NOW()
    ";

    $conn->query($sql);
}

function checkTimeSlotConflict($conn, $vid, $date_booked, $new_start_time, $new_end_time)
{
    expireUnpaidBookings($conn);

    $new_end_with_inspection = date("H:i:s", strtotime($new_end_time . " +30 minutes"));

    $sql1 = "SELECT COUNT(*) as conflict_count
            FROM booking
            WHERE vid = ?
              AND date_booked = ?
              AND status IN ('pending', 'approved')
              AND (
                    time_start < ?
                    AND ADDTIME(time_end, '00:30:00') > ?
                  )";

    $stmt1 = $conn->prepare($sql1);
    $stmt1->bind_param("ssss", $vid, $date_booked, $new_end_with_inspection, $new_start_time);
    $stmt1->execute();
    $res1 = $stmt1->get_result()->fetch_assoc();
    $stmt1->close();

    if ($res1['conflict_count'] > 0)
    {
        return true;
    }

    $sql2 = "SELECT COUNT(*) as conflict_count
             FROM academic_schedule a
             JOIN semester_config s ON a.sem_id = s.sem_id
             WHERE a.vid = ?
               AND ? BETWEEN s.start_date AND s.end_date
               AND a.day_of_week = DAYNAME(?)
               AND (a.start_time < ? AND a.end_time > ?)";

    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("sssss", $vid, $date_booked, $date_booked, $new_end_with_inspection, $new_start_time);
    $stmt2->execute();
    $res2 = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();

    return ($res2['conflict_count'] > 0);
}

function syncCompletedBookings($conn)
{
    $sql = "
        UPDATE booking
        SET status = 'completed'
        WHERE status = 'approved'
          AND payment_status = 'paid'
          AND CONCAT(date_booked, ' ', time_end) <= NOW()
    ";

    return $conn->query($sql);
}
?>
