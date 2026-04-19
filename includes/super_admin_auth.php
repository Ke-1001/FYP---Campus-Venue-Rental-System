<?php
// File: includes/super_admin_auth.php

require_once 'admin_auth.php'; 

// Privilege Elevation Check
if ($_SESSION['role'] !== 'Super_Admin') {
    $_SESSION['toast'] = [
        'type' => 'error', 
        'msg' => 'Access Denied: Super Administrator privileges required.'
    ];
    // Redirect unauthorized personnel back to a safe zone
    header("Location: ../admin/dashboard.php");
    exit();
}
?>