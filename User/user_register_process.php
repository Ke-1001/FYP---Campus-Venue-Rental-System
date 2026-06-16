<?php
// This section checks user registration details and creates the user account.
require_once __DIR__ . '/../config/db.php';
$uid = trim($_POST['uid']);
$email = trim($_POST['email']);
$username = $_POST['username'];
$phone_num = $_POST['phone_num'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
if (!preg_match('/^[0-9]{3}[A-Za-z]{2}[A-Za-z0-9]{5}$/', $uid))
{
    header("Location: user_register.php?status=error&msg=Invalid+ID+Format");
    exit();
}
if (strpos($email, '@student.mmu.edu.my') === false)
{
    header("Location: user_register.php?status=error&msg=Only+MMU+Emails+Allowed");
    exit();
}
$stmt = $conn->prepare("SELECT uid FROM user WHERE uid = ? OR email = ?");
$stmt->bind_param("ss", $uid, $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0)
{
    header("Location: user_register.php?status=error&msg=User+Already+Exists");
    exit();
}
$stmt = $conn->prepare("INSERT INTO user (uid, username, email, password, phone_num) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $uid, $username, $email, $password, $phone_num);
if ($stmt->execute())
{
    header("Location: user_register.php?status=success&msg=Account+Created+Successfully");
}
else
{
    header("Location: user_register.php?status=error&msg=Database+Error");
}
exit();
?>
