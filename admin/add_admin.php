<?php
// File: admin/add_admin.php
session_start();
require_once '../includes/super_admin_auth.php'; // 🔒 Enforce Super Admin Privilege
require_once '../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Identity Governance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b', accent: '#38bdf8' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
        
        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0">
            <div class="flex items-center">
                <button onclick="toggleSidebar()" class="p-2 mr-4 text-slate-500 hover:text-mmu-blue transition-colors rounded-lg hover:bg-slate-100 focus:outline-none">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Identity Governance / Register Administrator</h2>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="px-3 py-1 bg-indigo-50 border border-indigo-100 rounded-full text-xs font-bold text-indigo-600 flex items-center">
                    <i data-lucide="shield-check" class="w-3 h-3 mr-1"></i> Root Privilege Verified
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth flex justify-center">
            
            <div class="w-full max-w-2xl">
                <div class="mb-8 text-center">
                    <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Register System Administrator</h1>
                    <p class="text-sm text-slate-500 mt-2">Deploy new administrative nodes. Strict validation and enterprise-grade cryptographic complexity are enforced.</p>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <form action="../actions/process_add_admin.php" method="POST" id="addAdminForm" class="p-8 space-y-6">
                        
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Entity Full Name</label>
                                <input type="text" name="full_name" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-mmu-blue outline-none text-sm transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Institutional Email</label>
                                <input type="email" name="email" id="email" required onkeyup="validateFormState()" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-mmu-blue outline-none text-sm font-mono transition-all">
                                <p id="email-feedback" class="text-[10px] font-bold text-red-500 mt-1 hidden tracking-wide uppercase">Invalid Email Syntax Domain</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Access Level (RBAC Profile)</label>
                            <input type="hidden" name="role" value="Normal_Admin">
                            <div class="w-full px-4 py-3 bg-slate-100 border border-slate-200 rounded-lg text-sm font-bold text-slate-500 cursor-not-allowed flex items-center">
                                <i data-lucide="shield" class="w-4 h-4 mr-2 text-mmu-blue"></i> Standard Administrator (Level 1)
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">* Super_Admin nodes cannot be replicated through this interface.</p>
                        </div>

                        <div class="border-t border-slate-100 pt-6 mt-6">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Cryptographic Key (Password)</label>
                            <input type="password" name="password" id="password" required onkeyup="validateFormState()" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-mmu-blue outline-none text-sm font-mono tracking-widest transition-all">
                            
                            <div class="mt-4 p-4 bg-slate-50 border border-slate-200 rounded-lg grid grid-cols-1 md:grid-cols-2 gap-2 text-xs font-bold text-slate-400">
                                <div id="rule-length" class="flex items-center transition-colors"><span class="icon-slot"></span> Minimum 8 Characters</div>
                                <div id="rule-upper" class="flex items-center transition-colors"><span class="icon-slot"></span> 1 Uppercase Letter (A-Z)</div>
                                <div id="rule-lower" class="flex items-center transition-colors"><span class="icon-slot"></span> 1 Lowercase Letter (a-z)</div>
                                <div id="rule-number" class="flex items-center transition-colors"><span class="icon-slot"></span> 1 Numeric Digit (0-9)</div>
                                <div id="rule-special" class="flex items-center transition-colors col-span-1 md:col-span-2"><span class="icon-slot"></span> 1 Special Symbol (@$!%*?&)</div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4 pt-4">
                            <a href="manage_admins.php" class="px-6 py-3 text-sm font-bold text-slate-500 hover:bg-slate-100 rounded-lg transition-colors">Abort Sequence</a>
                            <button type="submit" id="submitBtn" disabled class="px-6 py-3 text-sm font-bold text-white bg-slate-300 rounded-lg transition-all flex items-center cursor-not-allowed">
                                <i data-lucide="user-plus" class="w-4 h-4 mr-2"></i> Deploy Administrator
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();

        function toggleSidebar() {
            document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed');
        }

        // Raw SVG injected directly to bypass Lucide DOM destruction on keyup
        const checkSVG = `<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
        const crossSVG = `<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;

        // Initialize state
        window.onload = () => {
            const rules = ['rule-length', 'rule-upper', 'rule-lower', 'rule-number', 'rule-special'];
            rules.forEach(id => {
                document.getElementById(id).querySelector('.icon-slot').innerHTML = crossSVG;
            });
        };

        function validateFormState() {
            // 1. Email Validation Vector
            const emailInput = document.getElementById('email');
            const emailFeedback = document.getElementById('email-feedback');
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            const isEmailValid = emailRegex.test(emailInput.value);

            if (emailInput.value.length > 0) {
                if (isEmailValid) {
                    emailInput.classList.replace('border-red-400', 'border-slate-200');
                    emailFeedback.classList.add('hidden');
                } else {
                    emailInput.classList.replace('border-slate-200', 'border-red-400');
                    emailFeedback.classList.remove('hidden');
                }
            } else {
                emailInput.classList.replace('border-red-400', 'border-slate-200');
                emailFeedback.classList.add('hidden');
            }

            // 2. Cryptographic Password Vector
            const pwd = document.getElementById('password').value;
            
            const reqs = {
                length: pwd.length >= 8,
                upper: /[A-Z]/.test(pwd),
                lower: /[a-z]/.test(pwd),
                number: /\d/.test(pwd),
                special: /[@$!%*?&]/.test(pwd)
            };

            const toggleRule = (id, isValid) => {
                const el = document.getElementById(id);
                const iconSlot = el.querySelector('.icon-slot');
                
                if (isValid) {
                    el.className = 'flex items-center transition-colors text-emerald-600';
                    iconSlot.innerHTML = checkSVG;
                } else {
                    el.className = 'flex items-center transition-colors text-slate-400';
                    iconSlot.innerHTML = crossSVG;
                }
            };

            toggleRule('rule-length', reqs.length);
            toggleRule('rule-upper', reqs.upper);
            toggleRule('rule-lower', reqs.lower);
            toggleRule('rule-number', reqs.number);
            toggleRule('rule-special', reqs.special);

            // 3. Final Boolean Gate for Submission Execution
            const isPwdSecure = Object.values(reqs).every(val => val === true);
            const btn = document.getElementById('submitBtn');

            if (isPwdSecure && isEmailValid) {
                btn.disabled = false;
                btn.className = "px-6 py-3 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md rounded-lg transition-all flex items-center cursor-pointer";
            } else {
                btn.disabled = true;
                btn.className = "px-6 py-3 text-sm font-bold text-white bg-slate-300 rounded-lg transition-all flex items-center cursor-not-allowed";
            }
        }
    </script>
</body>
</html>