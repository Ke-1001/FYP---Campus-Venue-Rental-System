<?php
// File path: actions/process_profile.php
session_start();
require_once '../includes/admin_auth.php'; 
require_once '../config/db.php';

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ==========================================
    // Sequence A: Identity Parameters Update
    // =========================    =================
    if ($action === 'update_profile') {
        $full_name = htmlspecialchars(trim($_POST['full_name']));
        $email = trim($_POST['email']);

        // Check for Email Collision (excluding self)
        $sql_check = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("si", $email, $user_id);
        $stmt_check->execute();
        
        if ($stmt_check->get_result()->num_rows > 0) {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Conflict: Email identifier is already allocated to another entity.'];
            $stmt_check->close();
            header("Location: ../admin/profile.php");
            exit;
        }
        $stmt_check->close();

        // Deploy Update
        $sql = "UPDATE users SET full_name = ?, email = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ssi", $full_name, $email, $user_id);
            if ($stmt->execute()) {
                $_SESSION['full_name'] = $full_name; // Refresh session state to update sidebar instantly
                $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Configuration Deployed: Identity parameters updated successfully.'];
            } else {
                $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Fault: ' . $stmt->error];
            }
            $stmt->close();
        }
        header("Location: ../admin/profile.php");
        exit;
    }

    // ==========================================
    // Sequence B: Cryptographic Key Update
    // ==========================================
    elseif ($action === 'update_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // 1. Verify Current Cryptographic Key
        $sql_verify = "SELECT password_hash FROM users WHERE user_id = ?";
        $stmt_verify = $conn->prepare($sql_verify);
        $stmt_verify->bind_param("i", $user_id);
        $stmt_verify->execute();
        $result = $stmt_verify->get_result();
        
        if ($result->num_rows === 0) {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Critical Anomaly: Entity not found.'];
            header("Location: ../admin/profile.php");
            exit;
        }
        
        $row = $result->fetch_assoc();
        $stmt_verify->close();

        if (!password_verify($current_password, $row['password_hash'])) {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Authentication Fault: Current key provided is incorrect.'];
            header("Location: ../admin/profile.php");
            exit;
        }

        // 2. Validate New Key Matrix
        if ($new_password !== $confirm_password) {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Logic Fault: New cryptographic keys do not match.'];
            header("Location: ../admin/profile.php");
            exit;
        }

        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
        if (!preg_match($pattern, $new_password)) {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Security Fault: Key does not meet enterprise complexity standards.'];
            header("Location: ../admin/profile.php");
            exit;
        }

        // 3. Deploy New Cryptographic Hash
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $sql_update = "UPDATE users SET password_hash = ? WHERE user_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        
        if ($stmt_update) {
            $stmt_update->bind_param("si", $new_hash, $user_id);
            if ($stmt_update->execute()) {
                $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Security Upgraded: New cryptographic key has been enforced.'];
            } else {
                $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Fault: ' . $stmt_update->error];
            }
            $stmt_update->close();
        }
        
        header("Location: ../admin/profile.php");
        exit;
    }

} else {
    // Malformed request fallback
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Invalid HTTP request vector.'];
    header("Location: ../admin/profile.php");
    exit;
}
?>