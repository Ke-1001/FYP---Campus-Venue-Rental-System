<?php
// This section checks and updates personnel account details.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $action = $_POST['action'] ?? '';
 $table = $_POST['target_table'] ?? '';
    $id = intval($_POST['entity_id'] ?? 0);
    $name = htmlspecialchars(trim($_POST['full_name'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
    $access_level = $_POST['access_level'] ?? '';
    $raw_password = $_POST['password'] ?? '';
    if ($action !== 'update' || $id === 0 || !in_array($table, ['admin', 'staff']))
    {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Execution Fault: Invalid update payload or missing entity reference.'];
        header("Location: ../admin/staff_directory.php");
        exit;
    }
    $conn->begin_transaction();
    try
    {
        $aid_exclude = ($table === 'admin') ? $id : 0;
        $sid_exclude = ($table === 'staff') ? $id : 0;
        $chk_a = $conn->prepare("SELECT aid FROM admin WHERE email = ? AND aid != ?");
        $chk_a->bind_param("si", $email, $aid_exclude);
        $chk_a->execute();
        if ($chk_a->get_result()->num_rows > 0) throw new Exception("Conflict: Email is already assigned to another Administrator.");
        $chk_a->close();
        $chk_s = $conn->prepare("SELECT sid FROM staff WHERE email = ? AND sid != ?");
        $chk_s->bind_param("si", $email, $sid_exclude);
        $chk_s->execute();
        if ($chk_s->get_result()->num_rows > 0) throw new Exception("Conflict: Email is already assigned to another Staff member.");
        $chk_s->close();
        $role_col = ($table === 'admin') ? 'role' : 'position';
        $id_col = ($table === 'admin') ? 'aid' : 'sid';
        $name_col = ($table === 'admin') ? 'admin_name' : 'staff_name';
        if (!empty($raw_password))
        {
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $raw_password))
            {
                throw new Exception("Security Fault: New password does not meet complexity standards.");
            }
            $new_hash = password_hash($raw_password, PASSWORD_DEFAULT);
            $sql = "UPDATE $table SET $name_col = ?, email = ?, phone_num = ?, $role_col = ?, password = ? WHERE $id_col = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $name, $email, $phone, $access_level, $new_hash, $id);
        } else
        {
            $sql = "UPDATE $table SET $name_col = ?, email = ?, phone_num = ?, $role_col = ? WHERE $id_col = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $name, $email, $phone, $access_level, $id);
        }
        $stmt->execute();
        if ($stmt->affected_rows === 0 && $stmt->error)
        {
            throw new Exception("SQL Mutation Fault: " . $stmt->error);
        }
        $stmt->close();
        $conn->commit();
        $_SESSION['toast'] = ['type' => 'success', 'msg' => "Mutation Success: Configuration for entity [$id] updated."];
    } catch (Exception $e)
    {
        $conn->rollback();
        $_SESSION['toast'] = ['type' => 'error', 'msg' => "Update Aborted: " . $e->getMessage()];
    }
    header("Location: ../admin/staff_directory.php");
    exit;
} else
{
    header("Location: ../admin/staff_directory.php");
    exit;
}
