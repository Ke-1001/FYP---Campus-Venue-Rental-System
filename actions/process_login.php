<?php
// File: actions/process_login.php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password']; // USER enters plaintext password

    // 1. Search for the admin in the new `admin` table
    // 💡 適配新架構：使用 aid, admin_name, password 以及小寫的 role
    $sql = "SELECT aid, admin_name, password, role FROM admin WHERE email = ? AND role IN ('admin', 'super_admin')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // 2. Authenticate the password
        // 💡 雙重驗證機制：優先使用安全的 password_verify，若失敗則嘗試比對明文 (方便開發過渡期)
        if (password_verify($password, $user['password']) || $password === $user['password']) {
            
            // Login successful, set session variables
            $_SESSION['aid'] = $user['aid']; // 寫入新的主鍵
            $_SESSION['full_name'] = $user['admin_name']; // 映射到舊的 full_name 以保持 UI 相容
            $_SESSION['role'] = $user['role']; // 寫入新的小寫 role
            
            // Redirect to admin's launchpad
            header("Location: ../admin/manage_bookings.php");
            exit();
        }
    }
    
    // Wrong credentials, redirect back to login with error message
    header("Location: ../admin/login.php?error=invalid");
    exit();
}
$conn->close();
?>