<?php
// File: user/user_login_process.php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Payload Extraction
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 💡 2. 適配新架構：從獨立的 user 表提取驗證向量
    $sql = "SELECT uid, username, password 
            FROM user 
            WHERE email = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Prepare Fault: " . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // 3. Cryptographic Verification Sequence
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // 💡 映射至新欄位名稱 'password'
        if (password_verify($password, $user['password'])) {

            // Security: Prevent session fixation
            session_regenerate_id(true);

            // 💡 4. 狀態機注入：寫入新版 Session 參數 (uid, username)
            $_SESSION['uid'] = $user['uid'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = 'user'; // Implicit role definition

            // 路由至使用者主頁 (對齊 user_login.php 的重定向邏輯)
            header("Location: homepage.php");
            exit();
        }
    }

    $stmt->close();

    // Authentication Fault 
    header("Location: user_login.php?error=invalid");
    exit();
}

$conn->close();
?>