<?php
// This section checks damage report decisions and updates the report status.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
function set_toast($type, $msg)
{
    $_SESSION['toast'] = [
        'type' => $type,
        'msg' => $msg
    ];
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    set_toast('error', "Invalid request method.");
    header("Location: ../admin/damage_reports.php");
    exit;
}
$report_id = intval($_POST['report_id'] ?? 0);
$admin_remark = trim($_POST['admin_remark'] ?? '');
if ($report_id <= 0)
{
    set_toast('error', "Invalid Report ID.");
    header("Location: ../admin/damage_reports.php");
    exit;
}
$conn->begin_transaction();
try
{
    $stmt = $conn->prepare("
        SELECT dr.bid, dr.report_status, b.status AS booking_status, b.payment_status, b.cancel_reason
        FROM damage_report dr
        JOIN booking b ON dr.bid = b.bid
        WHERE dr.report_id = ? FOR UPDATE
    ");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0)
    {
        throw new Exception("Damage report not found.");
    }
    $row = $result->fetch_assoc();
    $bid = $row['bid'];
    if ($row['report_status'] === 'reviewed')
    {
        throw new Exception("This report has already been reviewed.");
    }
    $update_report = $conn->prepare("
        UPDATE damage_report
        SET report_status = 'reviewed', admin_remark = ?
        WHERE report_id = ?
    ");
    $update_report->bind_param("si", $admin_remark, $report_id);
    $update_report->execute();
    if ($row['booking_status'] === 'cancelled' && $row['payment_status'] === 'paid' && $row['cancel_reason'] === 'USER_REPORTED_CRITICAL_DAMAGE')
    {
        $update_payment = $conn->prepare("
            UPDATE booking
            SET payment_status = 'refunded'
            WHERE bid = ?
        ");
        $update_payment->bind_param("i", $bid);
        $update_payment->execute();
        $action_msg = "Report marked as reviewed and Full Refund processed for cancelled booking.";
    } else
    {
        $action_msg = "Report marked as reviewed. Booking status remains unaffected (Waiver recorded).";
    }
    $conn->commit();
    set_toast('success', $action_msg);
} catch (Exception $e)
{
    $conn->rollback();
    set_toast('error', "System Error: " . $e->getMessage());
}
header("Location: ../admin/damage_reports.php");
exit;
