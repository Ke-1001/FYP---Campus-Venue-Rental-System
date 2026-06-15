<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['uid'])) {
    header("Location: user_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $user_id = $_SESSION['uid'];

    // FIXED: Changed 'users' to 'user'
    $stmt = $conn->prepare("SELECT password FROM user WHERE uid = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($current_password, $user['password'])) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // FIXED: Changed 'users' to 'user' and column from 'id' to 'uid'
        $update_stmt = $conn->prepare("UPDATE user SET password = ? WHERE uid = ?");
        $update_stmt->bind_param("ss", $hashed_password, $user_id);
        
        if ($update_stmt->execute()) {
            header("Location: change_password.php?status=success");
            exit();
        }
    } else {
        header("Location: change_password.php?status=error");
        exit();
    }
}
?>