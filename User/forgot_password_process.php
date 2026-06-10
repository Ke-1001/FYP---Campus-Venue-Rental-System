<?php
include("../config/db.php");
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .glass-panel { background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.15); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
    </style>
</head>
<body class="font-sans antialiased min-h-screen relative flex items-center justify-center">
    <div class="fixed inset-0 z-0">
        <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&q=80" alt="Campus" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-slate-900/80 mix-blend-multiply"></div>
    </div>

    <div class="relative z-10 w-full max-w-sm glass-panel rounded-2xl p-8 shadow-2xl text-center">
        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $query = "SELECT * FROM user WHERE email = '$email'";
            $result = mysqli_query($conn, $query);

            if (mysqli_num_rows($result) > 0) {
                $token = bin2hex(random_bytes(32));
                $token_hash = hash("sha256", $token);

                $stmt = $conn->prepare("INSERT INTO password_resets (email, token_hash, expires_at) 
                                        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))
                                        ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash), expires_at = VALUES(expires_at)");
                $stmt->bind_param("ss", $email, $token_hash);
                $stmt->execute();

                $resetLink = "http://localhost/FYP/User/reset_password.php?token=" . $token;
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; $mail->SMTPAuth = true;
                $mail->Username = 'noreply.cvbms@gmail.com'; $mail->Password = 'mtlg hlqw gcrq wsuw'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; $mail->Port = 587;
                $mail->setFrom('noreply.cvbms@gmail.com', 'System');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Reset Password';
                $mail->Body = "Click link: <a href='$resetLink'>$resetLink</a>";
                
                if ($mail->send()) {
                    echo "<h2 class='text-white font-bold'>Success!</h2><p class='text-slate-300 mt-2'>Reset link sent to your email.</p>";
                } else {
                    echo "<h2 class='text-red-500 font-bold'>Error!</h2><p class='text-slate-300 mt-2'>Failed to send email.</p>";
                }
            } else {
                echo "<h2 class='text-red-500 font-bold'>Error!</h2><p class='text-slate-300 mt-2'>Email not found.</p>";
            }
            echo "<a href='forgot_password.php' class='block mt-6 text-blue-400 font-bold hover:underline'>Back</a>";
        }
        ?>
    </div>
</body>
</html>