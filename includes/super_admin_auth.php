<?php
// File: includes/super_admin_auth.php

require_once 'admin_auth.php'; 

// 💡 Privilege Elevation Check (Mapped to new lowercase ENUM)
if ($_SESSION['role'] !== 'super_admin') {
    $_SESSION['toast'] = [
        'type' => 'error', 
        'msg' => 'Access Denied: Root (Super Administrator) privileges required.'
    ];
    // Redirect unauthorized personnel back to a safe zone
    header("Location: ../admin/manage_bookings.php"); // 💡 修正路由至新的 Launchpad
    exit();
}
?>