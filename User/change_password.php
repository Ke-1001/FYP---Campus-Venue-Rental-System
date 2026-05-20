<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['uid'])) { header("Location: user_login.php"); exit(); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Password Update | CVBMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
        .bg-fixed-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; }
        .bg-img { width: 100%; height: 100%; object-fit: cover; opacity: 0.3; }
        .glass-panel { background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.15); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .input-wrapper { position: relative; width: 100%; display: flex; align-items: center; }
        .input-glass { background: rgba(255, 255, 255, 0.95); border: 2px solid transparent; transition: all 0.3s ease; color: #1e293b; width: 100%; padding: 12px 40px 12px 16px; border-radius: 12px; font-weight: bold; }
        .input-glass:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25); outline: none; }
        .toggle-btn { position: absolute; right: 12px; cursor: pointer; color: #64748b; height: 20px; width: 20px; }
        .entropy-container { width: 100%; height: 6px; background: rgba(255, 255, 255, 0.1); border-radius: 9999px; overflow: hidden; margin: 12px 0; }
        .entropy-bar { height: 100%; width: 0%; transition: all 0.4s ease; }
        .entropy-weak { background: #ef4444; }
        .entropy-fair { background: #f59e0b; }
        .entropy-strong { background: #10b981; }
        .rule-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
        .rule-item { display: flex; align-items: center; font-size: 11px; font-weight: 600; }
        .rule-invalid { color: #94a3b8; }
        .rule-valid { color: #34d399; }
    </style>
</head>
<body class="bg-slate-950 font-sans antialiased min-h-screen flex items-center justify-center p-4">

<div class="bg-fixed-container">
    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&q=80" class="bg-img">
    <div class="absolute inset-0 bg-slate-900/60"></div>
</div>

<div id="toast-success" class="fixed top-10 z-50 bg-emerald-600 text-white px-6 py-3 rounded-xl shadow-2xl font-bold hidden animate-pulse">Password updated!</div>
<div id="toast-error" class="fixed top-10 z-50 bg-red-600 text-white px-6 py-3 rounded-xl shadow-2xl font-bold hidden animate-pulse">Incorrect current password!</div>

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
                
                <div class="entropy-container"><div id="entropy-bar" class="entropy-bar"></div></div>

                <div class="rule-grid bg-black/20 p-3 rounded-xl border border-white/5">
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
    function togglePass(id, icon) {
        const input = document.getElementById(id);
        const isPass = input.type === "password";
        input.type = isPass ? "text" : "password";
        icon.setAttribute('data-lucide', isPass ? 'eye-off' : 'eye');
        lucide.createIcons();
    }

    // Toast Logic
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    if (status === 'success') {
        const t = document.getElementById('toast-success');
        t.classList.remove('hidden');
        setTimeout(() => window.location.href = 'profile.php', 2000);
    } else if (status === 'error') {
        document.getElementById('toast-error').classList.remove('hidden');
        setTimeout(() => document.getElementById('toast-error').classList.add('hidden'), 3000);
    }

    function evaluateEntropy() {
        const p = document.getElementById('password').value;
        const v = { length: p.length >= 8, upper: /[A-Z]/.test(p), lower: /[a-z]/.test(p), number: /\d/.test(p), special: /[!@#$%^&*(),.?":{}|<>]/.test(p) };
        let score = 0;
        ['length', 'upper', 'lower', 'number', 'special'].forEach(rule => {
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