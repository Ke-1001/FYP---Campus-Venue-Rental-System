<?php
// File: includes/mailer.php

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Global system mail sender (Global SMTP Dispatch Engine)
 * * @param string $to_email target email
 * @param string $to_name target name
 * @param string $subject email subject
 * @param string $body plain text email body
 * @return bool send status (True ≡ Success, False ≡ Failure)
 */
function dispatchSystemEmail($to_email, $to_name, $subject, $body) {
 // Create mailer and enable error handling
    $mail = new PHPMailer(true);

    try {
 // 1. Server SMTP settings (Server Topology Settings)
 $mail->isSMTP(); // Use SMTP
 $mail->Host = 'smtp.gmail.com'; // replace with company SMTP server (Gmail is used here as example)
 $mail->SMTPAuth = true; // Enable SMTP password authentication
        
 // Warning: Security note: replace this with a dedicated sender account and App Password (App Password)
        $mail->Username   = 'noreply.cvbms@gmail.com';          
        $mail->Password   = 'mtlg hlqw gcrq wsuw';           
        
 $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use TLS encryption
        $mail->Port       = 587;  
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            )
); // STARTTLS standard port

 // 2. Sender and recipient setup (Entity Address Definition)
        $mail->setFrom('noreply.cvbms@gmail.com', 'CVBMS Automated System');
        $mail->addAddress($to_email, $to_name);

 // 3. Email content setup (Payload Construction)
 $mail->isHTML(false); // Use plain text to avoid XSS and improve delivery
        $mail->Subject = $subject;
        $mail->Body    = $body;

 // Send email
        $mail->send();
        return true;

    } catch (Exception $e) {
 // Temporary debug: print the error and stop to prevent redirect
 echo "<h3>SMTP connection failed. Error log:</h3>";
        echo "<pre>{$mail->ErrorInfo}</pre>";
        exit; 
    }
}
?>