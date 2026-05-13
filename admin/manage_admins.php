<?php
// File: admin/manage_admins.php
session_start();
require_once("../config/db.php");
require_once('../includes/admin_auth.php'); 

// 💡 統計總人數 KPI (使用 UNION 進行純量聚合)
$kpi_sql = "SELECT COUNT(*) FROM (
                SELECT aid FROM admin 
                UNION ALL 
                SELECT sid FROM staff
            ) AS combined_personnel";
$kpi_total = $conn->query($kpi_sql)->fetch_row()[0] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Admin | Staff Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { cstyle: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori-tile.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">
        
            <?php 
            $topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Identity Management / Staff Management</h2>';
            include('../includes/admin_topbar.php'); 
            ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
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

    <script>
        lucide.createIcons();
        function toggleSidebar() { document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed'); }
    </script>
</body>
</html>