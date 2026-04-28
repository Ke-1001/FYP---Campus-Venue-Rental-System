<?php
// File: admin/inspections.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php'; 

// 💡 1. 狀態機同步：清道夫邏輯 (Sweep Logic)
// 將時間已過的 approved 轉為 completed，使其進入可指派/可檢驗佇列
$sweep_sql = "
    UPDATE booking 
    SET status = 'completed' 
    WHERE status = 'approved' 
    AND CONCAT(date_booked, ' ', ADDTIME(time_start, SEC_TO_TIME(duration * 60))) <= NOW()
";
$conn->query($sweep_sql);

// 💡 2. KPI 數據提取 (Vector State Retrieval)
$kpi_pending = $conn->query("SELECT COUNT(*) FROM inspection WHERE ins_status = 'pending'")->fetch_row()[0] ?? 0;
$kpi_tracked = $conn->query("SELECT COUNT(*) FROM inspection WHERE ins_status IN ('passed', 'failed')")->fetch_row()[0] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Inspection Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b', accent: '#38bdf8' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori-tile.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
        
        <?php 
        $topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Operations / Inspections</h2>';
        include('../includes/admin_topbar.php'); 
        ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="mb-8 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Venue Inspections</h1>
                <p class="text-xs text-slate-500 mt-1">Select a discrete module to execute post-usage assessments and track inspection history.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <a href="pending_inspections.php" class="fiori-tile">
                    <div class="fiori-tile-header">
                        <h3 class="fiori-tile-title">Pending Inspections</h3>
                        <i data-lucide="clipboard-list" class="w-5 h-5 fiori-tile-icon"></i>
                    </div>
                    <p class="fiori-tile-desc">Execute assessments for recently utilized venues.</p>
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
                    <p class="fiori-tile-desc">Review historical inspection logs and penalty records.</p>
                    <div class="fiori-tile-kpi">
                        <?php echo $kpi_tracked; ?>
                    </div>
                    <div class="fiori-tile-footer">
                        View History <i data-lucide="arrow-right" class="w-3 h-3 ml-2"></i>
                    </div>
                </a>

            </div>

            <div class="mt-10 p-6 bg-white rounded-lg border border-slate-200 flex flex-col md:flex-row justify-between items-center shadow-sm">
                <div class="mb-4 md:mb-0">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Inspection Subsystem Status</p>
                    <h4 class="text-sm font-bold text-slate-700 mt-1">Condition: <span class="text-emerald-600 ml-1">Operational</span></h4>
                </div>
                <div class="flex space-x-12">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 pb-1 mb-1">Awaiting Execution</p>
                        <p class="text-lg font-mono text-slate-800"><?php echo $kpi_pending; ?></p>
                    </div>
                </div>
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