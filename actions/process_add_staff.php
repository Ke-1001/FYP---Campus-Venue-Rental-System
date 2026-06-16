<?php

// This section checks and saves staff account details.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $full_name = htmlspecialchars(trim($_POST['full_name'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone_num = htmlspecialchars(trim($_POST['phone_num'] ?? ''));
    $access_level = $_POST['access_level'] ?? '';

    if (empty($full_name) || empty($email) || empty($access_level))
    {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Validation Fault: Essential data vectors cannot be null.'];
        header("Location: ../admin/add_staff.php");
        exit;
    }

    if (!preg_match('/^[a-zA-Z0-9._%+-]+@([a-zA-Z0-9.-]+\.)?mmu\.edu\.my$/', $email))
    {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Security Exception: Invalid identity vector. Only MMU institutional domains are permitted.'];
        header("Location: ../admin/add_staff.php");
        exit;
    }

    $conn->begin_transaction();

    try
    {
        $stmt_chk_a = $conn->prepare("SELECT aid FROM admin WHERE email = ?");
        $stmt_chk_a->bind_param("s", $email);
        $stmt_chk_a->execute();
        if ($stmt_chk_a->get_result()->num_rows > 0) throw new Exception("Conflict: Email is already registered as an Admin.");
        $stmt_chk_a->close();

        $stmt_chk_s = $conn->prepare("SELECT sid FROM staff WHERE email = ?");
        $stmt_chk_s->bind_param("s", $email);
        $stmt_chk_s->execute();
        if ($stmt_chk_s->get_result()->num_rows > 0) throw new Exception("Conflict: Email is already assigned to a Staff member.");
        $stmt_chk_s->close();

        $dummy_password = bin2hex(random_bytes(32));
        $password_hash = password_hash($dummy_password, PASSWORD_DEFAULT);
        $new_id = 0;

        if (in_array($access_level, ['super_admin', 'admin']))
        {
            $stmt = $conn->prepare("INSERT INTO admin (admin_name, email, password, phone_num, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $full_name, $email, $password_hash, $phone_num, $access_level);
            $stmt->execute();
            $new_id = $conn->insert_id;
            $stmt->close();
        }
        elseif ($access_level === 'inspector')
        {
            $stmt = $conn->prepare("INSERT INTO staff (staff_name, email, password, phone_num, position) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $full_name, $email, $password_hash, $phone_num, $access_level);
            $stmt->execute();
            $new_id = $conn->insert_id;
            $stmt->close();
        }
        else
        {
            throw new Exception("Security Exception: Unknown authorization level parameter.");
        }

        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);
        $expires_at = date("Y-m-d H:i:s", time() + 3600);

        $stmt_token = $conn->prepare("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
        $stmt_token->bind_param("ss", $email, $token_hash);
        $stmt_token->execute();
        $stmt_token->close();

        $reset_link = getAppBaseUrl() . "/admin/setup_password.php?token=" . $token;

        $subject = "Action Required: Complete Your CVBMS password Configuration";
        $message = "Hello {$full_name},\n\n" . "An administrator account has been provisioned for you. " . "To activate your account and configure your secure credential vector, please access the following cryptographic link:\n\n" . $reset_link . "\n\n" . "Note: This link will strictly expire in 1 hour. Do not share this URL with anyone.\n\n" . "CVBMS Automated System";

        $mail_sent = dispatchSystemEmail($email, $full_name, $subject, $message);

        if ($mail_sent)
        {
            $smtp_status = "Activation token dispatched to [{$email}].";
            $toast_type = 'success';
        }
        else
        {
            $smtp_status = "Personnel created, but SMTP dispatch failed. Check system logs.";
            $toast_type = 'warning';
        }

        $conn->commit();

        $msg = "Success: Personnel initialized as {$access_level}. " . $smtp_status;
        $_SESSION['toast'] = ['type' => $toast_type, 'msg' => $msg];
        header("Location: ../admin/staff_directory.php");

    }
    catch (Exception $e)
    {
        $conn->rollback();
        $_SESSION['toast'] = ['type' => 'error', 'msg' => $e->getMessage()];
        header("Location: ../admin/add_staff.php");
    }
    exit;
}
else
{
    header("Location: ../admin/staff_directory.php");
    exit;
}
