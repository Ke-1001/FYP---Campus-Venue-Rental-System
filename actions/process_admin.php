<?php
// File path: actions/process_admin.php
session_start();
require_once '../includes/admin_auth.php'; // Standard admin gate
require_once '../config/db.php';

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // === UPDATE PROFILE ===
    $user_id = intval($_POST['user_id']);
    $full_name = htmlspecialchars(trim($_POST['full_name']));
    $email = trim($_POST['email']);

    // Check for email collision (excluding the current user)
    $sql_check = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("si", $email, $user_id);
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

    $sql = "UPDATE users SET full_name = ?, email = ? WHERE user_id = ? AND role IN ('Normal_Admin', 'Super_Admin')";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssi", $full_name, $email, $user_id);
        if ($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Configuration Deployed: Administrator profile updated.'];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Fault: ' . $stmt->error];
        }
        $stmt->close();
    }
    header("Location: ../admin/manage_admins.php");
    exit;

} elseif ($action === 'delete' && isset($_GET['id'])) {
    // === REVOKE PRIVILEGES ===
    
    // RBAC: Only Super_Admin can execute deletions
    if ($_SESSION['role'] !== 'Super_Admin') {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Access Denied: Only Super Administrator can revoke nodes.'];
        header("Location: ../admin/manage_admins.php");
        exit;
    }

    $user_id = intval($_GET['id']);

    // Prevent deletion of the Root node
    $sql_check = "SELECT role FROM users WHERE user_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows > 0) {
        $target = $result->fetch_assoc();
        if ($target['role'] === 'Super_Admin') {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Violation: Root immutable node (Super_Admin) cannot be terminated.'];
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
    $sql = "DELETE FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
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