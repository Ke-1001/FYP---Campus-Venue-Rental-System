<?php
// File: User/forgot_password_process.php

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: forgot_password.php");
    exit;
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

if (empty($email)) {
    $_SESSION['error'] = "Please enter your email address.";
    header("Location: forgot_password.php");
    exit;
}

try {
    $stmt = $conn->prepare("SELECT username FROM user WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $token_hash = hash("sha256", $token);

        $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("
            INSERT INTO password_resets (email, token_hash, expires_at)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))
        ");
        $stmt->bind_param("ss", $email, $token_hash);
        $stmt->execute();
        $stmt->close();

        $reset_link = getAppBaseUrl() . "/User/reset_password.php?token=" . urlencode($token);

        $subject = "Reset Your CVBMS Password";
        $message = "Hello {$user['username']},\n\n" . "We received a request to reset your CVBMS account password.\n\n" . "Please click the link below to set a new password:\n\n" . $reset_link . "\n\n" . "This link will expire in 1 hour.\n\n" . "If you did not request this, you can ignore this email.\n\n" . "CVBMS System";

        dispatchSystemEmail($email, $user['username'], $subject, $message);
    }

    $_SESSION['recovery_email'] = $email;
    header("Location: ../check_email.php");
    exit;

} catch (Exception $e) {
    $_SESSION['error'] = "Unable to process password reset request. Please try again.";
    header("Location: forgot_password.php");
    exit;
}