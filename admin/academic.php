<?php
session_start();
require_once("../config/db.php");
require_once('../includes/admin_auth.php'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Manage Bookings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { cstyle: { blue: '#004aad', dark: '#1e293b', accent: '#38bdf8' } } } } }
    </script>
    <link rel="stylesheet" href="../assets/css/layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori-tile.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
        
        <?php 
        $topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Bookings / Dashboard</h2>';
        include('../includes/admin_topbar.php'); 
        ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">

            <div class="mb-8 border-b border-slate-200 pb-4">
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Academic Arrangement</h1>
                <p class="text-sm text-slate-500 mt-1">Select a module below to manage semester and academic arrangement.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                <a href="semester_management.php" class="fiori-tile">
                    <div class="fiori-tile-header">
                        <h3 class="fiori-tile-title">Manage semester</h3>
                    </div>
                    <p class="fiori-tile-desc">Configure semester start and end time.</p>
                    <div class="fiori-tile-kpi">
                        <i data-lucide="calendar-plus" class="w-8 h-8 opacity-20 fiori-tile-icon"></i>
                    </div>
                    <div class="fiori-tile-footer">
                        Manage <i data-lucide="arrow-right" class="w-3 h-3 ml-2"></i>
                    </div>
                </a>

                <a href="academic_schedule.php" class="fiori-tile">
                    <div class="fiori-tile-header">
                        <h3 class="fiori-tile-title">Academic Schedule</h3>
                    </div>
                    <p class="fiori-tile-desc">Pre-book the venue for internal staff.</p>
                    <div class="fiori-tile-kpi">
                        <i data-lucide="book-plus" class="w-8 h-8 opacity-20"></i>
                    </div>
                    <div class="fiori-tile-footer">
                        Arrange <i data-lucide="arrow-right" class="w-3 h-3 ml-2"></i>
                    </div>
                </a>

            </div>
            
            
        </div>
    </main>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();
        function toggleSidebar() {
            document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed');
        }
    </script>
</body>
</html>