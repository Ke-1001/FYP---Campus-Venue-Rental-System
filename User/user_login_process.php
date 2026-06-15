<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$uid = trim($_POST['uid']);
$password = $_POST['password'];

// 1. Update the SELECT statement to include 'username'
$stmt = $conn->prepare("SELECT uid, password, username FROM user WHERE uid = ? OR email = ?");
$stmt->bind_param("ss", $uid, $uid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        $_SESSION['uid'] = $user['uid'];
        
        // 2. ADD THIS LINE: Save the name to the session
        $_SESSION['username'] = $user['username'];
        
        header("Location: ../User/user_dashboard.php");
        exit();
    }
}

header("Location: user_login.php?status=error&msg=Invalid+ID+or+Password");
exit();
?>