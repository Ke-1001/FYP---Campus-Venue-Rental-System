<?php
session_start();
include("../config/db.php");

$identifier = $_POST['login_identifier'];
$password = $_POST['password'];

// 采用 ID 为主，Email 为辅的复合查询
$stmt = $conn->prepare("SELECT * FROM user WHERE uid = ? OR email = ?");
$stmt->bind_param("ss", $identifier, $identifier);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    

    if (password_verify($password, $row['password'])) {
        // 状态水化 (State Hydration)
        $_SESSION['uid'] = $row['uid'];
        $_SESSION['username'] = $row['username']; // 🔴 新增：将数据库中的 username 映射至 Session
        $_SESSION['role'] = 'user'; 

        header("Location: homepage.php");
        exit();
    } else {
        header("Location: user_login.php?error=invalid");
        exit();
    }

} else {
    header("Location: user_login.php?error=invalid");
    exit();
}
?>