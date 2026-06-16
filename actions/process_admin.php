<?php
// This section checks and processes admin requests.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST')
{
    $aid = intval($_POST['aid'] ?? 0);
    $admin_name = htmlspecialchars(trim($_POST['admin_name']));
    $email = trim($_POST['email']);
    $phone_num = htmlspecialchars(trim($_POST['phone_num']));
    if ($aid === 0)
    {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Fatal: Invalid Identity Parameter.'];
        header("Location: ../admin/manage_admins.php");
        exit;
    }
    $sql_check = "SELECT aid FROM admin WHERE (email = ? OR admin_name = ?) AND aid != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ssi", $email, $admin_name, $aid);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0)
    {
        $_SESSION['toast'] =
        [
            'type' => 'error',
            'msg' => 'Update Blocked: Email or Administrator Name already belongs to another entity.'
        ];
        $stmt_check->close();
        header("Location: ../admin/manage_admins.php");
        exit;
    }
    $stmt_check->close();
    $sql = "UPDATE admin SET admin_name = ?, email = ?, phone_num = ? WHERE aid = ? AND role IN ('admin', 'super_admin')";
    $stmt = $conn->prepare($sql);
    if ($stmt)
    {
        $stmt->bind_param("sssi", $admin_name, $email, $phone_num, $aid);
        if ($stmt->execute())
        {
            $_SESSION['toast'] = ['type' => 'success', 'msg' => "Configuration Deployed: Administrator [ID: {$aid}] updated."];
        }
        else
        {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Fault: ' . $stmt->error];
        }
        $stmt->close();
    }
    header("Location: ../admin/manage_admins.php");
    exit;
}
else if ($action === 'delete' && isset($_GET['aid']))
{
    if ($_SESSION['role'] !== 'super_admin')
    {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Access Denied: Only Super Administrator can revoke nodes.'];
        header("Location: ../admin/manage_admins.php");
        exit;
    }
    $aid = intval($_GET['aid']);
    $sql_check = "SELECT role FROM admin WHERE aid = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $aid);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    if ($result->num_rows > 0)
    {
        $target = $result->fetch_assoc();
        if ($target['role'] === 'super_admin')
        {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Violation: Root immutable node (super_admin) cannot be terminated.'];
            $stmt_check->close();
            header("Location: ../admin/manage_admins.php");
            exit;
        }
    }
    else
    {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Entity not found.'];
        header("Location: ../admin/manage_admins.php");
        exit;
    }
    $stmt_check->close();
    $sql = "DELETE FROM admin WHERE aid = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt)
    {
        $stmt->bind_param("i", $aid);
        if ($stmt->execute())
        {
            $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Node Terminated: Administrative privileges revoked globally.'];
        }
        else
        {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Deletion Fault: ' . $stmt->error];
        }
        $stmt->close();
    }
    header("Location: ../admin/manage_admins.php");
    exit;
}
else
{
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Malformed request vector.'];
    header("Location: ../admin/manage_admins.php");
    exit;
}
?>
