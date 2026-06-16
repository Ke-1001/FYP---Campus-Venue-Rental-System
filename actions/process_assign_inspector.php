<?php
// This section checks and processes assign inspector requests.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
if (!in_array($_SESSION['role'] ?? '', ['admin', 'super_admin'], true))
{
    $_SESSION['toast'] =
    [
        'type' => 'error',
        'msg' => 'Access denied. Only admin can assign inspectors.'
    ];
    header("Location: ../admin/dashboard.php?error=access_denied");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $bid = intval($_POST['bid']);
    $sid_raw = $_POST['sid'];
    $conn->begin_transaction();
    try
    {
        $stmt_check_booking = $conn->prepare("SELECT status, date_booked, time_end FROM booking WHERE bid = ? FOR UPDATE");
        $stmt_check_booking->bind_param("i", $bid);
        $stmt_check_booking->execute();
        $b_res = $stmt_check_booking->get_result()->fetch_assoc();
        $stmt_check_booking->close();
        if (!$b_res || !in_array($b_res['status'], ['approved', 'completed']))
        {
            throw new Exception("State Fault: Booking {$bid} is not eligible for inspection.");
        }
        $end_timestamp = strtotime($b_res['date_booked'] . ' ' . $b_res['time_end']);
        $critical_timestamp = $end_timestamp - 300;
        if (time() >= $critical_timestamp)
        {
            throw new Exception("SLA Violation: Manual assignment threshold expired. System autopilot will engage.");
        }
        $stmt_check_ins = $conn->prepare("SELECT ins_id FROM inspection WHERE bid = ? LIMIT 1");
        $stmt_check_ins->bind_param("i", $bid);
        $stmt_check_ins->execute();
        if ($stmt_check_ins->get_result()->num_rows > 0)
        {
            $stmt_check_ins->close();
            throw new Exception("Mapping Fault: Booking {$bid} already possesses an active inspection node.");
        }
        $stmt_check_ins->close();
        if ($sid_raw === 'RA01')
        {
            $sql_ra01 =
            "
                SELECT s.sid, COALESCE(clash.clash_count, 0) AS load_weight FROM staff s
                LEFT JOIN (SELECT i.sid, COUNT(*) as clash_count FROM inspection i JOIN booking b ON i.bid = b.bid WHERE b.date_booked = ? AND b.time_end = ? AND i.ins_status = 'pending' GROUP BY i.sid) clash ON s.sid = clash.sid
                WHERE s.position = 'inspector' AND s.status = 'active'
                ORDER BY load_weight ASC, RAND()
                LIMIT 1
            ";
            $stmt_ra01 = $conn->prepare($sql_ra01);
            $stmt_ra01->bind_param("ss", $b_res['date_booked'], $b_res['time_end']);
            $stmt_ra01->execute();
            $res_ra01 = $stmt_ra01->get_result();
            if ($res_ra01->num_rows > 0)
            {
                $sid = $res_ra01->fetch_assoc()['sid'];
            }
            else
            {
                $stmt_ra01->close();
                throw new Exception("Resource Exhaustion: No active inspectors available in the pool.");
            }
            $stmt_ra01->close();
        }
        else
        {
            $sid = intval($sid_raw);
        }
        $sql = "INSERT INTO inspection (bid, sid, ins_status) VALUES (?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $bid, $sid);
        $stmt->execute();
        if ($stmt->affected_rows === 0)
        {
            throw new Exception("Database Fault: Inspection injection failed.");
        }
        $stmt->close();
        $conn->commit();
        $_SESSION['toast'] = ['type' => 'success', 'msg' => "Execution Success: Inspector ID {$sid} allocated to Booking {$bid}."];
    }
    catch (Exception $e)
    {
        $conn->rollback();
        $_SESSION['toast'] = ['type' => 'error', 'msg' => $e->getMessage()];
    }
    header("Location: ../admin/assign_inspector.php");
    exit;
}
