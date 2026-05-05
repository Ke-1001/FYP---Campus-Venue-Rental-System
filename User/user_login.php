<?php
// File: user/user_login.php
session_start();

// 💡 检查 uid 是否存在，若已登录则导向首页
if (isset($_SESSION['uid']) && isset($_SESSION['role']) && $_SESSION['role'] === 'user') {
    header("Location: homepage.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - CVBMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        mmu: { 
                            core: '#004aad',   // 品牌深蓝 (Mass Color)
                            glow: '#3b82f6'    // 发光亮蓝 (Emissive Color)
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
        
        /* 核心毛玻璃拟态容器 */
        .glass-panel {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        /* 输入框交互态 */
        .input-glass {
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        /* 聚焦时应用发光亮蓝 */
        .input-glass:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25);
            outline: none;
        }
    </style>
</head>
<body class="font-sans antialiased selection:bg-mmu-glow selection:text-white h-screen overflow-hidden">

<!-- 沉浸式背景层 -->
<div class="absolute inset-0 z-0">
    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&q=80" alt="Campus Background" class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-slate-900/80 mix-blend-multiply"></div>
</div>

<!-- 认证视口 -->
<div class="relative z-10 flex flex-col items-center justify-center h-full px-4">
    
    <!-- 顶部 Logo 标识 (应用深蓝核心色) -->
    <div class="absolute top-8 left-8 flex items-center gap-2">
        <div class="w-8 h-8 bg-mmu-core rounded-md flex items-center justify-center text-white font-bold shadow-lg">C</div>
        <span class="font-bold text-white text-xl tracking-tight">CVBMS</span>
    </div>

    <div class="w-full max-w-md">
        <!-- 标题组 -->
        <div class="text-center mb-10">
            <h1 class="text-4xl md:text-5xl font-extrabold text-white tracking-tight mb-3">
                Welcome <span class="text-mmu-glow drop-shadow-[0_0_12px_rgba(59,130,246,0.6)]">Back.</span>
            </h1>
            <p class="text-slate-300 font-medium text-sm">Access your campus venue dashboard.</p>
        </div>

        <!-- 毛玻璃表单容器 -->
        <div class="glass-panel rounded-2xl p-8 sm:p-10">
            
            <?php
            // 错误处理反馈
            if (isset($_GET['error'])) {
                $errorMsg = "An error occurred.";
                if ($_GET['error'] == 'invalid') $errorMsg = "Invalid ID/Email or password.";
                elseif ($_GET['error'] == 'access_denied') $errorMsg = "Please log in to continue.";
                elseif ($_GET['error'] == 'timeout') $errorMsg = "Session expired. Please log in again.";
                
                echo "<div class='mb-6 px-4 py-3 bg-red-500/20 border border-red-500/50 rounded-xl text-red-200 text-sm font-semibold text-center flex items-center justify-center gap-2'>
                        <i data-lucide='alert-circle' class='w-4 h-4'></i> {$errorMsg}
                      </div>";
            }
            ?>

            <form action="user_login_process.php" method="POST" class="space-y-5">
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Student ID / Email</label>
                    <input type="text" name="login_identifier" required placeholder="e.g. 242DT2430C" 
                           class="input-glass w-full px-4 py-3.5 rounded-xl text-slate-800 font-semibold placeholder-slate-400">
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider">Password</label>
                        <!-- 如果系统有忘记密码功能，这里可以留一个发光蓝的入口 -->
                        <!-- <a href="#" class="text-[11px] font-bold text-mmu-glow hover:text-white transition-colors">Forgot?</a> -->
                    </div>
                    <input type="password" name="password" required placeholder="••••••••" 
                           class="input-glass w-full px-4 py-3.5 rounded-xl text-slate-800 font-semibold placeholder-slate-400 tracking-widest">
                </div>

                <!-- 核心按钮：深蓝 (Mass Color) 配合光学投影 -->
                <button type="submit" class="w-full mt-4 bg-mmu-core hover:bg-blue-800 text-white font-bold py-4 rounded-xl shadow-[0_4px_14px_0_rgba(0,74,173,0.39)] hover:shadow-[0_6px_20px_rgba(0,74,173,0.23)] transition-all flex justify-center items-center gap-2 group">
                    Login to Dashboard <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-slate-300 text-[13px]">
                    Don't have an account? 
                    <a href="user_register.php" class="text-mmu-glow font-bold hover:text-white transition-colors">Sign up</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
</script>

</body>
</html>