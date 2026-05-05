<?php
// File: admin/inspections.php
session_start();
require_once '../config/db.php';
require_once('../includes/admin_auth.php'); 

// 💡 1. 狀態機同步：清道夫邏輯 (Sweep Logic) - v3.2 端點模式
// 確保當前時間超越 time_end 時，自動將狀態推移至 completed，解鎖檢驗權限
$sweep_sql = "
    UPDATE booking 
    SET status = 'completed' 
    WHERE status = 'approved' 
    AND CONCAT(date_booked, ' ', time_end) <= NOW()
";
$conn->query($sweep_sql);

// 💡 2. 向量狀態提取 (Vector State Retrieval) / KPI 計算
// $N_{pending}$: 已經 ready for inspection 的數量
$sql_kpi_pending = "SELECT COUNT(*) FROM inspection i JOIN booking b ON i.bid = b.bid WHERE i.ins_status = 'pending' AND b.status = 'completed'";
$kpi_pending = $conn->query($sql_kpi_pending)->fetch_row()[0] ?? 0;

// $N_{tracked}$: 已經完成 (passed/failed) 的歷史數量
$sql_kpi_tracked = "SELECT COUNT(*) FROM inspection WHERE ins_status IN ('passed', 'failed')";
$kpi_tracked = $conn->query($sql_kpi_tracked)->fetch_row()[0] ?? 0;
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
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori-tile.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
        
            <?php 
            $topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Operations / Inspections Dashboard</h2>';
            include('../includes/admin_topbar.php'); 
            ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="mb-8 border-b border-slate-200 pb-4">
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