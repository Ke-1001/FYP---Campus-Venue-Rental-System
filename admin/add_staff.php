<?php
// File: admin/add_staff.php
session_start();
require_once '../includes/admin_auth.php'; 
require_once '../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Register Staff</title>
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
            <?php 
            $topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Personnel Management / Register Staff</h2>';
            include('../includes/admin_topbar.php'); 
            ?>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth flex justify-center">
            
            <div class="w-full max-w-2xl">
                <div class="mb-8 text-center">
                    <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Register Operational Staff</h1>
                    <p class="text-sm text-slate-500 mt-2">Create a new staff account for venue inspections or maintenance tasks.</p>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <form action="../actions/process_add_staff.php" method="POST" id="addStaffForm" class="p-8 space-y-6">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Staff Full Name</label>
                                <input type="text" name="staff_name" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Email Address</label>
                                <input type="email" name="email" id="email" required onkeyup="validateFormState()" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm font-mono transition-all">
                                <p id="email-feedback" class="text-[10px] font-bold text-red-500 mt-1 hidden tracking-wide uppercase">Invalid Email Format</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Contact Number</label>
                                <input type="text" name="phone_num" required placeholder="e.g. 0123456789" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm font-mono transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Position / Role</label>
                                <select name="position" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm font-bold text-slate-700 transition-all">
                                    <option value="inspector" selected>Venue Inspector</option>
                                    <option value="manager">Operations Manager</option>
                                </select>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 pt-6 mt-6">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Account Password</label>
                            <input type="password" name="password" id="password" required onkeyup="validateFormState()" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm font-mono tracking-widest transition-all">
                            
                            <div class="mt-4 p-4 bg-slate-50 border border-slate-200 rounded-lg grid grid-cols-1 md:grid-cols-2 gap-2 text-xs font-bold text-slate-400">
                                <div id="rule-length" class="flex items-center transition-colors"><span class="icon-slot"></span> Minimum 8 Characters</div>
                                <div id="rule-upper" class="flex items-center transition-colors"><span class="icon-slot"></span> 1 Uppercase (A-Z)</div>
                                <div id="rule-lower" class="flex items-center transition-colors"><span class="icon-slot"></span> 1 Lowercase (a-z)</div>
                                <div id="rule-number" class="flex items-center transition-colors"><span class="icon-slot"></span> 1 Number (0-9)</div>
                                <div id="rule-special" class="flex items-center transition-colors col-span-1 md:col-span-2"><span class="icon-slot"></span> 1 Special Character (@$!%*?&)</div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4 pt-4">
                            <a href="manage_admins.php" class="px-6 py-3 text-sm font-bold text-slate-500 hover:bg-slate-100 rounded-lg transition-colors">Cancel</a>
                            <button type="submit" id="submitBtn" disabled class="px-6 py-3 text-sm font-bold text-white bg-slate-300 rounded-lg transition-all flex items-center cursor-not-allowed">
                                <i data-lucide="save" class="w-4 h-4 mr-2"></i> Save Staff Record
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

        const checkSVG = `<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
        const crossSVG = `<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;

        window.onload = () => {
            const rules = ['rule-length', 'rule-upper', 'rule-lower', 'rule-number', 'rule-special'];
            rules.forEach(id => {
                document.getElementById(id).querySelector('.icon-slot').innerHTML = crossSVG;
            });
        };

        function validateFormState() {
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

            const isPwdSecure = Object.values(reqs).every(val => val === true);
            const btn = document.getElementById('submitBtn');

            if (isPwdSecure && isEmailValid) {
                btn.disabled = false;
                btn.className = "px-6 py-3 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md rounded-lg transition-all flex items-center cursor-pointer";
            } else {
                btn.disabled = true;
                btn.className = "px-6 py-3 text-sm font-bold text-white bg-slate-300 rounded-lg transition-all flex items-center cursor-not-allowed";
            }
        }
    </script>
</body>
</html>