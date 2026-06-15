<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - CVBMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/user_css.css?v=2.4">
</head>
<body class="user-light-theme font-sans antialiased min-h-screen relative">

<?php if (isset($_GET['status'])): ?>
    <div id='toast' class='fixed top-5 right-5 z-50 px-6 py-4 rounded-xl text-white font-bold shadow-2xl <?php echo $_GET['status'] == 'success' ? 'bg-emerald-500' : 'bg-red-500'; ?>'>
        <?php echo htmlspecialchars($_GET['msg']); ?>
    </div>
    <script>setTimeout(() => { document.getElementById('toast').style.display = 'none'; }, 3000);</script>
<?php endif; ?>

    <div class="user-page-bg" aria-hidden="true"></div>

    <div class="relative z-10 flex items-center justify-center min-h-screen px-4">
        <div class="w-full max-w-sm glass-panel rounded-2xl p-8 shadow-2xl">
            <h2 class="text-white text-xl font-bold mb-6 text-center">Reset Password</h2>
            <form action="forgot_password_process.php" method="POST" id="recoveryForm" class="space-y-6">
                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <label class="block text-[12px] font-bold text-slate-300 uppercase tracking-wider">Registered Email</label>
                        <span id="email-error" class="validation-error">Invalid Email Format</span>
                    </div>
                    <input type="email" name="email" id="email" placeholder="student@student.mmu.edu.my" required class="w-full px-4 py-3 rounded-xl text-slate-800 font-semibold text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition-all duration-200 transform hover:scale-[1.02] shadow-lg">
                    Send Reset Link
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="../User/user_login.php" class="text-slate-400 hover:text-white text-sm font-bold transition-colors">Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        const emailInput = document.getElementById('email');
        emailInput.addEventListener('input', () => {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const errorEl = document.getElementById('email-error');
            if (emailInput.value.length === 0) {
                errorEl.style.display = 'none';
            } else {
                errorEl.style.display = regex.test(emailInput.value) ? 'none' : 'block';
            }
        });
    </script>
</body>
</html>