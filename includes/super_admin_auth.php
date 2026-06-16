<?php

// This section provides shared super admin auth logic or layout.
require_once 'admin_auth.php';

if ($_SESSION['role'] !== 'super_admin')
{
    $_SESSION['toast'] = [
        'type' => 'error',
        'msg' => 'Access Denied: Root (Super Administrator) privileges required.'
    ];

 header("Location:../admin/manage_bookings.php");
    exit();
}
?>
