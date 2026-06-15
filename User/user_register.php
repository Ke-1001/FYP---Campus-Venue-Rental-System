<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - CVBMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { mmu: { core: '#004aad', glow: '#3b82f6' } },
                    fontFamily: { sans: ['Century Gothic', 'CenturyGothic', 'Century', 'Arial', 'sans-serif'] }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="../assets/css/user_css.css?v=1.1">
</head>
<body class="font-sans antialiased min-h-screen relative overflow-y-auto">

<?php if (isset($_GET['status'])): ?>
    <div id='toast' class='fixed top-5 right-5 z-50 px-6 py-4 rounded-xl text-white font-bold shadow-2xl <?php echo $_GET['status'] == 'success' ? 'bg-emerald-500' : 'bg-red-500'; ?>'>
        <?php echo htmlspecialchars($_GET['msg']); ?>
    </div>
    <script>
        setTimeout(() => { 
            const toast = document.getElementById('toast');
            toast.style.display = 'none'; 
            <?php if ($_GET['status'] == 'success') echo "window.location.href = 'user_login.php';"; ?>
        }, 3000);
    </script>
<?php endif; ?>

<div class="fixed inset-0 z-0">
    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&q=80" alt="Campus" class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-slate-900/80 mix-blend-multiply"></div>
</div>

<div class="relative z-10 flex flex-col items-center justify-center min-h-screen px-4 py-12">
    <div class="w-full max-w-lg glass-panel rounded-2xl p-8 shadow-2xl">
        <form action="../User/user_register_process.php" method="POST" id="regForm" class="space-y-4">
            <div>
                <div class="flex justify-between items-center mb-1.5">
                    <label class="block text-[14px] font-bold text-slate-300 uppercase tracking-wider">Student ID</label>
                    <span id="uid-error" class="validation-error">Invalid Student ID</span>
                </div>
                <input type="text" name="uid" id="uid" placeholder="e.g. 242DT2430C" required class="input-glass w-full px-4 py-3 rounded-xl text-slate-800 font-semibold text-sm">
            </div>
            <div>
                <div class="flex justify-between items-center mb-1.5">
                    <label class="block text-[14px] font-bold text-slate-300 uppercase tracking-wider">Email Address</label>
                    <span id="email-error" class="validation-error">Invalid Email Format</span>
                </div>
                <input type="email" name="email" id="email" placeholder="student@student.mmu.edu.my" required class="input-glass w-full px-4 py-3 rounded-xl text-slate-800 font-semibold text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-[14px] font-bold text-slate-300 uppercase tracking-wider mb-1.5">Full Name</label><input type="text" name="username" required class="input-glass w-full px-4 py-3 rounded-xl text-slate-800 font-semibold text-sm"></div>
                <div class="relative">
                    <label class="block text-[14px] font-bold text-slate-300 uppercase tracking-wider mb-1.5">Phone Number</label>
                    <div id="phoneToast" class="hidden absolute -top-1 right-0 bg-red-600 text-white text-[12px] px-3 py-1 rounded-lg z-50">Only numbers allowed!</div>
                    <input type="tel" name="phone_num" placeholder="01X-XXXXXXX" required oninput="validatePhone(this)" class="input-glass w-full px-4 py-3 rounded-xl text-slate-800 font-semibold text-sm">
                </div>
            </div>
            <div class="pt-2">
                <label class="block text-[14px] font-bold text-slate-300 uppercase tracking-wider mb-1.5">Password</label>
                <div class="relative">
                    <input type="password" name="password" id="password" placeholder="••••••••" required oninput="evaluateEntropy()" class="input-glass w-full px-4 py-3 rounded-xl text-slate-800 font-semibold text-sm pr-12">
                    <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 px-4 flex items-center text-slate-400">
                        <i id="eyeIcon" data-lucide="eye" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="entropy-container"><div id="entropy-bar" class="entropy-bar entropy-weak"></div></div>
                <div class="rule-grid bg-black/20 p-3.5 rounded-xl border border-white/5">
                    <div id="rule-length" class="rule-item rule-invalid"><span class="rule-icon"></span> 8+ Characters</div>
                    <div id="rule-upper" class="rule-item rule-invalid"><span class="rule-icon"></span> 1 Upper Case</div>
                    <div id="rule-lower" class="rule-item rule-invalid"><span class="rule-icon"></span> 1 Lower Case</div>
                    <div id="rule-number" class="rule-item rule-invalid"><span class="rule-icon"></span> 1 Number</div>
                    <div id="rule-special" class="rule-item rule-invalid"><span class="rule-icon"></span> 1 Special Character</div>
                </div>
            </div>
            <button type="submit" id="submitBtn" class="w-full mt-6 bg-mmu-core hover:bg-blue-800 text-white font-bold py-3.5 rounded-xl transition-all">Create Account</button>
        </form>
    </div>
</div>

<script>
    lucide.createIcons();
    function validatePhone(input) {
        if (/[^0-9]/.test(input.value)) {
            input.value = input.value.replace(/[^0-9]/g, '');
            const toast = document.getElementById('phoneToast');
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 2000);
        }
    }
    const uidInput = document.getElementById('uid');
    const emailInput = document.getElementById('email');
    uidInput.addEventListener('input', () => {
        const regex = /^[0-9]{3}[A-Za-z]{2}[A-Za-z0-9]{5}$/;
        const errorEl = document.getElementById('uid-error');
        errorEl.style.display = (uidInput.value.length === 0) ? 'none' : (regex.test(uidInput.value) ? 'none' : 'block');
    });
    emailInput.addEventListener('input', () => {
        const regex = /@student\.mmu\.edu\.my$/;
        const errorEl = document.getElementById('email-error');
        errorEl.style.display = (emailInput.value.length === 0) ? 'none' : (regex.test(emailInput.value) ? 'none' : 'block');
    });
    function togglePasswordVisibility() {
        const p = document.getElementById('password');
        const eye = document.getElementById('eyeIcon');
        if (p.type === 'password') { p.type = 'text'; eye.setAttribute('data-lucide', 'eye-off'); }
        else { p.type = 'password'; eye.setAttribute('data-lucide', 'eye'); }
        lucide.createIcons();
    }
    function evaluateEntropy() {
        const p = document.getElementById('password').value;
        const v = { length: p.length >= 8, upper: /[A-Z]/.test(p), lower: /[a-z]/.test(p), number: /\d/.test(p), special: /[@$!%*?&]/.test(p) };
        let score = 0;
        const setUI = (id, valid) => {
            const el = document.getElementById(id);
            if (valid) { score++; el.className = 'rule-item rule-valid'; el.querySelector('.rule-icon').textContent = '✓'; }
            else { el.className = 'rule-item rule-invalid'; el.querySelector('.rule-icon').textContent = '✗'; }
        };
        setUI('rule-length', v.length); setUI('rule-upper', v.upper); setUI('rule-lower', v.lower); setUI('rule-number', v.number); setUI('rule-special', v.special);
        const bar = document.getElementById('entropy-bar');
        bar.style.width = (score / 5) * 100 + '%';
        bar.className = 'entropy-bar ' + (score <= 2 ? 'entropy-weak' : score <= 4 ? 'entropy-fair' : 'entropy-strong');
    }
</script>
</body>
</html>