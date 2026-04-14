<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get user input
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Query to find a User (not Admin)
    $sql = "SELECT user_id, full_name, password_hash, role 
            FROM users 
            WHERE email = ? AND role = 'User'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password using hash
        if (password_verify($password, $user['password_hash'])) {

            // Security: prevent session fixation attack
            session_regenerate_id(true);

            // Store user data in session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            // Redirect to user homepage
            header("Location: ../user/user_dashboard.php");
            exit();
        }
    }

    // Invalid credentials → redirect back to login page
    header("Location: ../User/user_login.php?error=invalid");
    exit();
}

$conn->close();
?>