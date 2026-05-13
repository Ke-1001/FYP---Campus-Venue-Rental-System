<?php
// File: admin/dashboard.php

session_start();
require_once("../config/db.php");
require_once('../includes/admin_auth.php'); 

//bookings
$kpi_pending = $conn->query("SELECT COUNT(*) FROM booking WHERE status = 'pending' AND payment_status = 'paid'")->fetch_row()[0] ?? 0;
$kpi_ongoing = $conn->query("SELECT COUNT(*) FROM booking WHERE status = 'approved'")->fetch_row()[0] ?? 0;
$kpi_returned = $conn->query("SELECT COUNT(*) FROM booking WHERE status = 'completed'")->fetch_row()[0] ?? 0;
$sql_kpi_assign = "SELECT COUNT(*) FROM booking b LEFT JOIN inspection i ON b.bid = i.bid WHERE b.status IN ('approved', 'completed') AND b.payment_status = 'paid' AND i.ins_id IS NULL";
$kpi_assign = $conn->query($sql_kpi_assign)->fetch_row()[0] ?? 0;

//inspections
$sql_kpi_pending = "SELECT COUNT(*) FROM inspection i JOIN booking b ON i.bid = b.bid WHERE i.ins_status = 'pending' AND b.status = 'completed'";
$kpi_pending = $conn->query($sql_kpi_pending)->fetch_row()[0] ?? 0;
$sql_kpi_tracked = "SELECT COUNT(*) FROM inspection WHERE ins_status IN ('passed', 'failed')";
$kpi_tracked = $conn->query($sql_kpi_tracked)->fetch_row()[0] ?? 0;

//venues
$kpi_total = $conn->query("SELECT COUNT(*) FROM venue")->fetch_row()[0] ?? 0;
$kpi_available = $conn->query("SELECT COUNT(*) FROM venue WHERE status = 'available'")->fetch_row()[0] ?? 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { cstyle: { blue: '#004aad', dark: '#1e293b', accent: '#38bdf8' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.1">
    <link rel="stylesheet" href="../assets/css/fiori-tile.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
        
        <?php 
        $topbar_content = '
        <div class="flex items-center text-slate-500 bg-white px-4 py-2 rounded-lg border border-slate-200 focus-within:border-mmu-blue shadow-sm transition-all">
            <i data-lucide="search" class="w-4 h-4 mr-2"></i>
            <input type="text" placeholder="Search system assets..." class="bg-transparent border-none outline-none w-64 text-sm focus:ring-0">
        </div>';
        include('../includes/admin_topbar.php'); 
        ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">

            <div class="mt-12 mb-8 border-b border-slate-200 pb-4">
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Semester Management</h1>
                <p class="text-sm text-slate-500 mt-1">Configure configure semester start time and end time.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                <a href="semester_management.php" class="fiori-tile">
                    <div class="fiori-tile-header">
                        <h3 class="fiori-tile-title">Manage semester</h3>
                        <i data-lucide="plus-square" class="w-5 h-5 fiori-tile-icon"></i>
                    </div>
                    <p class="fiori-tile-desc">Configure semester start and end time.</p>
                    <div class="fiori-tile-kpi">
                        <i data-lucide="door-open" class="w-8 h-8 opacity-20"></i>
                    </div>
                    <div class="fiori-tile-footer">
                        Manage <i data-lucide="arrow-right" class="w-3 h-3 ml-2"></i>
                    </div>
                </a>

                <a href="academic_schedule.php" class="fiori-tile">
                    <div class="fiori-tile-header">
                        <h3 class="fiori-tile-title">Academic Schedule</h3>
                        <i data-lucide="database" class="w-5 h-5 fiori-tile-icon"></i>
                    </div>
                    <p class="fiori-tile-desc">Pre-book the venue for internal staff.</p>
                    <div class="fiori-tile-kpi">
                        <?php echo $kpi_total; ?>
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
            const sidebar = document.getElementById('system-sidebar');
            sidebar.classList.toggle('sidebar-collapsed');
        }
    </script>
</body>
</html>