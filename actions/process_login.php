<?php
// File path: actions/process_login.php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Payload Extraction
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 💡 2. 對齊實體隔離表 (從 admin 表而非 users 提取資料)
    $sql = "SELECT aid, admin_name, password, role FROM admin WHERE email = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Prepare Fault: " . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // 3. Cryptographic Verification Sequence
    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        // 驗證密碼學特徵
        if (password_verify($password, $admin['password'])) {

            // Security: Prevent session fixation
            session_regenerate_id(true);

            // 💡 4. 寫入狀態機 (Session Key 映射為 aid)
            $_SESSION['aid'] = $admin['aid'];
            $_SESSION['full_name'] = $admin['admin_name'];
            $_SESSION['role'] = $admin['role'];

            // Routing to Operations Launchpad
            header("Location: ../admin/dashboard.php");
            exit();
        }
    }

    $stmt->close();

    // Authentication Fault
    header("Location: ../admin/login.php?error=invalid");
    exit();
}

$conn->close();
?>