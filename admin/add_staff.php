<?php
// File: admin/add_staff.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Register Personnel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { cstyle: { blue: '#004aad', dark: '#1e293b' }, fiori: { bg: '#f4f4f4', text: '#1d2d3e', blue: '#0a6ed1', label: '#6b7280' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori_forms.css">
    <style>
        /* 隱藏原生日期選擇器圖示，改用 Lucide 圖示 */
        input[type="date"]::-webkit-calendar-picker-indicator {
            display: none;
            -webkit-appearance: none;
        }
    </style>
</head>
<body class="bg-[#f4f4f4] text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0 bg-white">
            <?php 
            $topbar_content = '
            <div class="flex items-center">
                <a href="staff_directory.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Identity Management / Register Personnel</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>
        </header>

        <form action="../actions/process_add_staff.php" method="POST" id="addStaffForm" class="flex-1 flex flex-col overflow-hidden">
            <input type="hidden" name="action" value="create">

            <div class="flex-1 overflow-y-auto p-4 md:p-8 space-y-6">
                
                <div class="fiori-form-container">
                    <div class="fiori-section-header">
                        <h2 class="text-base font-bold text-fiori-text">Basic Personnel Data</h2>
                    </div>

                    <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">
                        
                        <div class="lg:col-span-6 space-y-4">
                            <h3 class="text-sm font-bold text-fiori-text mb-4">Identity Data</h3>
                            
                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label">Authorization Level:</label>
                                <div class="col-span-2 relative">
                                    <select name="access_level" required class="fiori-input appearance-none pr-8 bg-white cursor-pointer transition-colors font-bold text-indigo-700">
                                        <option value="" disabled selected>Select Role Assignment</option>
                                        <optgroup label="System Core">
                                            <option value="super_admin">Super Administrator</option>
                                            <option value="admin">Standard Administrator</option>
                                        </optgroup>
                                        <optgroup label="Operations">
                                            <option value="inspector">Venue Inspector</option>
                                        </optgroup>
                                    </select>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 absolute right-3 top-2.5 pointer-events-none"></i>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label">Full Name:</label>
                                <div class="col-span-2">
                                    <input type="text" name="full_name" id="full_name" required placeholder="e.g., Siti Nurhaliza" class="fiori-input">
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label">Date Created:</label>
                                <div class="col-span-2 relative">
                                    <input type="date" value="<?php echo date('Y-m-d'); ?>" class="fiori-input fiori-readonly pr-8" readonly>
                                    <i data-lucide="calendar" class="w-4 h-4 text-slate-400 absolute right-3 top-2.5"></i>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-6 space-y-4">
                            <h3 class="text-sm font-bold text-fiori-text mb-4">System Credentials Data</h3>
                            
                            <div class="grid grid-cols-3 gap-4 items-start">
                                <label class="col-span-1 text-sm text-fiori-label mt-1">Email Address:</label>
                                <div class="col-span-2 relative">
                                    <input type="email" name="email" id="email" required onkeyup="validateERPForm()" placeholder="name@mmu.edu.my" class="fiori-input pr-8">
                                    <i data-lucide="mail" class="w-4 h-4 text-slate-400 absolute right-3 top-2.5"></i>
                                    <p id="email-feedback" class="text-xs text-red-600 mt-1 hidden">Invalid email format</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label">Contact Number:</label>
                                <div class="col-span-2 relative">
                                    <input type="text" name="phone_num" id="phone_num" required placeholder="0123456789" class="fiori-input font-mono">
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 items-start border-t border-slate-100 pt-4 mt-2">
                                <label class="col-span-1 text-sm text-fiori-label mt-1">Initial Password:</label>
                                <div class="col-span-2">
                                    <input type="password" name="password" id="password" required onkeyup="validateERPForm()" placeholder="Assign secure password" class="fiori-input bg-[#fffdf0] font-mono">
                                    <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1">
                                        <span id="rule-length" class="validation-tag">Len $\ge$ 8</span>
                                        <span id="rule-upper" class="validation-tag">Upper [A-Z]</span>
                                        <span id="rule-lower" class="validation-tag">Lower [a-z]</span>
                                        <span id="rule-number" class="validation-tag">Num [0-9]</span>
                                        <span id="rule-special" class="validation-tag">Spec {@$!%*?&}</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="fiori-footer-toolbar">
                <button type="button" onclick="window.location.href='staff_directory.php'" class="fiori-btn-cancel">
                    Cancel
                </button>
                <button type="submit" id="submitBtn" disabled class="fiori-btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                    Save Document
                </button>
            </div>

        </form>
    </main>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();
        function toggleSidebar() { document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed'); }

        function validateERPForm() {
            const emailInput = document.getElementById('email');
            const emailFeedback = document.getElementById('email-feedback');
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            const isEmailValid = emailRegex.test(emailInput.value);

            if (emailInput.value.length > 0) {
                if (isEmailValid) {
                    emailInput.style.borderColor = '#89919a';
                    emailFeedback.classList.add('hidden');
                } else {
                    emailInput.style.borderColor = '#ee0000';
                    emailFeedback.classList.remove('hidden');
                }
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
                if (el) {
                    if (isValid) el.classList.add('valid');
                    else el.classList.remove('valid');
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
            } else {
                btn.disabled = true;
            }
        }

        // 💡 防止重複提交防護
        document.getElementById('addStaffForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2 inline"></i> Processing...';
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            btn.style.pointerEvents = 'none';
            lucide.createIcons();
        });
    </script>
</body>
</html>