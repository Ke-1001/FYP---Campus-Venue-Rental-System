<?php

// This section checks and saves the new account password.
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($token) || empty($password) || empty($confirm_password))
    {
        die("Security Fault: Missing required payload vectors.");
    }
    if ($password !== $confirm_password)
    {
        die("Symmetry Fault: Passwords do not match.");
    }

    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
    if (!preg_match($pattern, $password))
    {
        die("Security Fault: Password complexity does not meet protocol standards.");
    }

    $conn->begin_transaction();

    try
    {
        $token_hash = hash('sha256', $token);
        $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token_hash = ? AND expires_at > NOW()");
        $stmt->bind_param("s", $token_hash);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0)
        {
            throw new Exception("Cryptographic Execution Failed: Invalid or expired token.");
        }
        $email = $result->fetch_assoc()['email'];
        $stmt->close();

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $update_success = false;

        $stmt_admin = $conn->prepare("UPDATE admin SET password = ? WHERE email = ?");
        $stmt_admin->bind_param("ss", $password_hash, $email);
        $stmt_admin->execute();
        if ($stmt_admin->affected_rows > 0)
        {
            $update_success = true;
        }
        $stmt_admin->close();

        if (!$update_success)
        {
            $stmt_staff = $conn->prepare("UPDATE staff SET password = ? WHERE email = ?");
            $stmt_staff->bind_param("ss", $password_hash, $email);
            $stmt_staff->execute();
            if ($stmt_staff->affected_rows > 0)
            {
                $update_success = true;
            }
            $stmt_staff->close();
        }

        if (!$update_success)
        {
            throw new Exception("Entity Resolution Fault: Target email not found in active operational tables.");
        }

        $stmt_del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt_del->bind_param("s", $email);
        $stmt_del->execute();
        $stmt_del->close();

        $conn->commit();

        $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Credential vector configured successfully. You may now authenticate.'];
        header("Location: ../admin/login.php");
        exit;

    }
    catch (Exception $e)
    {
        $conn->rollback();
        die($e->getMessage());
    }
}
else
{
    header("Location: ../admin/login.php");
    exit;
}
