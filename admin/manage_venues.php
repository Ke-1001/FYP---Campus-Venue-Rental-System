<?php
// File: admin/manage_venues.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

// 💡 KPI 提取：計算總場地與可用場地數量
$kpi_total = $conn->query("SELECT COUNT(*) FROM venue")->fetch_row()[0] ?? 0;
$kpi_available = $conn->query("SELECT COUNT(*) FROM venue WHERE status = 'available'")->fetch_row()[0] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Venue Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori-tile.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
        
            <?php 
            $topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">System Administration / Manage Venues</h2>';
            include('../includes/admin_topbar.php'); 
            ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="mb-8 border-b border-slate-200 pb-4">
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

                <a href="manage_categories.php" class="fiori-tile">
                    <div class="fiori-tile-header">
                        <h3 class="fiori-tile-title">Manage Categories</h3>
                        <i data-lucide="tags" class="w-5 h-5 fiori-tile-icon"></i>
                    </div>
                    <p class="fiori-tile-desc">Define and govern persistent venue classification tags.</p>
                    <div class="fiori-tile-kpi">
                        <?php 
                        // 💡 可以在頂部 PHP 加入這行: $kpi_cat = $conn->query("SELECT COUNT(*) FROM venue_category")->fetch_row()[0] ?? 0;
                        echo $kpi_cat ?? '--'; 
                        ?>
                    </div>
                    <div class="fiori-tile-footer">
                        Configure Tags <i data-lucide="arrow-right" class="w-3 h-3 ml-2"></i>
                    </div>
                </a>

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