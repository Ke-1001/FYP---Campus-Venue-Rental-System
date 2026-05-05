<?php
// File: admin/manage_students.php
session_start();
require_once("../config/db.php");
require_once('../includes/admin_auth.php'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Admin | Student Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori-tile.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">
        
        <?php 
        $topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">User Directory / Manage Students</h2>';
        include('../includes/admin_topbar.php'); 
        ?>

        <div class="flex-1 overflow-y-auto p-8">
            <div class="mb-8">
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Student Management</h1>
                <p class="text-sm text-slate-500 mt-1">Maintain student records and handle campus access credentials.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                
                <a href="add_student.php" class="group bg-white p-6 rounded-2xl shadow-sm border border-slate-200 hover:border-blue-500 hover:shadow-md transition-all">
                    <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mb-4 group-hover:bg-blue-600 transition-colors">
                        <i data-lucide="user-plus" class="w-6 h-6 text-blue-600 group-hover:text-white"></i>
                    </div>
                    <h3 class="font-bold text-slate-800">Add New Student</h3>
                    <p class="text-xs text-slate-500 mt-1">Register a new student entity.</p>
                </a>

                <div class="group bg-white p-6 rounded-2xl shadow-sm border border-slate-200 cursor-not-allowed opacity-60">
                    <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center mb-4">
                        <i data-lucide="database" class="w-6 h-6 text-slate-400"></i>
                    </div>
                    <h3 class="font-bold text-slate-400">Student Directory</h3>
                    <p class="text-xs text-slate-400 mt-1">View and edit existing records.</p>
                </div>

                <div class="group bg-white p-6 rounded-2xl shadow-sm border border-slate-200 cursor-not-allowed opacity-60">
                    <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center mb-4">
                        <i data-lucide="file-up" class="w-6 h-6 text-slate-400"></i>
                    </div>
                    <h3 class="font-bold text-slate-400">Batch Import</h3>
                    <p class="text-xs text-slate-400 mt-1">Upload student list via CSV/Excel.</p>
                </div>

                <div class="group bg-white p-6 rounded-2xl shadow-sm border border-slate-200 cursor-not-allowed opacity-60">
                    <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center mb-4">
                        <i data-lucide="fingerprint" class="w-6 h-6 text-slate-400"></i>
                    </div>
                    <h3 class="font-bold text-slate-400">Verification</h3>
                    <p class="text-xs text-slate-400 mt-1">Validate student institutional IDs.</p>
                </div>

            </div>
        </div>
    </main>
    <script>
        lucide.createIcons();
        function toggleSidebar() {
            document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed');
        }
    </script>
</body>
</html>