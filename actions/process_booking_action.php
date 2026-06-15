<?php
// File: actions/process_booking_action.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ∴ Vectorized Input Extraction
    $bids = $_POST['bids'] ?? [];
    $action = $_POST['action_type'] ?? ''; 
    $admin_id = intval($_SESSION['aid'] ?? $_SESSION['user_id'] ?? 0);

    // Strict validation: Require array topology and valid action states
    if (empty($bids) || !is_array($bids) || !in_array($action, ['approve', 'reject'], true) || $admin_id === 0) {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Protocol Violation: Invalid or Missing Action Identifiers.'];
        header("Location: ../admin/pending_requests.php");
        exit;
    }

    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    $new_payment_status = ($action === 'reject') ? 'refunded' : 'paid';

    $conn->begin_transaction();

    try {
        // Iterate over the vector space of requested booking IDs
        foreach ($bids as $raw_bid) {
            $bid = intval($raw_bid);
            if ($bid === 0) continue; 

            // 💡 1. Isolate and lock vector (FOR UPDATE)
            $stmt_target = $conn->prepare("SELECT vid, date_booked, time_start, time_end FROM booking WHERE bid = ? AND status = 'pending' AND payment_status = 'paid' FOR UPDATE");
            $stmt_target->bind_param("i", $bid);
            $stmt_target->execute();
            $target_booking = $stmt_target->get_result()->fetch_assoc();
            $stmt_target->close();

            if (!$target_booking) {
                throw new Exception("State Mutation Failed for BID #{$bid}: Record non-existent, unpaid, or already processed.");
            }

            // 💡 2. SLA Bound Calculation (T_curr < T_end - 5min)
            $end_timestamp = strtotime($target_booking['date_booked'] . ' ' . $target_booking['time_end']);
            $critical_timestamp = $end_timestamp - 300; 

            if (time() >= $critical_timestamp) {
                throw new Exception("SLA Violation for BID #{$bid}: Threshold expired. System locked for auto-cancellation.");
            }

            // 💡 3. Spatiotemporal Collision Detection (Only applicable for positive assertions)
            if ($action === 'approve') {
                $sql_conflict = "
                    SELECT bid FROM booking 
                    WHERE vid = ? 
                      AND date_booked = ? 
                      AND time_start < ? 
                      AND time_end > ? 
                      AND status = 'approved' 
                    LIMIT 1
                ";
                $stmt_conflict = $conn->prepare($sql_conflict);
                $stmt_conflict->bind_param(
                    "ssss", 
                    $target_booking['vid'], 
                    $target_booking['date_booked'], 
                    $target_booking['time_end'], 
                    $target_booking['time_start']
                );
                $stmt_conflict->execute();
                $conflict_exists = $stmt_conflict->get_result()->num_rows > 0;
                $stmt_conflict->close();

                if ($conflict_exists) {
                    throw new Exception("Spatiotemporal Collision for BID #{$bid}: Temporal vector overlaps with an active approved booking.");
                }

                // Re-check academic schedule conflict before approval
                $day_name = date('l', strtotime($target_booking['date_booked']));

                $sql_schedule_conflict = "
                    SELECT 1
                    FROM academic_schedule sch
                    JOIN semester_config sem ON sch.sem_id = sem.sem_id
                    WHERE sch.vid = ?
                    AND sch.day_of_week = ?
                    AND ? BETWEEN sem.start_date AND sem.end_date
                    AND sem.is_active = 1
                    AND sch.start_time < ?
                    AND sch.end_time > ?
                    LIMIT 1
                ";

                $stmt_schedule = $conn->prepare($sql_schedule_conflict);
                $stmt_schedule->bind_param(
                    "ssss",
                    $target_booking['vid'],
                    $day_name,
                    $target_booking['time_end'],
                    $target_booking['time_start']
                );
                $stmt_schedule->execute();
                $schedule_conflict_exists = $stmt_schedule->get_result()->num_rows > 0;
                $stmt_schedule->close();

                if ($schedule_conflict_exists) {
                    throw new Exception("Schedule Conflict for BID #{$bid}: Venue is no longer available due to academic schedule.");
                }
            }

            // 💡 4. Atomic Database Write
            $sql = "UPDATE booking SET status = ?, payment_status = ?, aid = ?, approve_date = NOW() WHERE bid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssii", $new_status, $new_payment_status, $admin_id, $bid);
            $stmt->execute();

            if ($stmt->affected_rows === 0) {
                throw new Exception("Execution Fault for BID #{$bid}: Database mutation unacknowledged.");
            }
            $stmt->close();
        }

        $conn->commit();
        
        $count = count($bids);
        $_SESSION['toast'] = ['type' => 'success', 'msg' => "Batch Execution Success: {$count} booking(s) have been " . strtoupper($new_status) . "."];

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Transaction Fault: ' . $e->getMessage()];
    }

    header("Location: ../admin/pending_requests.php");
    exit;
}

header("Location: ../admin/manage_bookings.php");
exit;