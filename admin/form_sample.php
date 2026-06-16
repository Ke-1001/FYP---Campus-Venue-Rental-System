<?php

// This section prepares the admin form sample page.
session_start();
require_once '../includes/super_admin_auth.php';
require_once '../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Add Administrator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config =
{ theme:
{ extend:
{ colors:
{ cstyle:
{ blue: '#004aad', dark: '#1e293b', accent: '#38bdf8' } } } } }
    </script>
    <link rel="stylesheet" href="../assets/css/admin_css.css?v=2.0">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-fiori-bg relative">

        <?php
        $topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Personnel Management / Register Admin</h2>';
        include('../includes/admin_topbar.php');
        ?>

        <form action="../actions/process_add_admin.php" method="POST" id="addAdminForm" class="flex-1 flex flex-col overflow-hidden">

            <div class="flex-1 overflow-y-auto">
                <div class="fiori-form-container">

                    <div class="fiori-section-header">
                        <h2 class="text-base font-bold text-fiori-text">Basic Data</h2>
                        <a href="#" class="text-fiori-blue hover:underline text-sm font-semibold">Change Role Assignment Data</a>
                    </div>

                    <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">

                        <div class="lg:col-span-6 space-y-4">
                            <h3 class="text-sm font-bold text-fiori-text mb-4">Identity Data</h3>

                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label">Access Level:</label>
                                <div class="col-span-2 relative">
                                    <input type="text" value="Standard Administrator (01)" class="fiori-input fiori-readonly" readonly>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label">Full Name:</label>
                                <div class="col-span-2">
                                    <input type="text" name="admin_name" id="admin_name" required class="fiori-input">
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label">Date Created:</label>
                                <div class="col-span-2 relative">
                                    <input type="date" name="date_created" id="date_created" value="<?php echo date('Y-m-d'); ?>" class="fiori-input pr-8 cursor-pointer hover:border-[#0a6ed1] transition-colors" required>
                                    <i data-lucide="calendar" onclick="document.getElementById('date_created').showPicker()" class="w-4 h-4 text-fiori-label absolute right-2 top-2 cursor-pointer hover:text-[#0a6ed1] transition-colors"></i>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label">System Module:</label>
                                <div class="col-span-2 relative">
                                    <select class="fiori-input appearance-none pr-8 bg-white cursor-pointer hover:border-[#0a6ed1] transition-colors">
                                        <option>Core Management (01)</option>
                                        <option>Financials (02)</option>
                                    </select>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-fiori-label absolute right-2 top-2 pointer-events-none"></i>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-6 space-y-4">
                            <h3 class="text-sm font-bold text-fiori-text mb-4">System Credentials Data</h3>

                            <div class="grid grid-cols-3 gap-4 items-start">
                                <label class="col-span-1 text-sm text-fiori-label mt-1">Email Address:</label>
                                <div class="col-span-2 relative">
                                    <input type="email" name="email" id="email" required onkeyup="validateERPForm()" class="fiori-input pr-8">
                                    <i data-lucide="mail" class="w-4 h-4 text-fiori-label absolute right-2 top-2"></i>
                                    <p id="email-feedback" class="text-xs text-red-600 mt-1 hidden">Invalid email format</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label">Contact Number:</label>
                                <div class="col-span-2 relative">
                                    <input type="text" name="phone_num" id="phone_num" class="fiori-input">
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 items-start border-t border-gray-100 pt-4 mt-2">
                                <label class="col-span-1 text-sm text-fiori-label mt-1">Initial Password:</label>
                                <div class="col-span-2">
                                    <input type="password" name="password" id="password" required onkeyup="validateERPForm()" class="fiori-input bg-[#fffdf0]">
                                    <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1">
                                        <span id="rule-length" class="validation-tag">Len $\ge$ 8</span>
                                        <span id="rule-upper" class="validation-tag">Upper [A-Z]</span>
                                        <span id="rule-lower" class="validation-tag">Lower [a-z]</span>
                                        <span id="rule-number" class="validation-tag">Num [0-9]</span>
                                        <span id="rule-special" class="validation-tag">Spec
{@$!%*?&}</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="fiori-footer-toolbar">
                <button type="button" onclick="window.location.href='manage_admins.php'" class="fiori-btn-cancel">
                    Cancel
                </button>
                <button type="submit" id="submitBtn" disabled class="fiori-btn-primary">
                    Save Document
                </button>
            </div>

        </form>
    </main>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();

        function toggleSidebar()
        {
            document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed');
        }

        function validateERPForm()
        {
            const emailInput = document.getElementById('email');
            const emailFeedback = document.getElementById('email-feedback');
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]
{2,}$/;
            const isEmailValid = emailRegex.test(emailInput.value);

            if (emailInput.value.length > 0)
            {
                if (isEmailValid)
                {
                    emailInput.style.borderColor = 'var(--fiori-border)';
                    emailFeedback.classList.add('hidden');
                }
                else
                {
                    emailInput.style.borderColor = 'var(--fiori-error)';
                    emailFeedback.classList.remove('hidden');
                }
            }
            else
            {
                emailInput.style.borderColor = 'var(--fiori-border)';
                emailFeedback.classList.add('hidden');
            }

            const pwd = document.getElementById('password').value;

            const reqs =
            {
                length: pwd.length >= 8,
                upper: /[A-Z]/.test(pwd),
                lower: /[a-z]/.test(pwd),
                number: /\d/.test(pwd),
                special: /[@$!%*?&]/.test(pwd)
            };

            const toggleRule = (id, isValid) =>
            {
                const el = document.getElementById(id);
                if (isValid)
                {
                    el.classList.add('valid');
                }
                else
                {
                    el.classList.remove('valid');
                }
            };

            toggleRule('rule-length', reqs.length);
            toggleRule('rule-upper', reqs.upper);
            toggleRule('rule-lower', reqs.lower);
            toggleRule('rule-number', reqs.number);
            toggleRule('rule-special', reqs.special);

            const isPwdSecure = Object.values(reqs).every(val => val === true);
            const btn = document.getElementById('submitBtn');

            if (isPwdSecure && isEmailValid)
            {
                btn.disabled = false;
                btn.style.opacity = "1";
            }
            else
            {
                btn.disabled = true;
                btn.style.opacity = "0.5";
            }
        }
    </script>
</body>
</html>
