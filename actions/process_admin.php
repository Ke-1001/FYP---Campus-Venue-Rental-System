<?php
// File path: actions/process_admin.php
session_start();
require_once '../includes/admin_auth.php'; // Standard admin gate
require_once '../config/db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // === UPDATE PROFILE ===
    // 💡 1. 嚴格轉型：提取整數識別碼 aid
    $aid = intval($_POST['aid'] ?? 0);
    $admin_name = htmlspecialchars(trim($_POST['admin_name']));
    $email = trim($_POST['email']);
    $phone_num = htmlspecialchars(trim($_POST['phone_num']));

    if ($aid === 0) {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Fatal: Invalid Identity Parameter.'];
        header("Location: ../admin/manage_admins.php");
        exit;
    }

    // 💡 2. Double Collision Check (排除自身的 aid)
    $sql_check = "SELECT aid FROM admin WHERE (email = ? OR admin_name = ?) AND aid != ?";
    $stmt_check = $conn->prepare($sql_check);
    // "ssi" -> 2 Strings, 1 Int
    $stmt_check->bind_param("ssi", $email, $admin_name, $aid); 
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $_SESSION['toast'] = [
            'type' => 'error', 
            'msg' => 'Update Blocked: Email or Administrator Name already belongs to another entity.'
        ];
        $stmt_check->close();
        header("Location: ../admin/manage_admins.php");
        exit;
    }
    $stmt_check->close();

    // 💡 3. 部署更新
    $sql = "UPDATE admin SET admin_name = ?, email = ?, phone_num = ? WHERE aid = ? AND role IN ('admin', 'super_admin')";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // "sssi" -> 3 Strings, 1 Int
        $stmt->bind_param("sssi", $admin_name, $email, $phone_num, $aid);
        if ($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'msg' => "Configuration Deployed: Administrator [ID: {$aid}] updated."];
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
    if ($_SESSION['role'] !== 'super_admin') {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Access Denied: Only Super Administrator can revoke nodes.'];
        header("Location: ../admin/manage_admins.php");
        exit;
    }

    // 💡 提取整數識別碼
    $aid = intval($_GET['aid']);

    // 預防刪除 Root 節點
    $sql_check = "SELECT role FROM admin WHERE aid = ?";
    $stmt_check = $conn->prepare($sql_check);
    // "i" -> Int
    $stmt_check->bind_param("i", $aid);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows > 0) {
        $target = $result->fetch_assoc();
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

    // 💡 執行安全刪除
    $sql = "DELETE FROM admin WHERE aid = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $aid);
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