<?php
// File: actions/process_student_action.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin/manage_students.php");
    exit();
}

$action = $_POST['action'] ?? '';

switch ($action) {
    /*
    |--------------------------------------------------------------------------
    | Flow A: Identity Status Synchronization (狀態機變更)
    |--------------------------------------------------------------------------
    | 處理來自 edit_student.php 的狀態變更請求
    */
    case 'update_status':
        $uid = isset($_POST['uid']) ? trim($_POST['uid']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'active';

        if (empty($uid)) {
            $_SESSION['error'] = "System Fault: Missing identity vector (UID).";
            header("Location: ../admin/manage_students.php");
            exit();
        }

        // ∴ 嚴格映射物理欄位 account_status
        $stmt = $conn->prepare("UPDATE user SET account_status = ? WHERE uid = ?");
        if ($stmt) {
            $stmt->bind_param("ss", $status, $uid);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Identity Matrix successfully synchronized.";
            } else {
                $_SESSION['error'] = "Execution Fault: Database persistence failed.";
            }
            $stmt->close();
        }
        header("Location: ../admin/edit_student.php?uid=" . urlencode($uid));
        exit();

    /*
    |--------------------------------------------------------------------------
    | Flow B: Preemptive Bulk Delete Interception (硬性關係攔截)
    |--------------------------------------------------------------------------
    | 阻止刪除存有預約向量的用戶，引導管理員實施狀態凍結
    */
    case 'bulk_delete':
        // DataGrid 預設傳遞的複選框陣列鍵值為 ids
        $ids = $_POST['ids'] ?? [];

        if (empty($ids) || !is_array($ids)) {
            $_SESSION['error'] = "System Fault: No entity nodes selected for mutation.";
            header("Location: ../admin/manage_students.php");
            exit();
        }

        $conn->begin_transaction();
        try {
            foreach ($ids as $uid) {
                $uid = trim($uid);
                
                // 1. 關係拓撲校驗 (Relational Constraint Check)
                $check_stmt = $conn->prepare("SELECT COUNT(*) AS booking_count FROM booking WHERE uid = ?");
                $check_stmt->bind_param("s", $uid);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result()->fetch_assoc();
                $check_stmt->close();

                if ($check_result['booking_count'] > 0) {
                    // ∴ 觸發拓撲違例攔截，拋出錯誤訊息並回滾事務
                    throw new Exception("Failed to Delete: Student [UID: {$uid}] possesses active or historical booking records within the cluster. Physical erasure is denied. Please navigate to the Identity Profile Editor to safely transition their Account State to 'Restricted' instead.");
                }

                // 2. 若無關聯數據，則允許執行最小物理刪除
                $delete_stmt = $conn->prepare("DELETE FROM user WHERE uid = ?");
                $delete_stmt->bind_param("s", $uid);
                $delete_stmt->execute();
                $delete_stmt->close();
            }

            $conn->commit();
            $_SESSION['success'] = "Selected entity matrices purged successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            // 寫入 Session 錯誤緩衝區，格式化標記以供前端渲染引擎動態攔截
            $_SESSION['error'] = $e->getMessage();
        }

        header("Location: ../admin/manage_students.php");
        exit();

    default:
        $_SESSION['error'] = "Execution Fault: Unknown operational protocol.";
        header("Location: ../admin/manage_students.php");
        exit();
}