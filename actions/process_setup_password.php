<?php
// File: actions/process_setup_password.php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 💡 1. 防護檢驗
    if (empty($token) || empty($password) || empty($confirm_password)) {
        die("Security Fault: Missing required payload vectors.");
    }
    if ($password !== $confirm_password) {
        die("Symmetry Fault: Passwords do not match.");
    }

    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
    if (!preg_match($pattern, $password)) {
        die("Security Fault: Password complexity does not meet protocol standards.");
    }

    $conn->begin_transaction();

    try {
        // 💡 2. 驗證權杖並獲取目標 Email
        $token_hash = hash('sha256', $token);
        $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token_hash = ? AND expires_at > NOW()");
        $stmt->bind_param("s", $token_hash);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Cryptographic Execution Failed: Invalid or expired token.");
        }
        $email = $result->fetch_assoc()['email'];
        $stmt->close();

        // 💡 3. 生成高強度密碼雜湊
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $update_success = false;

        // 💡 4. 多態實體解析 (Polymorphic Entity Resolution: Admin vs Staff)
        // 先嘗試更新 Admin 表
        $stmt_admin = $conn->prepare("UPDATE admin SET password = ? WHERE email = ?");
        $stmt_admin->bind_param("ss", $password_hash, $email);
        $stmt_admin->execute();
        if ($stmt_admin->affected_rows > 0) {
            $update_success = true;
        }
        $stmt_admin->close();

        // 若 Admin 未更新，嘗試更新 Staff 表
        if (!$update_success) {
            $stmt_staff = $conn->prepare("UPDATE staff SET password = ? WHERE email = ?");
            $stmt_staff->bind_param("ss", $password_hash, $email);
            $stmt_staff->execute();
            if ($stmt_staff->affected_rows > 0) {
                $update_success = true;
            }
            $stmt_staff->close();
        }

        if (!$update_success) {
            throw new Exception("Entity Resolution Fault: Target email not found in active operational tables.");
        }

        // 💡 5. 權杖自毀協議 (Purge Consumed Token)
        $stmt_del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt_del->bind_param("s", $email);
        $stmt_del->execute();
        $stmt_del->close();

        $conn->commit();
        
        // 部署成功後，導向登入介面
        $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Credential vector configured successfully. You may now authenticate.'];
        header("Location: ../admin/login.php"); 
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        die($e->getMessage());
    }
} else {
    header("Location: ../admin/login.php");
    exit;
}