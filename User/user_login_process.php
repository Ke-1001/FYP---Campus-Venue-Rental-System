<?php
// File: user/user_login_process.php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. 取得使用者輸入
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 2. 從 user 表中驗證學生身分
    $sql = "SELECT uid, username, password 
            FROM user 
            WHERE email = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Database Error: " . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // 3. 驗證密碼
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            // 防禦 Session Fixation 攻擊
            session_regenerate_id(true);

            // 4. 寫入 Session 狀態 (uid 即為學號)
            $_SESSION['uid'] = $user['uid'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = 'user'; // 隱式賦予學生權限

            // 成功後導向學生主頁
            header("Location: homepage.php");
            exit();
        }
    }

    $stmt->close();

    // 驗證失敗，導回登入頁面並顯示錯誤
    header("Location: user_login.php?error=invalid");
    exit();
}

$conn->close();
?>