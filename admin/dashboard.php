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
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b', accent: '#38bdf8' } } } } }
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

            <div class="mb-8 border-b border-slate-200 pb-4">
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Manage Bookings</h1>
                <p class="text-sm text-slate-500 mt-1">Select a module below to manage venue bookings, assign inspectors, and track records.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                <a href="pending_requests.php" class="fiori-tile">
                    <div class="fiori-tile-header">
                        <h3 class="fiori-tile-title">Approval Queue</h3>
                        <i data-lucide="shield-alert" class="w-5 h-5 fiori-tile-icon"></i>
                    </div>
                    <p class="fiori-tile-desc">Review and approve new venue booking requests.</p>
                    <div class="fiori-tile-kpi">
                        <?php echo $kpi_pending; ?>
                    </div>
                    <div class="fiori-tile-footer">
                        View Requests <i data-lucide="arrow-right" class="w-3 h-3 ml-2"></i>
                    </div>
                </a>

                <a href="assign_inspector.php" class="fiori-tile">
                    <div class="fiori-tile-header">
                        <h3 class="fiori-tile-title">Assign Inspector</h3>
                        <i data-lucide="users" class="w-5 h-5 fiori-tile-icon"></i>
                    </div>
                    <p class="fiori-tile-desc">Assign staff to inspect venues after they are used.</p>
                    <div class="fiori-tile-kpi">
                        <?php echo $kpi_assign; ?>
                    </div>
                    <div class="fiori-tile-footer">
                        Assign Staff <i data-lucide="arrow-right" class="w-3 h-3 ml-2"></i>
                    </div>
                </a>

                <a href="track_bookings.php" class="fiori-tile">
                    <div class="fiori-tile-header">
                        <h3 class="fiori-tile-title">Track Bookings</h3>
                        <i data-lucide="activity" class="w-5 h-5 fiori-tile-icon"></i>
                    </div>
                    <p class="fiori-tile-desc">Monitor ongoing bookings and view past records.</p>
                    <div class="fiori-tile-kpi">
                        <?php echo ($kpi_ongoing + $kpi_returned); ?>
                    </div>
                    <div class="fiori-tile-footer">
                        View Bookings <i data-lucide="arrow-right" class="w-3 h-3 ml-2"></i>
                    </div>
                </a>

            </div>

            <div class="mt-12 mb-8 border-b border-slate-200 pb-4">
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Venue Inspections</h1>
                <p class="text-sm text-slate-500 mt-1">Select a discrete module to execute post-usage assessments and track inspection history.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-10">

                <a href="pending_inspections.php" class="fiori-tile">
                    <div class="fiori-tile-header">
                        <h3 class="fiori-tile-title">Pending Inspections</h3>
                        <i data-lucide="clipboard-list" class="w-5 h-5 fiori-tile-icon"></i>
                    </div>
                    <p class="fiori-tile-desc">Execute physical assessments for recently utilized venues.</p>
                    <div class="fiori-tile-kpi">
                        <?php echo $kpi_pending; ?>
                    </div>
                    <div class="fiori-tile-footer">
                        Process Queue <i data-lucide="arrow-right" class="w-3 h-3 ml-2"></i>
                    </div>
                </a>

                <a href="track_inspections.php" class="fiori-tile">
                    <div class="fiori-tile-header">
                        <h3 class="fiori-tile-title">Track Inspections</h3>
                        <i data-lucide="history" class="w-5 h-5 fiori-tile-icon"></i>
                    </div>
                    <p class="fiori-tile-desc">Review historical assessment logs and penalty records.</p>
                    <div class="fiori-tile-kpi">
                        <?php echo $kpi_tracked; ?>
                    </div>
                    <div class="fiori-tile-footer">
                        View History <i data-lucide="arrow-right" class="w-3 h-3 ml-2"></i>
                    </div>
                </a>

            </div>

            <div class="mt-12 mb-8 border-b border-slate-200 pb-4">
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Venue Registry</h1>
                <p class="text-sm text-slate-500 mt-1">Configure physical assets, capacity constraints, and financial deposit requirements.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                <a href="register_venue.php" class="fiori-tile">
                    <div class="fiori-tile-header">
                        <h3 class="fiori-tile-title">Register Venue</h3>
                        <i data-lucide="plus-square" class="w-5 h-5 fiori-tile-icon"></i>
                    </div>
                    <p class="fiori-tile-desc">Add a new physical venue to the system repository.</p>
                    <div class="fiori-tile-kpi">
                        <i data-lucide="door-open" class="w-8 h-8 opacity-20"></i>
                    </div>
                    <div class="fiori-tile-footer">
                        New Entry <i data-lucide="arrow-right" class="w-3 h-3 ml-2"></i>
                    </div>
                </a>

                <a href="venue_directory.php" class="fiori-tile">
                    <div class="fiori-tile-header">
                        <h3 class="fiori-tile-title">Venue Directory</h3>
                        <i data-lucide="database" class="w-5 h-5 fiori-tile-icon"></i>
                    </div>
                    <p class="fiori-tile-desc">Manage existing venues, capacities, and statuses.</p>
                    <div class="fiori-tile-kpi">
                        <?php echo $kpi_total; ?>
                    </div>
                    <div class="fiori-tile-footer">
                        View Records <i data-lucide="arrow-right" class="w-3 h-3 ml-2"></i>
                    </div>
                </a>

            </div>

            <div class="mb-6">
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Admin Management</h1>
                <p class="text-sm text-slate-500 mt-1">Control administrative access levels and core system personnel.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                
                <a href="add_admin.php" class="fiori-tile">
                    <div class="fiori-tile-header">
                        <h3 class="fiori-tile-title">Add Admin</h3>
                        <i data-lucide="shield" class="w-5 h-5 fiori-tile-icon"></i>
                    </div>
                    <p class="fiori-tile-desc">Add a new administrator to the system.</p>
                    <div class="fiori-tile-kpi">
                        <i data-lucide="user" class="w-8 h-8 opacity-20"></i>
                    </div>
                    <div class="fiori-tile-footer">
                        ADD NOW <i data-lucide="arrow-right" class="w-3 h-3 ml-2"></i>
                    </div>
                </a>
                


                <a href="admin_directory.php" class="fiori-tile">
                    <div class="fiori-tile-header">
                        <h3 class="fiori-tile-title">Admin Directory</h3>
                        <i data-lucide="shield" class="w-6 h-6  group-hover:text-white"></i>
                    </div>
                    <p class="fiori-tile-desc">Manage existing administrators, their roles, and contact information.</p>
                    <div class="fiori-tile-footer">
                        View Admins <i data-lucide="arrow-right" class="w-3 h-3 ml-2"></i>
                    </div>
                </a>

            </div>

            <div class="mb-6 border-t border-slate-200 pt-10">
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Staff Management</h2>
                <p class="text-sm text-slate-500 mt-1">Manage operational personnel, including venue inspectors and maintenance crew.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                
                <a href="add_staff.php" class="fiori-tile">
                    <div class="fiori-tile-header">
                        <h3 class="fiori-tile-title">Add Staff</h3>
                        <i data-lucide="plus-square" class="w-5 h-5 fiori-tile-icon"></i>
                    </div>
                    <p class="fiori-tile-desc">Add a new staff member to the system.</p>
                    <div class="fiori-tile-kpi">
                        <i data-lucide="user" class="w-8 h-8 opacity-20"></i>
                    </div>
                    <div class="fiori-tile-footer">
                        ADD NOW <i data-lucide="arrow-right" class="w-3 h-3 ml-2"></i>
                    </div>
                </a>


                

                <a href="staff_directory.php" class="fiori-tile">
                    <div class="fiori-tile-header">
                        <h3 class="fiori-tile-title">Staff Directory</h3>
                        <i data-lucide="users" class="w-6 h-6  group-hover:text-white"></i>
                    </div>
                    <p class="fiori-tile-desc">Manage existing staff members, their roles, and contact information.</p>
                    <div class="fiori-tile-kpi">
                        <?php 
                        // Fetch total staff count for KPI
                        $staff_count_query = "SELECT COUNT(*) AS total_staff FROM staff";
                        $staff_count_result = mysqli_query($conn, $staff_count_query);
                        $staff_count_row = mysqli_fetch_assoc($staff_count_result);
                        echo $staff_count_row['total_staff'];
                        ?>
                    </div>
                    <div class="fiori-tile-footer">
                        View Staffs <i data-lucide="arrow-right" class="w-3 h-3 ml-2"></i>
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