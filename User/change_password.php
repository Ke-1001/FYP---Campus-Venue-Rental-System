<?php
// This section prepares the user change password page.
session_start();
require_once __DIR__ . '/../config/db.php';
if (!isset($_SESSION['uid']))
{ header("Location: user_login.php"); exit(); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Password Update | CVBMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/user_css.css?v=2.8">
</head>
<body class="profile-dark-theme bg-slate-900 font-sans antialiased min-h-screen flex items-center justify-center p-4">

<div class="profile-page-bg" aria-hidden="true"></div>

<div id="toast-success" class="fixed top-10 z-50 bg-emerald-600 text-white px-6 py-3 rounded-xl shadow-2xl font-bold hidden animate-pulse">Password updated!</div>
<div id="toast-error" class="fixed top-10 z-50 bg-red-600 text-white px-6 py-3 rounded-xl shadow-2xl font-bold hidden animate-pulse">Incorrect current password!</div>
<div id="toast-same-password" class="fixed top-10 z-50 bg-yellow-500 text-white px-6 py-3 rounded-xl shadow-2xl font-bold hidden animate-pulse">New password cannot be the same as current password!</div>

<div class="relative z-10 w-full max-w-md">
    <div class="text-center mb-6">
        <h1 class="text-3xl font-black text-white">Change <span class="text-blue-500">Password</span></h1>
        <p class="text-slate-400 text-sm font-medium mt-1">Update your password here</p>
    </div>

    <div class="glass-panel rounded-3xl p-8">
        <form action="change_password_process.php" method="POST" class="space-y-5">
            <div>
                <label class="block text-[11px] font-bold text-slate-300 uppercase mb-1.5 ml-1">Current Password</label>
                <div class="input-wrapper">
                    <input type="password" id="currPass" name="current_password" required placeholder="••••••••" class="input-glass">
                    <i data-lucide="eye" class="toggle-btn" onclick="togglePass('currPass', this)"></i>
                </div>
            </div>

            <div class="pt-2 border-t border-white/5">
                <label class="block text-[11px] font-bold text-slate-300 uppercase mb-1.5 ml-1">New Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="new_password" required oninput="evaluateEntropy()" placeholder="••••••••" class="input-glass">
                    <i data-lucide="eye" class="toggle-btn" onclick="togglePass('password', this)"></i>
                </div>

                <div class="w-full h-1.5 bg-white/10 rounded-full mt-3 overflow-hidden">
                    <div id="entropy-bar" class="h-full bg-emerald-500 transition-all duration-300 shadow-[0_0_10px_rgba(16,185,129,0.5)]" style="width: 0%;"></div>
                </div>

                <div class="rule-grid bg-black/20 p-3 rounded-xl border border-white/5 mt-3">
                    <div id="rule-length" class="rule-item rule-invalid"><span class="mr-2"></span> 8+ Characters</div>
                    <div id="rule-upper" class="rule-item rule-invalid"><span class="mr-2"></span> Uppercase</div>
                    <div id="rule-lower" class="rule-item rule-invalid"><span class="mr-2"></span> Lowercase</div>
                    <div id="rule-number" class="rule-item rule-invalid"><span class="mr-2"></span> Number</div>
                    <div id="rule-special" class="rule-item rule-invalid"><span class="mr-2"></span> Special Character</div>
                </div>
            </div>

            <button type="submit" id="submitBtn" disabled class="w-full bg-white hover:bg-slate-100 disabled:bg-slate-600 disabled:text-slate-400 text-slate-900 font-black py-4 rounded-2xl transition-all shadow-lg">
                Update Password
            </button>

            <a href="profile.php" class="block text-center text-blue-400 text-sm font-bold py-2 hover:text-white transition">← Return to Profile</a>
        </form>
    </div>
</div>

<script>
    lucide.createIcons();
    function togglePass(id, icon)
    {
        const input = document.getElementById(id);
        const isPass = input.type === "password";
        input.type = isPass ? "text" : "password";
        icon.setAttribute('data-lucide', isPass ? 'eye-off' : 'eye');
        lucide.createIcons();
    }

    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    if (status === 'success')
    {
        const t = document.getElementById('toast-success');
        t.classList.remove('hidden');
        setTimeout(() => window.location.href = 'profile.php', 2000);
    }
    else if (status === 'error')
    {
        const t = document.getElementById('toast-error');
        t.classList.remove('hidden');
        setTimeout(() => t.classList.add('hidden'), 3000);
    }
    else if (status === 'same_password')
    {
        const t = document.getElementById('toast-same-password');
        t.classList.remove('hidden');
        setTimeout(() => t.classList.add('hidden'), 3000);
    }

    function evaluateEntropy()
    {
        const p = document.getElementById('password').value;
        const v =
{ length: p.length >= 8, upper: /[A-Z]/.test(p), lower: /[a-z]/.test(p), number: /\d/.test(p), special: /[!@#$%^&*(),.?":{}|<>]/.test(p) };
        let score = 0;
        ['length', 'upper', 'lower', 'number', 'special'].forEach(rule =>
        {
            const el = document.getElementById('rule-' + rule);
            const valid = v[rule];
            el.className = `rule-item rule-${valid ? 'valid' : 'invalid'}`;
            el.innerHTML = `<span class="mr-2">${valid ? '✓' : '✗'}</span> ${el.textContent.split(' ')[1]}`;
            if(valid) score++;
        });
        document.getElementById('entropy-bar').style.width = (score / 5 * 100) + '%';
        document.getElementById('submitBtn').disabled = (score < 5);
    }
</script>
</body>
</html>
