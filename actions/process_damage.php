<?php
// File: actions/process_damage.php
session_start();
// 修正寻址向量：仅向上一级目录遍历寻找 config 文件夹
require_once __DIR__ . '/../config/db.php';

// ... 保持原有鉴权判断与 POST 验证不变 ...
// 假设你有某种 admin 权限验证机制，如果没有请根据实际情况调整
// require_once __DIR__ . '/../../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../admin/damage_reports.php");
    exit;
}

$report_id = intval($_POST['report_id'] ?? 0);
$admin_remark = trim($_POST['admin_remark'] ?? '');

if ($report_id <= 0) {
    $_SESSION['error'] = "Invalid Report ID.";
    header("Location: ../admin/damage_reports.php");
    exit;
}

// 开启事务 (Transaction) 以保证状态与财务数据的原子性
$conn->begin_transaction();

try {
    // 1. 获取报告与关联订单的状态快照
    $stmt = $conn->prepare("
        SELECT dr.bid, dr.report_status, b.status AS booking_status, b.payment_status, b.cancel_reason 
        FROM damage_report dr
        JOIN booking b ON dr.bid = b.bid
        WHERE dr.report_id = ? FOR UPDATE
    ");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Damage report not found.");
    }
    
    $row = $result->fetch_assoc();
    $bid = $row['bid'];
    
    if ($row['report_status'] === 'reviewed') {
        throw new Exception("This report has already been reviewed.");
    }

    // 2. 状态机跃迁：更新报告状态与管理员批注
    $update_report = $conn->prepare("
        UPDATE damage_report 
        SET report_status = 'reviewed', admin_remark = ? 
        WHERE report_id = ?
    ");
    $update_report->bind_param("si", $admin_remark, $report_id);
    $update_report->execute();

    // 3. 财务状态阻断与修复 (Financial State Reconciliation)
    // 如果该报告是 Level 3 导致用户自动 Cancel，此时系统必须自动处理退款状态
    if ($row['booking_status'] === 'cancelled' && 
        $row['payment_status'] === 'paid' && 
        $row['cancel_reason'] === 'USER_REPORTED_CRITICAL_DAMAGE') {
        
        $update_payment = $conn->prepare("
            UPDATE booking 
            SET payment_status = 'refunded' 
            WHERE bid = ?
        ");
        $update_payment->bind_param("i", $bid);
        $update_payment->execute();
        
        $action_msg = "Report marked as reviewed and Full Refund processed for cancelled booking.";
    } else {
        $action_msg = "Report marked as reviewed. Booking status remains unaffected (Waiver recorded).";
    }

    $conn->commit();
    $_SESSION['success'] = $action_msg;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "System Error: " . $e->getMessage();
}

header("Location: ../admin/damage_reports.php");
exit;