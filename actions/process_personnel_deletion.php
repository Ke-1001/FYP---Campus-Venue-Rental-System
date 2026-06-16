<?php
// This section checks and processes personnel deletion requests.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
$action = $_POST['action'] ?? '';
$ids = $_POST['ids'] ?? [];
if ($action !== 'bulk_delete' || empty($ids) || !is_array($ids))
{
    die("Execution Fault: Invalid Action or Payload Topology.");
}
$conn->begin_transaction();
try
{
    $success_count = 0;
    $stmts = [];
    foreach ($ids as $compound_id)
    {
        $parts = explode('|', $compound_id);
        $raw_type = $parts[0] ?? '';
        $id = intval($parts[1] ?? 0);
        $type = strtolower($raw_type);
        if (!in_array($type, ['admin', 'staff']) || $id <= 0)
        {
            throw new Exception("Security Exception: Illegal Entity Reference detected [{$compound_id}].");
        }
        if ($type === 'admin')
        {
            if (!isset($stmts['chk_admin']))
            {
                $stmts['chk_admin'] = $conn->prepare("SELECT COUNT(*) FROM booking WHERE aid = ?");
            }
            $stmts['chk_admin']->bind_param("i", $id);
            $stmts['chk_admin']->execute();
            $history_count = $stmts['chk_admin']->get_result()->fetch_row()[0];
            if ($history_count > 0)
            {
                if (!isset($stmts['upd_admin'])) $stmts['upd_admin'] = $conn->prepare("UPDATE admin SET status = 'inactive' WHERE aid = ?"); $stmts['upd_admin']->bind_param("i", $id); $stmts['upd_admin']->execute(); } else
                {
                if (!isset($stmts['del_admin'])) $stmts['del_admin'] = $conn->prepare("DELETE FROM admin WHERE aid = ?"); $stmts['del_admin']->bind_param("i", $id); $stmts['del_admin']->execute(); } } elseif ($type === 'staff')
                {
            if (!isset($stmts['chk_staff']))
            {
                $stmts['chk_staff'] = $conn->prepare("SELECT COUNT(*) FROM inspection WHERE sid = ?");
            }
            $stmts['chk_staff']->bind_param("i", $id);
            $stmts['chk_staff']->execute();
            $history_count = $stmts['chk_staff']->get_result()->fetch_row()[0];
            if ($history_count > 0)
            {
                if (!isset($stmts['upd_staff'])) $stmts['upd_staff'] = $conn->prepare("UPDATE staff SET status = 'inactive' WHERE sid = ?"); $stmts['upd_staff']->bind_param("i", $id); $stmts['upd_staff']->execute(); } else
                {
                if (!isset($stmts['del_staff'])) $stmts['del_staff'] = $conn->prepare("DELETE FROM staff WHERE sid = ?");
                $stmts['del_staff']->bind_param("i", $id);
                $stmts['del_staff']->execute();
            }
        }
        $success_count++;
    }
    foreach ($stmts as $stmt)
    {
        $stmt->close();
    }
    $conn->commit();
    $_SESSION['toast'] = ['type' => 'success', 'msg' => "Operation Executed: {$success_count} entity trace(s) successfully processed."];
} catch (Exception $e)
{
    $conn->rollback();
    $_SESSION['toast'] = ['type' => 'error', 'msg' => "Transaction Aborted: " . $e->getMessage()];
}
header("Location: ../admin/staff_directory.php");
exit;
