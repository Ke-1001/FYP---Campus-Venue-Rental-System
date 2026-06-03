<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure this path points exactly to the folder where 'vendor' is located
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include("../config/db.php");

$mail = new PHPMailer(true);

try {
    // Outlook/Office365 SMTP Configuration
    $mail->isSMTP();
    $mail->Host       = 'smtp.office365.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'YOUR_STUDENT_ID@student.mmu.edu.my';
    // Use the App Password you generated in Step 1
    $mail->Password   = 'YOUR_GENERATED_APP_PASSWORD'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('YOUR_STUDENT_ID@student.mmu.edu.my', 'CVBMS Admin');
    $mail->addAddress($_POST['email']);

    $mail->isHTML(true);
    $mail->Subject = 'Password Reset Request';
    $mail->Body    = "Click here to reset: <a href='http://localhost/FYP/User/reset_password.php'>Reset Password</a>";

    $mail->send();
    
    header("Location: ../User/password_reset_sent.php");
    exit();

} catch (Exception $e) {
    echo "SMTP Error: {$mail->ErrorInfo}";
}
?>