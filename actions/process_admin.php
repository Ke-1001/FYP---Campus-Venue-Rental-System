<?php
// File path: actions/process_admin.php
session_start();
require_once '../includes/admin_auth.php'; // Standard admin gate
require_once '../config/db.php';

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // === UPDATE PROFILE ===
    // 💡 適配新架構：使用 aid (VARCHAR)，並接收 phone_num
    $aid = htmlspecialchars(trim($_POST['aid']));
    $admin_name = htmlspecialchars(trim($_POST['admin_name']));
    $email = trim($_POST['email']);
    $phone_num = htmlspecialchars(trim($_POST['phone_num']));

    // Check for email collision (excluding the current user)
    // 💡 適配新架構：admin 表
    $sql_check = "SELECT aid FROM admin WHERE email = ? AND aid != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ss", $email, $aid); // "ss" 兩個都是字串
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $_SESSION['toast'] = [
            'type' => 'error', 
            'msg' => 'Update Blocked: Email identifier already belongs to another entity.'
        ];
        $stmt_check->close();
        header("Location: ../admin/manage_admins.php");
        exit;
    }
    $stmt_check->close();

    // 💡 適配新架構：更新 admin 表的 admin_name, email, phone_num
    $sql = "UPDATE admin SET admin_name = ?, email = ?, phone_num = ? WHERE aid = ? AND role IN ('admin', 'super_admin')";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssss", $admin_name, $email, $phone_num, $aid);
        if ($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Configuration Deployed: Administrator profile updated.'];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Fault: ' . $stmt->error];
        }
        $stmt->close();
    }
    header("Location: ../admin/manage_admins.php");
    exit;

} elseif ($action === 'delete' && isset($_GET['aid'])) {
    // === REVOKE PRIVILEGES ===
    
    // RBAC: Only Super_Admin can execute deletions
    // 💡 適配新架構：權限為小寫
    if ($_SESSION['role'] !== 'super_admin') {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Access Denied: Only Super Administrator can revoke nodes.'];
        header("Location: ../admin/manage_admins.php");
        exit;
    }

    $aid = $_GET['aid'];

    // Prevent deletion of the Root node
    $sql_check = "SELECT role FROM admin WHERE aid = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $aid);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows > 0) {
        $target = $result->fetch_assoc();
        // 💡 適配新架構：檢查小寫 super_admin
        if ($target['role'] === 'super_admin') {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Violation: Root immutable node (super_admin) cannot be terminated.'];
            $stmt_check->close();
            header("Location: ../admin/manage_admins.php");
            exit;
        }
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Entity not found.'];
        header("Location: ../admin/manage_admins.php");
        exit;
    }
    $stmt_check->close();

    // Execute safe deletion
    $sql = "DELETE FROM admin WHERE aid = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $aid);
        if ($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Node Terminated: Administrative privileges revoked globally.'];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Deletion Fault: ' . $stmt->error];
        }
        $stmt->close();
    }
    header("Location: ../admin/manage_admins.php");
    exit;

} else {
    // Invalid Vector
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Malformed request vector.'];
    header("Location: ../admin/manage_admins.php");
    exit;
}
?>