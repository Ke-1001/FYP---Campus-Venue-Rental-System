<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Password strength validation
    $pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";

    if (!preg_match($pattern, $password)) {
        header("Location: ../User/user_register.php?error=weak_password");
        exit();
    }

    // Check if email already exists
    $check_sql = "SELECT uid FROM user WHERE email = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $check_result = $stmt->get_result();

    if ($check_result->num_rows > 0) {
        header("Location: ../User/user_register.php?error=email_exists");
        exit();
    }

    // Hash password securely
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $sql = "INSERT INTO user (username, email, password) 
            VALUES (?, ?, ?)";
    
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