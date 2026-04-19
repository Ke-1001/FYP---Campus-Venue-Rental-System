<?php
// File path: actions/process_add_admin.php
session_start();
require_once '../includes/super_admin_auth.php'; // 🔒 API endpoint secured
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = htmlspecialchars(trim($_POST['full_name']));
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // 💡 CRITICAL RBAC ENFORCEMENT: 
    // Ignore any role payload from frontend. Force newly provisioned admins to Level 1.
    $role = 'Normal_Admin'; 

    // 1. Cryptographic Complexity Verification (Regex)
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
    if (!preg_match($pattern, $password)) {
        $_SESSION['toast'] = [
            'type' => 'error', 
            'msg' => 'Security Fault: Cryptographic key does not meet enterprise complexity standards.'
        ];
        header("Location: ../admin/add_admin.php");
        exit;
    }

    // 2. Email Uniqueness Check
    $sql_check = "SELECT user_id FROM users WHERE email = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $_SESSION['toast'] = [
            'type' => 'error', 
            'msg' => 'Conflict: Email identifier is already registered in the system.'
        ];
        $stmt_check->close();
        header("Location: ../admin/add_admin.php");
        exit;
    }
    $stmt_check->close();

    // 3. Cryptographic Hashing
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // 4. Database Deployment
    $sql = "INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssss", $full_name, $email, $password_hash, $role);
        if ($stmt->execute()) {
            $_SESSION['toast'] = [
                'type' => 'success', 
                'msg' => 'Node Deployed: Administrator account successfully provisioned.'
            ];
            // Route back to the directory upon success
            header("Location: ../admin/manage_admins.php");
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Fault: ' . $stmt->error];
            header("Location: ../admin/add_admin.php");
        }
        $stmt->close();
    }
    exit;
}
?>