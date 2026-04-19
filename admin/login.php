<?php
// File: admin/login.php
session_start();

// Redirect to dashboard if session state is already authenticated
if (isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['Normal_Admin', 'Super_Admin'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Authentication | MMU Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#0f172a', accent: '#38bdf8' } } } } }
    </script>
</head>
<body class="bg-mmu-dark flex items-center justify-center min-h-screen font-sans selection:bg-mmu-accent selection:text-white relative overflow-hidden">

    <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 opacity-20 pointer-events-none">
        <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-mmu-blue blur-[120px]"></div>
        <div class="absolute bottom-[10%] right-[5%] w-[40%] h-[40%] rounded-full bg-indigo-600 blur-[100px]"></div>
    </div>

    <div class="w-full max-w-md bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl shadow-2xl p-8 z-10">
        
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-mmu-blue/20 text-mmu-accent rounded-2xl flex items-center justify-center mx-auto mb-4 border border-mmu-blue/30">
                <i data-lucide="shield-alert" class="w-8 h-8"></i>
            </div>
            <h2 class="text-2xl font-extrabold text-white tracking-wide">Command Center</h2>
            <p class="text-slate-400 text-sm mt-2 font-mono uppercase tracking-widest">Authorized Personnel Only</p>
        </div>

        <?php
        // Error State Handling
        if (isset($_GET['error'])) {
            $err_code = $_GET['error'];
            $err_msg = "Authentication Fault: Unknown Error.";
            
            if ($err_code == 'invalid') $err_msg = "Invalid credentials. Verification failed.";
            elseif ($err_code == 'access_denied') $err_msg = "Access Denied. Elevated privileges required.";
            elseif ($err_code == 'timeout') $err_msg = "Session Expired. Secure connection terminated.";

            echo "
            <div class='bg-red-500/10 border border-red-500/50 text-red-400 p-4 rounded-lg flex items-start mb-6 text-sm font-medium'>
                <i data-lucide='alert-triangle' class='w-5 h-5 mr-3 shrink-0 mt-0.5'></i>
                <span>{$err_msg}</span>
            </div>";
        }
        ?>

        <form action="../actions/process_login.php" method="POST" class="space-y-5">
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Identification (Email)</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                        <i data-lucide="mail" class="w-5 h-5"></i>
                    </div>
                    <input type="email" name="email" required placeholder="admin@mmu.edu.my" 
                           class="w-full pl-10 pr-4 py-3 bg-slate-900/50 border border-slate-700 text-white rounded-lg focus:ring-2 focus:ring-mmu-accent focus:border-mmu-accent outline-none transition-all placeholder-slate-600">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Security Key (Password)</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                        <i data-lucide="lock" class="w-5 h-5"></i>
                    </div>
                    <input type="password" name="password" required placeholder="••••••••" 
                           class="w-full pl-10 pr-4 py-3 bg-slate-900/50 border border-slate-700 text-white rounded-lg focus:ring-2 focus:ring-mmu-accent focus:border-mmu-accent outline-none transition-all placeholder-slate-600">
                </div>
            </div>

            <button type="submit" class="w-full mt-6 py-3.5 bg-mmu-blue hover:bg-blue-600 text-white font-bold rounded-lg transition-all shadow-lg hover:shadow-mmu-blue/30 flex justify-center items-center">
                Initialize Secure Session <i data-lucide="arrow-right" class="w-4 h-4 ml-2"></i>
            </button>
        </form>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>