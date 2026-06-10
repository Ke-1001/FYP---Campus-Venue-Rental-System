<?php
include("../config/db.php");

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
    <style>
        .glass-panel { background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.15); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
    </style>
</head>
<body class="font-sans antialiased min-h-screen relative">
    <div class="fixed inset-0 z-0">
        <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&q=80" alt="Campus" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-slate-900/80 mix-blend-multiply"></div>
    </div>

    <div class="relative z-10 flex items-center justify-center min-h-screen px-4">
        <div class="w-full max-w-sm glass-panel rounded-2xl p-8 shadow-2xl">
            <h2 class="text-white text-xl font-bold mb-6 text-center">Set New Password</h2>
            
            <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): 
                $new_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $conn->prepare("UPDATE user SET password = ? WHERE email = ?")->execute([$new_pass, $user['email']]);
                $conn->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$user['email']]);
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