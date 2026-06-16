<?php
// This section provides shared mailer logic or layout.
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


function dispatchSystemEmail($to_email, $to_name, $subject, $body)
{

    $mail = new PHPMailer(true);

    try
    {

 $mail->isSMTP();
 $mail->Host = 'smtp.gmail.com';
 $mail->SMTPAuth = true;


        $mail->Username   = 'noreply.cvbms@gmail.com';
        $mail->Password   = 'mtlg hlqw gcrq wsuw';

 $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            )
);


        $mail->setFrom('noreply.cvbms@gmail.com', 'CVBMS Automated System');
        $mail->addAddress($to_email, $to_name);


 $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $body;


        $mail->send();
        return true;

    } catch (Exception $e)
    {

 echo "<h3>SMTP connection failed. Error log:</h3>";
        echo "<pre>{$mail->ErrorInfo}</pre>";
        exit;
    }
}
?>
