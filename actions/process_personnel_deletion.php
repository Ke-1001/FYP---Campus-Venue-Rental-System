<?php
// File: actions/process_personnel_deletion.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

// 💡 1. 擷取實體參數
$type = $_GET['type'] ?? ''; // 'admin' 或 'staff'
$id = $_GET['id'] ?? 0;

if (!$type || !$id) {
    die("Execution Fault: NULL Reference Error.");
}

$conn->begin_transaction();

try {
    if ($type === 'admin') {
        // 🔍 檢查 Admin 的歷史預約軌跡 (Ref: booking.aid)
        $stmt = $conn->prepare("SELECT COUNT(*) FROM booking WHERE aid = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $history_count = $stmt->get_result()->fetch_row()[0];
        
        if ($history_count > 0) {
            // 🔒 執行軟停權 (Deactivation)
            $update = $conn->prepare("UPDATE admin SET status = 'inactive' WHERE aid = ?");
            $update->bind_param("i", $id);
            $update->execute();
            $_SESSION['toast'] = ['type' => 'success', 'msg' => "Trace Detected: Admin [ID:$id] access revoked (Inactive)."];
        } else {
            // 🗑️ 執行物理刪除 (Purge)
            $delete = $conn->prepare("DELETE FROM admin WHERE aid = ?");
            $delete->bind_param("i", $id);
            $delete->execute();
            $_SESSION['toast'] = ['type' => 'success', 'msg' => "No Trace: Admin record purged successfully."];
        }
    } 
    elseif ($type === 'staff') {
        // 🔍 檢查 Staff 的歷史檢驗軌跡 (Ref: inspection.sid)
        $stmt = $conn->prepare("SELECT COUNT(*) FROM inspection WHERE sid = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $history_count = $stmt->get_result()->fetch_row()[0];
        
        if ($history_count > 0) {
            // 🔒 執行軟停權 (Deactivation)
            $update = $conn->prepare("UPDATE staff SET status = 'inactive' WHERE sid = ?");
            $update->bind_param("i", $id);
            $update->execute();
            $_SESSION['toast'] = ['type' => 'success', 'msg' => "Trace Detected: Staff [ID:$id] access revoked (Inactive)."];
        } else {
            // 🗑️ 執行物理刪除 (Purge)
            $delete = $conn->prepare("DELETE FROM staff WHERE sid = ?");
            $delete->bind_param("i", $id);
            $delete->execute();
            $_SESSION['toast'] = ['type' => 'success', 'msg' => "No Trace: Staff record purged successfully."];
        }
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['toast'] = ['type' => 'error', 'msg' => "Transaction Aborted: " . $e->getMessage()];
}

header("Location: ../admin/staff_directory.php");
exit;