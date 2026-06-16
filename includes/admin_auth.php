<?php
// This section provides shared admin auth logic or layout.
if (session_status() === PHP_SESSION_NONE)
{
    session_start();
}
$admin_identifier = $_SESSION['aid'] ?? null;
$staff_identifier = $_SESSION['sid'] ?? null;
$admin_role = $_SESSION['role'] ?? '';
$allowed_roles = ['super_admin', 'admin', 'staff'];
if ((!$admin_identifier && !$staff_identifier) || !in_array($admin_role, $allowed_roles, true))
{
    session_unset();
    session_destroy();
    header("Location: ../admin/login.php?error=access_denied");
    exit();
}
global $conn;
if (isset($conn))
{
    if ($admin_role === 'staff')
    {
        $auth_stmt = $conn->prepare("SELECT status FROM staff WHERE sid = ? LIMIT 1");
        $auth_stmt->bind_param("i", $staff_identifier);
    }
    else
    {
        $auth_stmt = $conn->prepare("SELECT status FROM admin WHERE aid = ? LIMIT 1");
        $auth_stmt->bind_param("i", $admin_identifier);
    }
    $auth_stmt->execute();
    $auth_res = $auth_stmt->get_result();
    if ($auth_res->num_rows === 1)
    {
        $auth_row = $auth_res->fetch_assoc();
        if ($auth_row['status'] === 'inactive')
        {
            session_unset();
            session_destroy();
            header("Location: ../admin/login.php?error=account_revoked");
            exit();
        }
    }
    else
    {
        session_unset();
        session_destroy();
        header("Location: ../admin/login.php?error=account_deleted");
        exit();
    }
    $auth_stmt->close();
}
$current_script = basename($_SERVER['SCRIPT_NAME']);
$inspection_pages = [
    'inspections.php',
    'pending_inspections.php',
    'execute_inspection.php',
    'track_inspections.php',
    'process_inspection.php'
];
$staff_allowed_pages = array_merge($inspection_pages, [
    'damage_reports.php'
]);
$identity_pages = [
    'manage_admins.php',
    'add_staff.php',
    'staff_directory.php',
    'admin_directory.php',
    'edit_admin.php',
    'edit_staff.php',
    'manage_students.php',
    'add_student.php',
    'edit_student.php',
    'process_add_staff.php',
    'process_admin.php',
    'process_personnel.php',
    'process_personnel_deletion.php',
    'process_student.php',
    'process_student_action.php',
    'route_personnel.php'
];
$is_inspection_page = in_array($current_script, $inspection_pages, true);
$is_staff_allowed_page = in_array($current_script, $staff_allowed_pages, true);
$is_identity_page = in_array($current_script, $identity_pages, true);
if ($admin_role === 'staff' && !$is_staff_allowed_page)
{
    header("Location: ../admin/inspections.php?error=staff_restricted");
    exit();
}
if ($admin_role === 'admin' && $is_inspection_page)
{
    header("Location: ../admin/dashboard.php?error=admin_inspection_restricted");
    exit();
}
if ($admin_role !== 'super_admin' && $is_identity_page)
{
    $redirect = ($admin_role === 'staff') ? '../admin/inspections.php?error=identity_restricted' : '../admin/dashboard.php?error=identity_restricted';
    header("Location: " . $redirect);
    exit();
}
$timeout_duration = 1800;
if (isset($_SESSION['last_activity']))
{
    $elapsed_time = time() - $_SESSION['last_activity'];
    if ($elapsed_time > $timeout_duration)
    {
        session_unset();
        session_destroy();
        header("Location: ../admin/login.php?error=timeout");
        exit();
    }
}
$_SESSION['last_activity'] = time();
?>
