<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CVBMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { mmu: { core: '#004aad', glow: '#3b82f6' } } } }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
        .glass-panel { background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.15); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .input-glass { background: rgba(255, 255, 255, 0.95); border: 2px solid transparent; transition: all 0.3s; }
        .input-glass:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25); outline: none; }
    </style>
</head>
<body class="font-sans antialiased min-h-screen relative overflow-y-auto">

<?php if (isset($_GET['status'])): ?>
    <div id='toast' class='fixed top-5 right-5 z-50 px-6 py-4 rounded-xl text-white font-bold shadow-2xl <?php echo $_GET['status'] == 'success' ? 'bg-emerald-500' : 'bg-red-500'; ?>'>
        <?php echo htmlspecialchars($_GET['msg']); ?>
    </div>
    <script>
        setTimeout(() => { document.getElementById('toast').style.display = 'none'; }, 3000);
    </script>
<?php endif; ?>

<div class="fixed inset-0 z-0">
    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&q=80" alt="Campus" class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-slate-900/80 mix-blend-multiply"></div>
</div>

<div class="relative z-10 flex flex-col items-center justify-center min-h-screen px-4 py-12">
    <div class="absolute top-6 left-6 flex items-center gap-2">
        <div class="w-8 h-8 bg-mmu-core rounded-md flex items-center justify-center text-white font-bold shadow-lg">C</div>
        <span class="font-bold text-white text-xl tracking-tight">CVBMS</span>
    </div>

    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-extrabold text-white tracking-tight mb-2">Welcome <span class="text-mmu-glow">Back.</span></h1>
            <p class="text-slate-300 font-medium text-sm">Access your campus venue dashboard.</p>
        </div>

        <div class="glass-panel rounded-2xl p-8 shadow-2xl">
            <form action="../User/user_login_process.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-[12px] font-bold text-slate-300 uppercase tracking-wider mb-1.5">Student ID / Email</label>
                    <input type="text" name="uid" placeholder="e.g. 242DT2430C" required class="input-glass w-full px-4 py-3 rounded-xl text-slate-800 font-semibold text-sm">
                </div>
                <div>
                    <label class="block text-[12px] font-bold text-slate-300 uppercase tracking-wider mb-1.5">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" placeholder="••••••••" required class="input-glass w-full px-4 py-3 rounded-xl text-slate-800 font-semibold text-sm pr-12">
                        <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 px-4 flex items-center text-slate-400">
                            <i id="eyeIcon" data-lucide="eye" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="w-full mt-6 bg-mmu-core hover:bg-blue-800 text-white font-bold py-3.5 rounded-xl transition-all flex items-center justify-center gap-2">
                    Login to Dashboard →
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-slate-300 text-sm">
                    Don't have an account? 
                    <a href="user_register.php" class="text-mmu-glow font-bold hover:text-white transition-colors">Sign up</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
    function togglePasswordVisibility() {
        const p = document.getElementById('password');
        const eye = document.getElementById('eyeIcon');
        if (p.type === 'password') { p.type = 'text'; eye.setAttribute('data-lucide', 'eye-off'); }
        else { p.type = 'password'; eye.setAttribute('data-lucide', 'eye'); }
        lucide.createIcons();
    }
</script>
</body>
</html>