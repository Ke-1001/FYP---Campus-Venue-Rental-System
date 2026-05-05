<?php
include("../config/db.php");

$uid = $_POST['uid'];
$username = $_POST['username'];
$email = $_POST['email'];
$phone_num = $_POST['phone_num'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// 复合去重校验: 确保 ID 或 Email 均未被占用
$stmt = $conn->prepare("SELECT uid FROM user WHERE uid = ? OR email = ?");
$stmt->bind_param("ss", $uid, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    header("Location: user_register.php?error=exists");
    exit();
}

// 执行规范化注入
$stmt = $conn->prepare("INSERT INTO user (uid, username, email, password, phone_num) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $uid, $username, $email, $password, $phone_num);

if ($stmt->execute()) {
    header("Location: user_login.php?success=registered");
} else {
    header("Location: user_register.php?error=failed");
}
exit();
?>