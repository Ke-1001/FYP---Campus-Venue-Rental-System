<?php
// File: forgot_password.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU System | Recovery Protocol</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { fiori: { text: '#1d2d3e', label: '#6b7280', blue: '#0a6ed1' } } } } }
    </script>
    <link rel="stylesheet" href="../assets/css/admin_css.css?v=2.0">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex items-center justify-center relative overflow-hidden">

    <?php if (isset($_SESSION['toast'])): ?>
        <div class="absolute top-6 right-6 bg-slate-800 text-white px-6 py-3 rounded-lg shadow-xl text-sm font-bold flex items-center z-50 animate-bounce">
            <?php echo htmlspecialchars($_SESSION['toast']['msg']); ?>
            <?php unset($_SESSION['toast']); ?>
        </div>
    <?php endif; ?>

    <div class="max-w-md w-full mx-4 relative z-10">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-black text-indigo-700 tracking-tight">CVBMS Management</h1>
            <p class="text-sm font-bold text-slate-500 uppercase tracking-widest mt-2">Reset Password</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
            
            <div class="px-8 py-6 border-b border-slate-100 bg-slate-50">
                <h2 class="text-lg font-bold text-slate-800">Identify Your Entity</h2>
                <p class="text-xs text-slate-500 font-medium mt-1 leading-relaxed">
                    Provide the email address associated with your account. A cryptographic token will be dispatched to authorize credential reconfiguration.
                </p>
            </div>

            <form action="../actions/process_forgot_password.php" method="POST" id="recoveryForm" class="p-8 space-y-6">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Registered Email Vector</label>
                    <div class="relative">
                        <input type="email" name="email" id="email" required onkeyup="validateEmail()" placeholder="entity@mmu.edu.my" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:border-indigo-500 outline-none text-sm font-mono transition-all pr-10">
                        <i data-lucide="mail" class="w-4 h-4 text-slate-400 absolute right-3 top-3.5 pointer-events-none"></i>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" id="submitBtn" disabled class="w-full py-3 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition opacity-50 cursor-not-allowed flex justify-center items-center">
                        Initiate Recovery Protocol
                    </button>
                </div>
            </form>
            
            <div class="px-8 py-4 bg-slate-50 border-t border-slate-100 text-center">
                <a href="login.php" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">
                    <i data-lucide="arrow-left" class="w-3 h-3 inline mr-1"></i> Return to Authentication
                </a>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function validateEmail() {
            const emailInput = document.getElementById('email').value;
            const btn = document.getElementById('submitBtn');
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            
            if (emailRegex.test(emailInput)) {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        document.getElementById('recoveryForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2 inline"></i> Verifying...';
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            btn.style.pointerEvents = 'none';
            lucide.createIcons();
        });
    </script>
</body>
</html>