<?php
// File path: actions/process_profile.php
session_start();
require_once '../includes/admin_auth.php'; 
require_once '../config/db.php';

$action = $_POST['action'] ?? '';

// 💡 1. 識別碼相容性：使用新的 aid，並以字串型態查詢
$aid = $_SESSION['aid'] ?? $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ==========================================
    // Sequence A: Identity Parameters Update
    // ==========================================
    if ($action === 'update_profile') {
        // 💡 適配新架構：接收 admin_name 與 phone_num
        $admin_name = htmlspecialchars(trim($_POST['admin_name']));
        $email = trim($_POST['email']);
        $phone_num = htmlspecialchars(trim($_POST['phone_num']));

        // Check for Email Collision (excluding self)
        // 💡 查詢 admin 表，比對 aid
        $sql_check = "SELECT aid FROM admin WHERE email = ? AND aid != ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ss", $email, $aid);
        $stmt_check->execute();
        
        if ($stmt_check->get_result()->num_rows > 0) {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Conflict: Email identifier is already allocated to another entity.'];
            $stmt_check->close();
            header("Location: ../admin/profile.php");
            exit;
        }
        $stmt_check->close();

        // Deploy Update
        // 💡 適配新架構：更新 admin 表的 admin_name, email, phone_num
        $sql = "UPDATE admin SET admin_name = ?, email = ?, phone_num = ? WHERE aid = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ssss", $admin_name, $email, $phone_num, $aid);
            if ($stmt->execute()) {
                // Refresh session state to update sidebar instantly (保持 full_name 相容 UI)
                $_SESSION['full_name'] = $admin_name; 
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
        // 💡 適配新架構：查詢 admin 表的 password 欄位
        $sql_verify = "SELECT password FROM admin WHERE aid = ?";
        $stmt_verify = $conn->prepare($sql_verify);
        $stmt_verify->bind_param("s", $aid);
        $stmt_verify->execute();
        $result = $stmt_verify->get_result();
        
        if ($result->num_rows === 0) {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Critical Anomaly: Entity not found.'];
            header("Location: ../admin/profile.php");
            exit;
        }
        
        $row = $result->fetch_assoc();
        $stmt_verify->close();

        // 驗證密碼
        if (!password_verify($current_password, $row['password'])) {
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
        // 💡 適配新架構：更新 admin 表的 password 欄位
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $sql_update = "UPDATE admin SET password = ? WHERE aid = ?";
        $stmt_update = $conn->prepare($sql_update);
        
        if ($stmt_update) {
            $stmt_update->bind_param("ss", $new_hash, $aid);
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