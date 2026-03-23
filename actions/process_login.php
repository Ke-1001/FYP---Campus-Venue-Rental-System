<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password']; // USER enters plaintext password

    // 1. search for the user in the database
    $sql = "SELECT user_id, full_name, password_hash, role FROM users WHERE email = ? AND role = 'Admin'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // 2. authenticate the password
        // (為了相容我們當初手動塞進資料庫的 'admin123' 明文測試資料，這裡做了一個簡單的比對)
        // 正式環境請務必改成: if (password_verify($password, $user['password_hash'])) { ... }
        if (password_verify($password, $user['password_hash'])) {
            
            // Login successful, set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // redirect to admin's homepage (booking approval center)
            header("Location: ../admin/manage_bookings.php");
            exit();
        }
    }
    
    // wrong credentials, redirect back to login with error message
    header("Location: ../admin/login.php?error=invalid");
    exit();
}
$conn->close();
?>