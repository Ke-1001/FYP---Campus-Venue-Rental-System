<?php
// This section checks inspection results and updates booking-related records.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{

    $bid = intval($_POST['bid'] ?? 0);
    $ins_status = $_POST['ins_status'] ?? '';
    $damage_desc = trim($_POST['damage_desc'] ?? '');
    $penalty = floatval($_POST['penalty'] ?? 0.00);

    if ($bid === 0)
    {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => "Protocol Violation: Missing Booking Identifier."];
        header("Location: ../admin/pending_inspections.php");
        exit;
    }


    if ($ins_status === 'passed')
    {
        $damage_desc = 'No damage. Venue is in standard condition.';
        $penalty = 0.00;
    }

    $conn->begin_transaction();

    try
    {

        $sql_curr_info = "SELECT b.vid, b.uid, v.deposit, CONCAT(b.date_booked, ' ', b.time_start) AS start_datetime, CONCAT(b.date_booked, ' ', b.time_end) AS end_datetime
                          FROM booking b
                          JOIN venue v ON b.vid = v.vid
                          WHERE b.bid = ?";
        $stmt_curr = $conn->prepare($sql_curr_info);
        $stmt_curr->bind_param("i", $bid);
        $stmt_curr->execute();
        $curr_meta = $stmt_curr->get_result()->fetch_assoc();
        $stmt_curr->close();

        if (!$curr_meta)
        {
            throw new Exception("State Mutation Rejected: Booking data mapping failed.");
        }

        $vid = $curr_meta['vid'];
        $curr_end_timestamp = strtotime($curr_meta['end_datetime']);


        if (time() > $curr_end_timestamp + 1800)
        {
            throw new Exception("SLA Violation: The 30-minute inspection window for this booking has expired. Action denied.");
        }


        $sql_overdue = "SELECT i.ins_id, b.bid
                        FROM inspection i
                        JOIN booking b ON i.bid = b.bid
                        WHERE b.vid = ? AND i.ins_status = 'overdue'
                          AND CAST(CONCAT(b.date_booked, ' ', b.time_end) AS DATETIME) <= CAST(? AS DATETIME)
                        FOR UPDATE";
        $stmt_overdue = $conn->prepare($sql_overdue);
        $stmt_overdue->bind_param("ss", $vid, $curr_meta['start_datetime']);
        $stmt_overdue->execute();
        $res_overdue = $stmt_overdue->get_result();

        $overdue_nodes = [];
        while ($row = $res_overdue->fetch_assoc())
        {
            $overdue_nodes[] = $row;
        }
        $stmt_overdue->close();


        if (!empty($overdue_nodes))
        {


            if ($ins_status === 'failed')
            {
                $ins_status = 'passed';
                $penalty = 0.00;
                $damage_desc = "[SYSTEM ABSORBED] Chain of Custody Fault: Damage was detected, but a preceding SLA violation exists for this venue. Original findings: " . $damage_desc;
            }


            $sql_release_ins = "UPDATE inspection SET ins_status = 'passed', damage_desc = 'State Collapse: Cleared by subsequent legitimate inspection log.', inspected_at = NOW() WHERE ins_id = ?";
            $stmt_release_ins = $conn->prepare($sql_release_ins);

            $sql_release_rpt = "INSERT IGNORE INTO report (ins_id, final_deduct, refund_status, penalty_status, created_at) VALUES (?, 0.00, 'pending', 'none', CURDATE())";
            $stmt_release_rpt = $conn->prepare($sql_release_rpt);

            foreach ($overdue_nodes as $node)
            {
                $stmt_release_ins->bind_param("i", $node['ins_id']);
                $stmt_release_ins->execute();

                $stmt_release_rpt->bind_param("i", $node['ins_id']);
                $stmt_release_rpt->execute();
            }
            $stmt_release_ins->close();
            $stmt_release_rpt->close();
        }


        $sql_ins = "UPDATE inspection
                    SET ins_status = ?, damage_desc = ?, penalty = ?, inspected_at = NOW()
                    WHERE bid = ? AND ins_status = 'pending'";

        $stmt_ins = $conn->prepare($sql_ins);
        $stmt_ins->bind_param("ssdi", $ins_status, $damage_desc, $penalty, $bid);
        $stmt_ins->execute();

        if ($stmt_ins->affected_rows === 0)
        {
            throw new Exception("Execution Fault: Current inspection already processed or state desynchronized.");
        }
        $stmt_ins->close();


        $res = $conn->query("SELECT ins_id FROM inspection WHERE bid = $bid");
        if ($res && $res->num_rows > 0)
        {
            $ins_id = $res->fetch_assoc()['ins_id'];
            $deposit = floatval($curr_meta['deposit']);
            $uid = $curr_meta['uid'];


            $excess = $penalty - $deposit;
            $delta_debt = ($excess > 0) ? $excess : 0.00;
            $refund_status = ($penalty >= $deposit) ? 'none' : 'pending';
            $penalty_status = ($penalty > 0) ? 'pending' : 'none';


            $sql_rpt = "INSERT IGNORE INTO report (ins_id, final_deduct, refund_status, penalty_status, created_at) VALUES (?, ?, ?, ?, CURDATE())";
            $stmt_rpt = $conn->prepare($sql_rpt);
            $stmt_rpt->bind_param("idss", $ins_id, $penalty, $refund_status, $penalty_status);
            $stmt_rpt->execute();
            $stmt_rpt->close();


            if ($delta_debt > 0)
            {
                $sql_user = "UPDATE user SET outstanding_debt = outstanding_debt + ? WHERE uid = ?";
                $stmt_user = $conn->prepare($sql_user);
                $stmt_user->bind_param("ds", $delta_debt, $uid);
                $stmt_user->execute();
                $stmt_user->close();
            }
        }

        $conn->commit();
        $_SESSION['toast'] = ['type' => 'success', 'msg' => "Execution Success: Evaluation finalized and state verified."];

    } catch (Exception $e)
    {
        $conn->rollback();
        $_SESSION['toast'] = ['type' => 'error', 'msg' => $e->getMessage()];
    }

    header("Location: ../admin/pending_inspections.php");
    exit;
} else
{
    header("Location: ../admin/pending_inspections.php");
    exit;
}
