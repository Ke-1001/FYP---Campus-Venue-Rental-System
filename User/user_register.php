<?php
session_start();
?>
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
                    colors: {
                        mmu: { 
                            core: '#004aad',   
                            glow: '#3b82f6'    
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap');
        
        .glass-panel {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .input-glass {
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .input-glass:focus {
            border-color: #3b82f6; 
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25);
            outline: none;
        }
        .input-error {
            border-color: #ef4444 !important;
            background: #fef2f2 !important;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.15) !important;
        }

        /* 🔴 核心重构：宏观熵值能量条 (Macro Entropy Bar) */
        .entropy-container { width: 100%; height: 6px; background: rgba(255, 255, 255, 0.15); border-radius: 9999px; overflow: hidden; margin: 12px 0; border: 1px solid rgba(255,255,255,0.05); }
        .entropy-bar { height: 100%; width: 0%; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .entropy-weak { background-color: #ef4444; box-shadow: 0 0 10px rgba(239, 68, 68, 0.6); }
        .entropy-fair { background-color: #f59e0b; box-shadow: 0 0 10px rgba(245, 158, 11, 0.6); }
        .entropy-strong { background-color: #10b981; box-shadow: 0 0 10px rgba(16, 185, 129, 0.6); }

        /* 🔴 核心重构：微观规则网格 (Micro Rule Grid) */
        .rule-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px 12px; }
        .rule-item { display: flex; align-items: center; font-size: 11px; font-weight: 600; transition: color 0.3s ease; }
        .rule-invalid { color: #94a3b8; } /* 暗态下的未激活色 */
        .rule-valid { color: #34d399; text-shadow: 0 0 8px rgba(52, 211, 153, 0.3); } /* 激活后的发光翡翠绿 */
        .rule-icon { margin-right: 6px; font-size: 13px; font-weight: bold; width: 14px; text-align: center; }
        
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }
    </style>
</head>
<body class="font-sans antialiased selection:bg-mmu-glow selection:text-white min-h-screen relative overflow-y-auto">

<div class="fixed inset-0 z-0">
    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&q=80" alt="Campus" class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-slate-900/80 mix-blend-multiply"></div>
</div>

<div class="relative z-10 flex flex-col items-center justify-center min-h-screen px-4 py-12">
    
    <div class="absolute top-6 left-6 flex items-center gap-2">
        <div class="w-8 h-8 bg-mmu-core rounded-md flex items-center justify-center text-white font-bold shadow-lg">C</div>
        <span class="font-bold text-white text-xl tracking-tight">CVBMS</span>
    </div>

    <div class="w-full max-w-lg">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-extrabold text-white tracking-tight mb-2">
                Join the <span class="text-mmu-glow drop-shadow-[0_0_12px_rgba(59,130,246,0.6)]">Network.</span>
            </h1>
            <p class="text-slate-300 font-medium text-sm">Secure your campus structural assets today.</p>
        </div>

        <div class="glass-panel rounded-2xl p-8 shadow-2xl">
            
            <?php
            if (isset($_GET['error'])) {
                $err = $_GET['error'] == 'exists' ? "Identity verification failed: Duplicate entry." : "Registration failed. Please try again.";
                echo "<div class='mb-6 px-4 py-3 bg-red-500/20 border border-red-500/50 rounded-lg text-red-200 text-sm font-semibold text-center'>
                        <i data-lucide='alert-octagon' class='w-4 h-4 inline-block mr-1 -mt-1'></i> {$err}
                      </div>";
            }
            ?>

            <form action="../User/user_register_process.php" method="POST" id="regForm" class="space-y-4">
                
                <div class="relative">
                    <div class="flex justify-between items-end mb-1.5">
                        <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider">Student ID</label>
                        <span id="uid-feedback" class="text-[11px] font-bold h-4 transition-colors"></span>
                    </div>
                    <input type="text" name="uid" id="uid" placeholder="e.g. 242DT2430C" required autocomplete="off" 
                           class="input-glass w-full px-4 py-3 rounded-xl text-slate-800 font-semibold placeholder-slate-400 text-sm">
                </div>

                <div class="relative">
                    <div class="flex justify-between items-end mb-1.5">
                        <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider">Email Address</label>
                        <span id="email-feedback" class="text-[11px] font-bold h-4 transition-colors"></span>
                    </div>
                    <input type="email" name="email" id="email" placeholder="student@mmu.edu.my" required autocomplete="off" 
                           class="input-glass w-full px-4 py-3 rounded-xl text-slate-800 font-semibold placeholder-slate-400 text-sm">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1.5">Full Name</label>
                        <input type="text" name="username" placeholder="Legal Name" required 
                               class="input-glass w-full px-4 py-3 rounded-xl text-slate-800 font-semibold placeholder-slate-400 text-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1.5">Phone Number</label>
                        <input type="text" name="phone_num" placeholder="01X-XXXXXXX" required 
                               class="input-glass w-full px-4 py-3 rounded-xl text-slate-800 font-semibold placeholder-slate-400 text-sm">
                    </div>
                </div>

                <!-- 🔴 密码域与双轨反馈矩阵 -->
                <div class="pt-2">
                    <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1.5">Master Password</label>
                    <input type="password" name="password" id="password" placeholder="••••••••" required oninput="evaluateEntropy()"
                           class="input-glass w-full px-4 py-3 rounded-xl text-slate-800 font-semibold placeholder-slate-400 text-sm tracking-widest">
                    
                    <!-- 宏观反馈：动态能量条 -->
                    <div class="entropy-container">
                        <div id="entropy-bar" class="entropy-bar entropy-weak"></div>
                    </div>
                    
                    <!-- 微观反馈：2x3 结构化约束网格 -->
                    <div class="rule-grid bg-black/20 p-3.5 rounded-xl border border-white/5">
                        <div id="rule-length" class="rule-item rule-invalid"><span class="rule-icon">✗</span> 8+ Characters</div>
                        <div id="rule-upper" class="rule-item rule-invalid"><span class="rule-icon">✗</span> 1 Uppercase</div>
                        <div id="rule-lower" class="rule-item rule-invalid"><span class="rule-icon">✗</span> 1 Lowercase</div>
                        <div id="rule-number" class="rule-item rule-invalid"><span class="rule-icon">✗</span> 1 Number</div>
                        <div id="rule-special" class="rule-item rule-invalid"><span class="rule-icon">✗</span> 1 Special Char</div>
                    </div>
                </div>

                <button type="submit" id="submitBtn" disabled 
                        class="w-full mt-6 bg-mmu-core hover:bg-blue-800 text-white font-bold py-3.5 rounded-xl shadow-[0_4px_14px_0_rgba(0,74,173,0.39)] hover:shadow-[0_6px_20px_rgba(0,74,173,0.23)] transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-mmu-core flex justify-center items-center gap-2 group">
                    Create Account <i data-lucide="chevron-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-slate-300 text-[13px]">
                    Already authorized? 
                    <a href="user_login.php" class="text-mmu-glow font-bold hover:text-white transition-colors">Access Dashboard</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
    const stateLocks = { uidValid: false, emailValid: false, pwdValid: false };

    function debounce(func, delay) {
        let timeoutId;
        return function (...args) { clearTimeout(timeoutId); timeoutId = setTimeout(() => func.apply(this, args), delay); };
    }

    const checkUidAvailability = async (uid) => {
        const fb = document.getElementById('uid-feedback');
        const input = document.getElementById('uid');
        if (uid.trim().length < 3) {
            fb.textContent = ''; input.classList.remove('input-error'); stateLocks.uidValid = false; updateMasterLock(); return;
        }
        fb.textContent = 'Verifying...'; fb.className = 'text-[11px] font-bold h-4 text-emerald-400';
        try {
            const res = await fetch(`../api/api_check_uid.php?uid=${encodeURIComponent(uid)}`);
            const data = await res.json();
            if (data.exists) {
                fb.textContent = 'ID Unavailable'; fb.className = 'text-[11px] font-bold h-4 text-red-400';
                input.classList.add('input-error'); stateLocks.uidValid = false;
            } else {
                fb.textContent = 'ID Available'; fb.className = 'text-[11px] font-bold h-4 text-emerald-400';
                input.classList.remove('input-error'); stateLocks.uidValid = true;
            }
        } catch (e) {
            fb.textContent = 'Network Error'; fb.className = 'text-[11px] font-bold h-4 text-red-400'; stateLocks.uidValid = false;
        }
        updateMasterLock();
    };

    const checkEmailAvailability = async (email) => {
        const fb = document.getElementById('email-feedback');
        const input = document.getElementById('email');
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            if (email.trim().length > 0) {
                fb.textContent = 'Invalid Format'; fb.className = 'text-[11px] font-bold h-4 text-red-400'; input.classList.add('input-error');
            } else { fb.textContent = ''; input.classList.remove('input-error'); }
            stateLocks.emailValid = false; updateMasterLock(); return;
        }
        fb.textContent = 'Verifying...'; fb.className = 'text-[11px] font-bold h-4 text-emerald-400';
        try {
            const res = await fetch(`../api/api_check_email.php?email=${encodeURIComponent(email)}`);
            const data = await res.json();
            if (data.exists) {
                fb.textContent = 'Email Registered'; fb.className = 'text-[11px] font-bold h-4 text-red-400';
                input.classList.add('input-error'); stateLocks.emailValid = false;
            } else {
                fb.textContent = 'Email Available'; fb.className = 'text-[11px] font-bold h-4 text-emerald-400';
                input.classList.remove('input-error'); stateLocks.emailValid = true;
            }
        } catch (e) {
            fb.textContent = 'Network Error'; fb.className = 'text-[11px] font-bold h-4 text-red-400'; stateLocks.emailValid = false;
        }
        updateMasterLock();
    };

    document.getElementById('uid').addEventListener('input', debounce(e => checkUidAvailability(e.target.value), 500));
    document.getElementById('email').addEventListener('input', debounce(e => checkEmailAvailability(e.target.value), 500));

    // 🔴 核心逻辑重构：双轨状态映射引擎 (Bi-modal State Mapping Engine)
    function evaluateEntropy() {
        const p = document.getElementById('password').value;
        const v = { length: p.length >= 8, upper: /[A-Z]/.test(p), lower: /[a-z]/.test(p), number: /\d/.test(p), special: /[@$!%*?&]/.test(p) };
        
        let score = 0;
        const setUI = (id, valid) => {
            const el = document.getElementById(id);
            if (valid) {
                score++;
                el.className = 'rule-item rule-valid';
                el.querySelector('.rule-icon').textContent = '✓';
            } else {
                el.className = 'rule-item rule-invalid';
                el.querySelector('.rule-icon').textContent = '✗';
            }
        };

        // 刷新微观约束矩阵
        setUI('rule-length', v.length); setUI('rule-upper', v.upper); setUI('rule-lower', v.lower); setUI('rule-number', v.number); setUI('rule-special', v.special);
        
        // 驱动宏观能量条拓扑变换
        const bar = document.getElementById('entropy-bar');
        const percentage = (score / 5) * 100;
        bar.style.width = percentage + '%';
        
        // 阈值断言 (Threshold Assertion)
        if (score <= 2) {
            bar.className = 'entropy-bar entropy-weak';
        } else if (score <= 4) {
            bar.className = 'entropy-bar entropy-fair';
        } else {
            bar.className = 'entropy-bar entropy-strong';
        }

        stateLocks.pwdValid = (score === 5);
        updateMasterLock();
    }

    function updateMasterLock() {
        document.getElementById('submitBtn').disabled = !(stateLocks.uidValid && stateLocks.emailValid && stateLocks.pwdValid);
    }
</script>
</body>
</html>