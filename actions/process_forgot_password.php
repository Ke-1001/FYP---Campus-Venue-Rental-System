<?php
// File: actions/process_forgot_password.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/mailer.php';

$source_node = $_SERVER['HTTP_REFERER'] ?? '../login.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

    if (empty($email)) {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Security Fault: Invalid email vector.'];
 // Upgrade: redirect back to the source page
        header("Location: " . $source_node);
        exit;
    }

    $conn->begin_transaction();

    try {
        $entity_name = null;
        $entity_found = false;
        $account_type = null;

        // A. Check Admin table
        $stmt = $conn->prepare("SELECT admin_name FROM admin WHERE email = ? AND status = 'active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $entity_name = $res->fetch_assoc()['admin_name'];
            $entity_found = true;
            $account_type = 'admin';
        }
        $stmt->close();

        // B. Check Staff table
        if (!$entity_found) {
            $stmt = $conn->prepare("SELECT staff_name FROM staff WHERE email = ? AND status = 'active'");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $entity_name = $res->fetch_assoc()['staff_name'];
                $entity_found = true;
                $account_type = 'staff';
            }
            $stmt->close();
        }

 // 2. Create token when needed (Conditional Token Issuance)
        if ($entity_found) {
 // Create a secure time-limited token
            $token = bin2hex(random_bytes(32)); 
            $token_hash = hash('sha256', $token); 

 // Clear old expired tokens for this email to avoid database bloat
            $stmt_clean = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt_clean->bind_param("s", $email);
            $stmt_clean->execute();
            $stmt_clean->close();

 // Save new token with a 1-hour TTL (use MySQL DATE_ADD for timezone consistency)
            $stmt_token = $conn->prepare("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
            $stmt_token->bind_param("ss", $email, $token_hash);
            $stmt_token->execute();
            $stmt_token->close();

 // 3. SMTP sending process
            $reset_link = getAppBaseUrl() . "/admin/setup_password.php?token=" . $token;

            $subject = "Security Alert: CVBMS System Credential Recovery";
            $message = "Hello {$entity_name},\n\n" . "A request to reconfigure the cryptographic credentials for your account has been initiated.\n\n" . "To execute the reset protocol, please access the following secure link:\n\n" . $reset_link . "\n\n" . "Note: This authorization vector will expire in 1 hour. If you did not initiate this request, you may safely ignore this email.\n\n" . "CVBMS Automated Security System";

            dispatchSystemEmail($email, $entity_name, $subject, $message);
        }

        $conn->commit();

 // 4. Final redirect (Terminal Routing Shift)
        $_SESSION['recovery_email'] = $email;
 // After sending succeeds, redirect to the check email page
        header("Location: ../check_email.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'System Execution Fault. Please try again later.'];
 // Upgrade: when a database or SMTP error happens, dynamicredirect
        header("Location: " . $source_node);
        exit;
    }
} else {
 // Upgrade: when blocking non-POST requestsdynamicredirect
    header("Location: " . $source_node);
    exit;
}