<?php
// File path: actions/process_profile.php
session_start();
require_once '../includes/admin_auth.php'; 
require_once '../config/db.php';

$action = $_POST['action'] ?? '';
// 💡 提取整數識別碼 (相容過渡期的 user_id)
$aid = intval($_SESSION['aid'] ?? $_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // === Sequence A: Identity Parameters Update ===
    if ($action === 'update_profile') {
        $admin_name = htmlspecialchars(trim($_POST['admin_name'] ?? $_POST['full_name'] ?? ''));
        $email = trim($_POST['email']);
        $phone_num = htmlspecialchars(trim($_POST['phone_num'] ?? ''));

        // Collision Check (Excluding self)
        $sql_check = "SELECT aid FROM admin WHERE email = ? AND aid != ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("si", $email, $aid); // string, integer
        $stmt_check->execute();
        
        if ($stmt_check->get_result()->num_rows > 0) {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Conflict: Email identifier already allocated.'];
            $stmt_check->close();
            header("Location: ../admin/profile.php");
            exit;
        }
        $stmt_check->close();

        // 💡 部署至 admin 表
        $sql = "UPDATE admin SET admin_name = ?, email = ?, phone_num = ? WHERE aid = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("sssi", $admin_name, $email, $phone_num, $aid); // 3 Strings, 1 Int
            if ($stmt->execute()) {
                $_SESSION['full_name'] = $admin_name; // Sync UI State
                $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Identity parameters updated successfully.'];
            } else {
                $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Fault: ' . $stmt->error];
            }
            $stmt->close();
        }
        header("Location: ../admin/profile.php");
        exit;
    }

    // === Sequence B: Cryptographic Key Update ===
    elseif ($action === 'update_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        $sql_verify = "SELECT password FROM admin WHERE aid = ?";
        $stmt_verify = $conn->prepare($sql_verify);
        $stmt_verify->bind_param("i", $aid); // "i"
        $stmt_verify->execute();
        $result = $stmt_verify->get_result();
        
        if ($result->num_rows === 0) {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Critical Anomaly: Entity not found.'];
            header("Location: ../admin/profile.php");
            exit;
        }
        
        $row = $result->fetch_assoc();
        $stmt_verify->close();

        if (!password_verify($current_password, $row['password'])) {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Authentication Fault: Current key incorrect.'];
            header("Location: ../admin/profile.php");
            exit;
        }

        if ($new_password !== $confirm_password) {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Logic Fault: Keys do not match.'];
            header("Location: ../admin/profile.php");
            exit;
        }

        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $sql_update = "UPDATE admin SET password = ? WHERE aid = ?";
        $stmt_update = $conn->prepare($sql_update);
        
        if ($stmt_update) {
            $stmt_update->bind_param("si", $new_hash, $aid); // "si"
            if ($stmt_update->execute()) {
                $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Security Upgraded.'];
            } else {
                $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Fault: ' . $stmt_update->error];
            }
            $stmt_update->close();
        }
        header("Location: ../admin/profile.php");
        exit;
    }
}
?>