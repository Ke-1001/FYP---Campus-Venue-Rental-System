<?php
// File path: actions/process_add_admin.php
session_start();
require_once '../includes/super_admin_auth.php'; // 🔒 API endpoint secured (Root Only)
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 💡 1. 接收並清理來自新版表單的 Payload
    $aid = htmlspecialchars(trim($_POST['aid']));
    $admin_name = htmlspecialchars(trim($_POST['admin_name']));
    $email = trim($_POST['email']);
    $phone_num = htmlspecialchars(trim($_POST['phone_num']));
    $password = $_POST['password'];
    
    // 💡 CRITICAL RBAC ENFORCEMENT: 
    // Ignore any role payload from frontend. Force newly provisioned admins to standard 'admin'.
    $role = 'admin'; 

    // 2. Cryptographic Complexity Verification (Regex)
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
    if (!preg_match($pattern, $password)) {
        $_SESSION['toast'] = [
            'type' => 'error', 
            'msg' => 'Security Fault: Cryptographic key does not meet enterprise complexity standards.'
        ];
        header("Location: ../admin/add_admin.php");
        exit;
    }

    // 💡 3. Double Uniqueness Check (檢查 Email 與新架構的主鍵 aid 是否碰撞)
    $sql_check = "SELECT aid FROM admin WHERE email = ? OR aid = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ss", $email, $aid);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $_SESSION['toast'] = [
            'type' => 'error', 
            'msg' => 'Conflict: Administrator ID or Email is already registered in the system.'
        ];
        $stmt_check->close();
        header("Location: ../admin/add_admin.php");
        exit;
    }
    $stmt_check->close();

    // 4. Cryptographic Hashing
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // 💡 5. Database Deployment (完美對齊 admin 表的 6 個欄位)
    $sql = "INSERT INTO admin (aid, admin_name, email, password, phone_num, role) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // "ssssss" 代表 6 個 String 變數
        $stmt->bind_param("ssssss", $aid, $admin_name, $email, $password_hash, $phone_num, $role);
        if ($stmt->execute()) {
            $_SESSION['toast'] = [
                'type' => 'success', 
                'msg' => 'Node Deployed: Administrator account successfully provisioned.'
            ];
            // Route back to the directory upon success
            header("Location: ../admin/manage_admins.php");
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Fault: ' . $stmt->error];
            header("Location: ../admin/add_admin.php");
        }
        $stmt->close();
    }
    exit;
}
?>