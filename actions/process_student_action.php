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
 | Flow A: Identity Status Synchronization (status change)
    |--------------------------------------------------------------------------
 | Handle status change request from edit_student.php
    */
    case 'update_status':
        $uid = isset($_POST['uid']) ? trim($_POST['uid']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'active';

        if (empty($uid)) {
            $_SESSION['error'] = "System Fault: Missing identity vector (UID).";
            header("Location: ../admin/manage_students.php");
            exit();
        }

 // Map to the account_status column
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
 | Flow B: Preemptive Bulk Delete Interception (strict related-record blocking)
    |--------------------------------------------------------------------------
 | Block deletion of users with bookings and ask admin to disable the account
    */
    case 'bulk_delete':
 // DataGrid default checkbox array key is ids
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
                
 // 1. Check related records (Relational Constraint Check)
                $check_stmt = $conn->prepare("SELECT COUNT(*) AS booking_count FROM booking WHERE uid = ?");
                $check_stmt->bind_param("s", $uid);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result()->fetch_assoc();
                $check_stmt->close();

                if ($check_result['booking_count'] > 0) {
 // Show error and roll back when related records exist
                    throw new Exception("Failed to Delete: Student [UID: {$uid}] possesses active or historical booking records within the cluster. Physical erasure is denied. Please navigate to the Identity Profile Editor to safely transition their Account State to 'Restricted' instead.");
                }

 // 2. ifnorelated, thenAllowRunHard delete
                $delete_stmt = $conn->prepare("DELETE FROM user WHERE uid = ?");
                $delete_stmt->bind_param("s", $uid);
                $delete_stmt->execute();
                $delete_stmt->close();
            }

            $conn->commit();
            $_SESSION['success'] = "Selected entity matrices purged successfully.";
        } catch (Exception $e) {
            $conn->rollback();
 // Save error message in session for the frontend modal
            $_SESSION['error'] = $e->getMessage();
        }

        header("Location: ../admin/manage_students.php");
        exit();

    default:
        $_SESSION['error'] = "Execution Fault: Unknown operational protocol.";
        header("Location: ../admin/manage_students.php");
        exit();
}