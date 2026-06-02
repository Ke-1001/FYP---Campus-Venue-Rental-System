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
    <title>MMU Admin | Register Personnel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { cstyle: { blue: '#004aad', dark: '#1e293b' }, fiori: { bg: '#f4f4f4', text: '#1d2d3e', blue: '#0a6ed1', label: '#6b7280' } } } } }
    </script>
    <link rel="stylesheet" href="../assets/css/layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori_forms.css">
    <style>input[type="date"]::-webkit-calendar-picker-indicator { display: none; -webkit-appearance: none; }</style>
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
            <div class="flex-1 overflow-y-auto p-4 md:p-8 space-y-6">
                <div class="fiori-form-container">
                    <div class="fiori-section-header">
                        <h2 class="text-base font-bold text-fiori-text">Basic Personnel Data</h2>
                    </div>

                    <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">
                        
                        <div class="lg:col-span-6 space-y-4">
                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label">Authorization Level:</label>
                                <div class="col-span-2 relative">
                                    <select name="access_level" required class="fiori-input appearance-none pr-8 bg-white cursor-pointer transition-colors font-bold text-indigo-700">
                                        <option value="" disabled selected>Select Role Assignment</option>
                                        <option value="admin">Standard Administrator</option>
                                        <option value="inspector">Venue Inspector</option>
                                    </select>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 absolute right-3 top-2.5 pointer-events-none"></i>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label">Full Name:</label>
                                <div class="col-span-2">
                                    <input type="text" name="full_name" required placeholder="e.g., Siti Nurhaliza" class="fiori-input">
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-6 space-y-4">
                            <div class="grid grid-cols-3 gap-4 items-start">
                                <label class="col-span-1 text-sm text-fiori-label mt-1">Email Address:</label>
                                <div class="col-span-2 relative">
                                    <input type="email" name="email" id="email" required onkeyup="validateEmail()" placeholder="name@mmu.edu.my" class="fiori-input pr-8">
                                    <i data-lucide="mail" class="w-4 h-4 text-slate-400 absolute right-3 top-2.5"></i>
                                    <p id="email-feedback" class="text-xs text-red-600 mt-1 hidden">Invalid email format</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label">Contact Number:</label>
                                <div class="col-span-2 relative">
                                    <input type="text" name="phone_num" required placeholder="0123456789" class="fiori-input font-mono">
                                </div>
                            </div>
                            
                            <div class="md:col-span-3 pt-4 border-t border-slate-100">
                                <p class="text-xs text-indigo-600 font-bold bg-indigo-50 border border-indigo-100 p-3 rounded-lg flex items-start">
                                    <i data-lucide="info" class="w-4 h-4 mr-2 shrink-0 mt-0.5"></i>
                                    Upon registration, an automated email containing a cryptographic token will be dispatched to the provided email address, allowing the personnel to configure their own credential vector.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="fiori-footer-toolbar shrink-0 z-20 shadow-[0_-2px_10px_rgba(0,0,0,0.05)]">
                <button type="button" onclick="window.location.href='staff_directory.php'" class="fiori-btn-cancel">Cancel</button>
                <button type="submit" id="submitBtn" disabled class="fiori-btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                    Register Entity & Dispatch Email
                </button>
            </div>
        </form>
    </main>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();
        function toggleSidebar() { document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed'); }

        function validateEmail() {
            const emailInput = document.getElementById('email');
            const emailFeedback = document.getElementById('email-feedback');
            const btn = document.getElementById('submitBtn');
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            
            if (emailInput.value.length > 0) {
                if (emailRegex.test(emailInput.value)) {
                    emailInput.style.borderColor = '#89919a';
                    emailFeedback.classList.add('hidden');
                    btn.disabled = false;
                } else {
                    emailInput.style.borderColor = '#ee0000';
                    emailFeedback.classList.remove('hidden');
                    btn.disabled = true;
                }
            } else {
                btn.disabled = true;
            }
        }

        document.getElementById('addStaffForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2 inline"></i> Provisioning...';
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            btn.style.pointerEvents = 'none';
            lucide.createIcons();
        });
    </script>
</body>
</html>