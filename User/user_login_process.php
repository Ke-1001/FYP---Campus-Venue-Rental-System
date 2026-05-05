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
        // 修正：采用实际 Schema 的 `uid` 字段，并与 user_login.php 保持统一
        $_SESSION['uid'] = $row['uid'];
        $_SESSION['role'] = 'user'; // 同步前端逻辑验证的标量

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