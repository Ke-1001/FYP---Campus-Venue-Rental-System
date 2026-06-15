<?php
// File: admin/setup_password.php
require_once __DIR__ . '/../config/db.php';

// 💡 1. 提取並驗證權杖向量
$token = $_GET['token'] ?? '';
$is_valid = false;
$email = '';

if (!empty($token)) {
    $token_hash = hash('sha256', $token);
    
    // 驗證權杖是否存在且尚未過期
    $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token_hash = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $is_valid = true;
        $email = $result->fetch_assoc()['email'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU System | Credential Configuration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { fiori: { text: '#1d2d3e', label: '#6b7280', blue: '#0a6ed1' } } } } }
    </script>
    <link rel="stylesheet" href="../assets/css/admin_css.css?v=2.0">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex items-center justify-center">

    <div class="max-w-md w-full mx-4">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-black text-indigo-700 tracking-tight">CVBMS Management</h1>
            <p class="text-sm font-bold text-slate-500 uppercase tracking-widest mt-2">Set New Password</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
            
            <?php if (!$is_valid): ?>
                <div class="p-8 text-center">
                    <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="shield-alert" class="w-8 h-8"></i>
                    </div>
                    <h2 class="text-xl font-bold text-slate-800 mb-2">Link Invalid</h2>
                    <p class="text-sm text-slate-500 mb-6 leading-relaxed">
                        The initialization link provided is either malformed, already consumed, or has exceeded its structural TTL (Time-To-Live) of 1 hour.
                    </p>
                    <a href="../login.php" class="inline-block px-6 py-2.5 bg-slate-100 text-slate-700 text-sm font-bold rounded-lg hover:bg-slate-200 transition">
                        Return to login page
                    </a>
                </div>
            <?php else: ?>
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50">
                    <h2 class="text-lg font-bold text-slate-800">Secure Your Identity</h2>
                    <p class="text-xs text-slate-500 font-mono mt-1">Target Email: <?php echo htmlspecialchars($email); ?></p>
                </div>

                <form action="../actions/process_setup_password.php" method="POST" id="setupForm" class="p-8 space-y-5">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">New Password</label>
                        <div class="relative">
                            <input type="password" name="password" id="password" required onkeyup="validateComplexity()" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:border-indigo-500 outline-none text-sm font-mono transition-all pr-10">
                            <i data-lucide="key" class="w-4 h-4 text-slate-400 absolute right-3 top-3.5 pointer-events-none"></i>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Confirm Password</label>
                        <div class="relative">
                            <input type="password" name="confirm_password" id="confirm_password" required onkeyup="validateComplexity()" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:border-indigo-500 outline-none text-sm font-mono transition-all pr-10">
                            <i data-lucide="shield-check" class="w-4 h-4 text-slate-400 absolute right-3 top-3.5 pointer-events-none"></i>
                        </div>
                        <p id="match-feedback" class="text-xs text-red-500 font-bold mt-1.5 hidden">Symmetry Fault: Passwords do not match.</p>
                    </div>

                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg grid grid-cols-2 gap-2 text-[10px] font-black uppercase tracking-wider">
                        <div id="rule-length" class="validation-tag"><i data-lucide="check" class="w-3 h-3 inline mr-1"></i>Min 8 Char</div>
                        <div id="rule-upper" class="validation-tag"><i data-lucide="check" class="w-3 h-3 inline mr-1"></i>1 Upper (A-Z)</div>
                        <div id="rule-lower" class="validation-tag"><i data-lucide="check" class="w-3 h-3 inline mr-1"></i>1 Lower (a-z)</div>
                        <div id="rule-number" class="validation-tag"><i data-lucide="check" class="w-3 h-3 inline mr-1"></i>1 Numeric (0-9)</div>
                        <div id="rule-special" class="validation-tag col-span-2"><i data-lucide="check" class="w-3 h-3 inline mr-1"></i>1 Symbol (@$!%*?&)</div>
                    </div>

                    <button type="submit" id="submitBtn" disabled class="w-full py-3 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition opacity-50 cursor-not-allowed flex justify-center items-center mt-4">
                        Create New Password
                    </button>
                </form>
            <?php endif; ?>

        </div>
    </div>

    <script>
        lucide.createIcons();

        function validateComplexity() {
            const pwd = document.getElementById('password').value;
            const confirmPwd = document.getElementById('confirm_password').value;
            const matchFeedback = document.getElementById('match-feedback');
            const btn = document.getElementById('submitBtn');

            const reqs = {
                length: pwd.length >= 8,
                upper: /[A-Z]/.test(pwd),
                lower: /[a-z]/.test(pwd),
                number: /\d/.test(pwd),
                special: /[@$!%*?&]/.test(pwd)
            };

            const toggleRule = (id, isValid) => {
                const el = document.getElementById(id);
                if (isValid) el.classList.add('valid');
                else el.classList.remove('valid');
            };

            toggleRule('rule-length', reqs.length);
            toggleRule('rule-upper', reqs.upper);
            toggleRule('rule-lower', reqs.lower);
            toggleRule('rule-number', reqs.number);
            toggleRule('rule-special', reqs.special);

            const isPwdSecure = Object.values(reqs).every(val => val === true);
            const isMatch = pwd === confirmPwd && pwd !== '';

            if (confirmPwd.length > 0) {
                if (isMatch) matchFeedback.classList.add('hidden');
                else matchFeedback.classList.remove('hidden');
            }

            if (isPwdSecure && isMatch) {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        document.getElementById('setupForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2 inline"></i> Finalizing...';
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            btn.style.pointerEvents = 'none';
            lucide.createIcons();
        });
    </script>
</body>
</html>