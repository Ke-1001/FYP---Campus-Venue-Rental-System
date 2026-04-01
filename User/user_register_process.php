<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 1. Check if email already exists
    $check_sql = "SELECT user_id FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $check_result = $stmt->get_result();

    if ($check_result->num_rows > 0) {
        header("Location: ../User/user_register.php?error=email_exists");
        exit();
    }

    // 2. Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // 3. Insert user (default role = User)
    $sql = "INSERT INTO users (full_name, email, password_hash, role) 
            VALUES (?, ?, ?, 'User')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $full_name, $email, $password_hash);

    if ($stmt->execute()) {
        header("Location: ../User/user_login.php?success=registered");
        exit();
    } else {
        header("Location: ../User/user_register.php?error=failed");
        exit();
    }
}

$conn->close();
?>