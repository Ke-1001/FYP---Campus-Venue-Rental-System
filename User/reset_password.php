<?php
require_once __DIR__ . '/../config/db.php';

$token = $_GET['token'] ?? '';
$token_hash = hash("sha256", $token);

$stmt = $conn->prepare("SELECT email FROM password_resets WHERE token_hash = ? AND expires_at > NOW()");
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) { 
    die("<div class='text-white p-10 text-center'>Invalid or expired reset link. <br><a href='user_login.php' class='text-blue-400'>Back to Login</a></div>"); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set New Password - CVBMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/user_css.css?v=2.4">
</head>
<body class="user-light-theme font-sans antialiased min-h-screen relative">
    <div class="user-page-bg" aria-hidden="true"></div>

    <div class="relative z-10 flex items-center justify-center min-h-screen px-4">
        <div class="w-full max-w-sm glass-panel rounded-2xl p-8 shadow-2xl">
            <h2 class="text-white text-xl font-bold mb-6 text-center">Set New Password</h2>
                <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): 
                    $password = $_POST['password'] ?? '';

                    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>]).{8,}$/';

                    if (!preg_match($pattern, $password)) {
                        die("<div class='text-white p-10 text-center'>Password does not meet the security requirements.<br><a href='reset_password.php?token=" . htmlspecialchars($token) . "' class='text-blue-400'>Try again</a></div>");
                    }

                    $new_pass = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $conn->prepare("UPDATE user SET password = ? WHERE email = ?");
                    $stmt->bind_param("ss", $new_pass, $user['email']);
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                    $stmt->bind_param("s", $user['email']);
                    $stmt->execute();
                    $stmt->close();
                ?>
                <p class="text-emerald-400 font-bold text-center">Password updated successfully!</p>
                <div class="mt-4 text-center"><a href="user_login.php" class="text-slate-400 hover:text-white font-bold">Back to Login</a></div>
            <?php else: ?>
                <form method="POST" class="space-y-4">
                    <div class="relative">
                        <label class="block text-[12px] font-bold text-slate-300 uppercase tracking-wider mb-1.5">New Password</label>
                        <div class="relative">
                            <input type="password" name="password" id="password" required oninput="checkPassword()"
                                   class="w-full px-4 py-3 rounded-xl text-slate-800 font-semibold text-sm focus:ring-2 focus:ring-blue-500 outline-none pr-12">
                            <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600">
                                <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <ul id="password-requirements" class="mt-3 text-[10px] text-slate-400 grid grid-cols-2 gap-x-2 gap-y-1">
                            <li id="length">● 8+ Characters</li>
                            <li id="upper">● 1 Uppercase</li>
                            <li id="lower">● 1 Lowercase</li>
                            <li id="number">● 1 Number</li>
                            <li id="special" class="col-span-2">● 1 Special Character</li>
                        </ul>
                    </div>
                    <button type="submit" id="submit-btn" disabled class="w-full bg-slate-600 cursor-not-allowed text-white font-bold py-3 rounded-xl transition-all">
                        Update Password
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            input.type = (input.type === "password") ? "text" : "password";
        }

        function checkPassword() {
            const val = document.getElementById('password').value;
            const btn = document.getElementById('submit-btn');
            
            const reqs = {
                length: val.length >= 8,
                upper: /[A-Z]/.test(val),
                lower: /[a-z]/.test(val),
                number: /[0-9]/.test(val),
                special: /[!@#$%^&*(),.?":{}|<>]/.test(val)
            };

            for (let id in reqs) {
                document.getElementById(id).style.color = reqs[id] ? '#10b981' : '#f87171';
            }

            if (Object.values(reqs).every(Boolean)) {
                btn.disabled = false;
                btn.className = "w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg active:scale-95";
            } else {
                btn.disabled = true;
                btn.className = "w-full bg-slate-600 cursor-not-allowed text-white font-bold py-3 rounded-xl transition-all";
            }
        }
    </script>
</body>
</html>