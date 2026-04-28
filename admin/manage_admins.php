<?php
// File: admin/manage_admins.php
session_start();
require_once("../config/db.php");
require_once('../includes/admin_auth.php'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Admin | Personnel Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
</head>
<body class="bg-slate-50 text-slate-800 font-sans h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">
        
        <?php 
        // 💡 將 Topbar 標題升級為更具包容性的 Personnel Management
        $topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">System Administration / Personnel Management</h2>';
        include('../includes/admin_topbar.php'); 
        ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="mb-6">
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Admin Management</h1>
                <p class="text-sm text-slate-500 mt-1">Control administrative access levels and core system personnel.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                
                <a href="add_admin.php" class="group bg-white p-6 rounded-2xl shadow-sm border border-slate-200 hover:border-indigo-500 hover:shadow-md transition-all">
                    <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center mb-4 group-hover:bg-indigo-600 transition-colors">
                        <i data-lucide="shield-plus" class="w-6 h-6 text-indigo-600 group-hover:text-white"></i>
                    </div>
                    <h3 class="font-bold text-slate-800">Add New Admin</h3>
                    <p class="text-xs text-slate-500 mt-1">Create a new administrator account.</p>
                </a>

                <div class="group bg-white p-6 rounded-2xl shadow-sm border border-slate-200 cursor-not-allowed opacity-60">
                    <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center mb-4">
                        <i data-lucide="shield" class="w-6 h-6 text-slate-400"></i>
                    </div>
                    <h3 class="font-bold text-slate-400">Admin Directory</h3>
                    <p class="text-xs text-slate-400 mt-1">Full list and profile management.</p>
                </div>

                <div class="group bg-white p-6 rounded-2xl shadow-sm border border-slate-200 cursor-not-allowed opacity-60">
                    <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center mb-4">
                        <i data-lucide="key" class="w-6 h-6 text-slate-400"></i>
                    </div>
                    <h3 class="font-bold text-slate-400">System Roles</h3>
                    <p class="text-xs text-slate-400 mt-1">Define permissions and access levels.</p>
                </div>

                <div class="group bg-white p-6 rounded-2xl shadow-sm border border-slate-200 cursor-not-allowed opacity-60">
                    <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center mb-4">
                        <i data-lucide="history" class="w-6 h-6 text-slate-400"></i>
                    </div>
                    <h3 class="font-bold text-slate-400">Activity Logs</h3>
                    <p class="text-xs text-slate-400 mt-1">Monitor administrative operations.</p>
                </div>

            </div>

            <div class="mb-6 border-t border-slate-200 pt-10">
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Staff Management</h2>
                <p class="text-sm text-slate-500 mt-1">Manage operational personnel, including venue inspectors and maintenance crew.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                
                <a href="add_staff.php" class="group bg-white p-6 rounded-2xl shadow-sm border border-slate-200 hover:border-emerald-500 hover:shadow-md transition-all">
                    <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center mb-4 group-hover:bg-emerald-600 transition-colors">
                        <i data-lucide="user-plus" class="w-6 h-6 text-emerald-600 group-hover:text-white"></i>
                    </div>
                    <h3 class="font-bold text-slate-800">Add New Staff</h3>
                    <p class="text-xs text-slate-500 mt-1">Register an inspector or staff member.</p>
                </a>

                <div class="group bg-white p-6 rounded-2xl shadow-sm border border-slate-200 cursor-not-allowed opacity-60">
                    <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center mb-4">
                        <i data-lucide="users" class="w-6 h-6 text-slate-400"></i>
                    </div>
                    <h3 class="font-bold text-slate-400">Staff Directory</h3>
                    <p class="text-xs text-slate-400 mt-1">View and manage operational staff.</p>
                </div>

            </div>

            <div class="p-4 bg-slate-100 border border-slate-200 rounded-xl flex items-start mt-8">
                <i data-lucide="info" class="w-5 h-5 text-slate-500 mr-3 mt-0.5"></i>
                <p class="text-xs font-medium text-slate-600 leading-relaxed">
                    <strong>Note:</strong> The Directory and Roles modules for both Administrators and Staff are currently being integrated into the core engine. You can currently add new entities and they will be immediately available in the system.
                </p>
            </div>

        </div>
    </main>

    <script>lucide.createIcons();</script>
</body>
</html>