<?php
session_start();
require_once '../config/db.php';

// Auth Check
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
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { mmu: { core: '#004aad', glow: '#3b82f6' } }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
        .glass-panel { background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.15); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .input-glass { background: rgba(255, 255, 255, 0.95); border: 2px solid transparent; transition: all 0.3s ease; color: #1e293b; }
        .input-glass:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25); outline: none; }
        
        /* Entropy Bar Styles from Register Page */
        .entropy-container { width: 100%; height: 6px; background: rgba(255, 255, 255, 0.1); border-radius: 9999px; overflow: hidden; margin: 12px 0; }
        .entropy-bar { height: 100%; width: 0%; transition: all 0.4s ease; }
        .entropy-weak { background: #ef4444; shadow: 0 0 10px #ef4444; }
        .entropy-fair { background: #f59e0b; shadow: 0 0 10px #f59e0b; }
        .entropy-strong { background: #10b981; shadow: 0 0 10px #10b981; }

        .rule-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
        .rule-item { display: flex; align-items: center; font-size: 11px; font-weight: 600; }
        .rule-invalid { color: #94a3b8; }
        .rule-valid { color: #34d399; text-shadow: 0 0 8px rgba(52, 211, 153, 0.3); }
    </style>
</head>
<body class="bg-slate-950 font-sans antialiased min-h-screen flex items-center justify-center px-4 relative overflow-hidden">

<div class="fixed inset-0 z-0">
    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&q=80" class="w-full h-full object-cover opacity-20">
    <div class="absolute inset-0 bg-slate-900/60"></div>
</div>

<div class="relative z-10 w-full max-w-md">
    <div class="text-center mb-6">
        <h1 class="text-3xl font-black text-white tracking-tight">Change <span class="text-mmu-glow">Password</span></h1>
        <p class="text-slate-400 text-sm font-medium">Update your password here</p>
    </div>

    <div class="glass-panel rounded-3xl p-8">
        <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div class="mb-6 p-3 bg-emerald-500/20 border border-emerald-500/50 rounded-xl text-emerald-200 text-xs font-bold text-center">
                Password updated successfully.
            </div>
        <?php endif; ?>

        <form action="change_password_process.php" method="POST" class="space-y-5">
            <div>
                <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-widest mb-1.5 ml-1">Current Password</label>
                <input type="password" name="current_password" required placeholder="••••••••"
                       class="input-glass w-full px-4 py-3 rounded-xl font-bold tracking-widest text-sm">
            </div>

            <div class="pt-2 border-t border-white/5">
                <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-widest mb-1.5 ml-1">New Password</label>
                <input type="password" name="new_password" id="password" required oninput="evaluateEntropy()" placeholder="••••••••"
                       class="input-glass w-full px-4 py-3 rounded-xl font-bold tracking-widest text-sm">
                
                <div class="entropy-container">
                    <div id="entropy-bar" class="entropy-bar entropy-weak"></div>
                </div>

                <div class="rule-grid bg-black/20 p-3 rounded-xl border border-white/5">
                    <div id="rule-length" class="rule-item rule-invalid"><span class="mr-2">✗</span> 8+ Character</div>
                    <div id="rule-upper" class="rule-item rule-invalid"><span class="mr-2">✗</span> Uppercase</div>
                    <div id="rule-number" class="rule-item rule-invalid"><span class="mr-2">✗</span> Number</div>
                    <div id="rule-special" class="rule-item rule-invalid"><span class="mr-2">✗</span> Special</div>
                </div>
            </div>

            <button type="submit" id="submitBtn" disabled
                    class="w-full bg-mmu-core hover:bg-blue-700 text-white font-black py-4 rounded-2xl shadow-xl transition-all disabled:opacity-30 disabled:cursor-not-allowed flex justify-center items-center gap-2">
                Update Security Key <i data-lucide="shield-check" class="w-4 h-4"></i>
            </button>
            
            <a href="profile.php" class="block text-center text-slate-400 text-xs font-bold hover:text-white transition">
                Return to Profile
            </a>
        </form>
    </div>
</div>

<script>
    lucide.createIcons();

    function evaluateEntropy() {
        const p = document.getElementById('password').value;
        const v = {
            length: p.length >= 8,
            upper: /[A-Z]/.test(p),
            number: /\d/.test(p),
            special: /[@$!%*?&]/.test(p)
        };
        
        let score = 0;
        const updateUI = (id, valid) => {
            const el = document.getElementById(id);
            if(valid) { score++; el.className = 'rule-item rule-valid'; el.firstChild.textContent = '✓ '; }
            else { el.className = 'rule-item rule-invalid'; el.firstChild.textContent = '✗ '; }
        };

        updateUI('rule-length', v.length);
        updateUI('rule-upper', v.upper);
        updateUI('rule-number', v.number);
        updateUI('rule-special', v.special);

        const bar = document.getElementById('entropy-bar');
        bar.style.width = (score / 4 * 100) + '%';
        
        if(score <= 1) bar.className = 'entropy-bar entropy-weak';
        else if(score <= 3) bar.className = 'entropy-bar entropy-fair';
        else bar.className = 'entropy-bar entropy-strong';

        document.getElementById('submitBtn').disabled = (score < 4);
    }
</script>
</body>
</html>