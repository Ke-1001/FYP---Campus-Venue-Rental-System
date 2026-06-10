<?php
// File path: includes/admin_auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$admin_identifier = $_SESSION['aid'] ?? null;
$staff_identifier = $_SESSION['sid'] ?? null;
$admin_role = $_SESSION['role'] ?? '';

$allowed_roles = ['super_admin', 'admin', 'staff'];

if ((!$admin_identifier && !$staff_identifier) || !in_array($admin_role, $allowed_roles, true)) {
    session_unset();
    session_destroy();
    header("Location: ../admin/login.php?error=access_denied");
    exit();
}

global $conn;
if (isset($conn)) {
    if ($admin_role === 'staff') {
        $auth_stmt = $conn->prepare("SELECT status FROM staff WHERE sid = ? LIMIT 1");
        $auth_stmt->bind_param("i", $staff_identifier);
    } else {
        $auth_stmt = $conn->prepare("SELECT status FROM admin WHERE aid = ? LIMIT 1");
        $auth_stmt->bind_param("i", $admin_identifier);
    }

    $auth_stmt->execute();
    $auth_res = $auth_stmt->get_result();

    if ($auth_res->num_rows === 1) {
        $auth_row = $auth_res->fetch_assoc();
        if ($auth_row['status'] === 'inactive') {
            session_unset();
            session_destroy();
            header("Location: ../admin/login.php?error=account_revoked");
            exit();
        }
    } else {
        session_unset();
        session_destroy();
        header("Location: ../admin/login.php?error=account_deleted");
        exit();
    }
    $auth_stmt->close();
}

/*
|--------------------------------------------------------------------------
| Role-based page access
|--------------------------------------------------------------------------
| staff       : can only open inspection pages and inspection action
| admin       : can open all admin modules except inspection pages/actions
| super_admin : no restriction
*/
$current_script = basename($_SERVER['SCRIPT_NAME']);

$inspection_pages = [
    'inspections.php',
    'pending_inspections.php',
    'execute_inspection.php',
    'track_inspections.php',
    'process_inspection.php'
];

$is_inspection_page = in_array($current_script, $inspection_pages, true);

if ($admin_role === 'staff' && !$is_inspection_page) {
    header("Location: ../admin/inspections.php?error=staff_restricted");
    exit();
}

if ($admin_role === 'admin' && $is_inspection_page) {
    header("Location: ../admin/dashboard.php?error=admin_inspection_restricted");
    exit();
}

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

$_SESSION['last_activity'] = time();
?>
