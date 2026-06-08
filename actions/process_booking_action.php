<?php
// File: actions/process_booking_action.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bid = intval($_POST['booking_id'] ?? 0);
    $action = $_POST['action_type'] ?? ''; // approve / reject
    $admin_id = intval($_SESSION['aid'] ?? $_SESSION['user_id'] ?? 0);

    if ($bid === 0 || !in_array($action, ['approve', 'reject'], true) || $admin_id === 0) {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Protocol Violation: Missing Action Identifiers.'];
        header("Location: ../admin/pending_requests.php");
        exit;
    }

    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    // 資金流狀態向量同步
    $new_payment_status = ($action === 'reject') ? 'refunded' : 'paid';

    $conn->begin_transaction();

    try {
        // 💡 1. 提取時空向量並施加排他鎖 (FOR UPDATE) 阻斷併發競爭
        $stmt_target = $conn->prepare("SELECT vid, date_booked, time_start, time_end FROM booking WHERE bid = ? AND status = 'pending' AND payment_status = 'paid' FOR UPDATE");
        $stmt_target->bind_param("i", $bid);
        $stmt_target->execute();
        $target_booking = $stmt_target->get_result()->fetch_assoc();
        $stmt_target->close();

        if (!$target_booking) {
            throw new Exception("State Mutation Failed: Booking record either non-existent, unpaid, or already processed.");
        }

        // 💡 2. SLA 臨界時間防護 (T_curr < T_end - 5min)
        $end_timestamp = strtotime($target_booking['date_booked'] . ' ' . $target_booking['time_end']);
        $critical_timestamp = $end_timestamp - 300; // 提前 5 分鐘

        if (time() >= $critical_timestamp) {
            throw new Exception("SLA Violation: The threshold for manual intervention has expired (T < 5 mins to completion). The system has locked this vector for auto-cancellation.");
        }

        // 💡 3. 時空防撞校驗 (Spatiotemporal Collision Detection)
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
            // $time_end < target_start ∪ $time_start > target_end 的補集即為重疊
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
                throw new Exception("Spatiotemporal Collision: The requested temporal vector overlaps with an active approved booking.");
            }
        }

        // 💡 4. 執行狀態與資金流的原子化寫入
        $sql = "UPDATE booking SET status = ?, payment_status = ?, aid = ?, approve_date = NOW() WHERE bid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $new_status, $new_payment_status, $admin_id, $bid);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            throw new Exception("Execution Fault: Database mutation unacknowledged.");
        }

        $stmt->close();
        $conn->commit();

        $_SESSION['toast'] = ['type' => 'success', 'msg' => "Execution Success: Booking #{$bid} has been " . strtoupper($new_status) . "."];

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Transaction Fault: ' . $e->getMessage()];
    }

    header("Location: ../admin/pending_requests.php");
    exit;
}

header("Location: ../admin/manage_bookings.php");
exit;