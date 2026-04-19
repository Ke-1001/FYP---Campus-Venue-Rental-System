<?php
// File: includes/admin_auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. RBAC Verification
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Normal_Admin', 'Super_Admin'])) {
    header("Location: ../admin/login.php?error=access_denied");
    exit();
}

// 2. Idle Timeout Security (30 minutes)
$timeout_duration = 1800; 

if (isset($_SESSION['last_activity'])) {
    $elapsed_time = time() - $_SESSION['last_activity'];
    
    if ($elapsed_time > $timeout_duration) {
        session_unset();
        session_destroy();
        header("Location: ../admin/login.php?error=timeout");
        exit();
    }
}

// 3. Refresh Activity Timestamp
$_SESSION['last_activity'] = time(); 
?>