<?php
// File: admin/manage_bookings.php
session_start();
require_once("../config/db.php");
require_once('../includes/admin_auth.php'); 

// 💡 1. 提取實時指標矩陣 (Live Metrics Extraction)
// [Database Migration Applied: v2.0 Schema]

// KPI 1: 待審批 (Pending) -> 條件：預約狀態為 pending 且 已付款 (paid)
$kpi_pending = $conn->query("SELECT COUNT(*) FROM booking WHERE status = 'pending' AND payment_status = 'paid'")->fetch_row()[0] ?? 0;

// KPI 2: 進行中 (Ongoing) -> 條件：預約狀態為 approved
$kpi_ongoing = $conn->query("SELECT COUNT(*) FROM booking WHERE status = 'approved'")->fetch_row()[0] ?? 0;

// KPI 3: 待審核/已結束 (Awaiting Audit) -> 條件：預約狀態為 completed (取代舊版的 Returned)
$kpi_returned = $conn->query("SELECT COUNT(*) FROM booking WHERE status = 'completed'")->fetch_row()[0] ?? 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Operations Launchpad</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b', accent: '#38bdf8' } } } } }
    </script>
    <link rel="stylesheet" href="../assets/css/fiori-tile.css">
    <style>
        /* 💡 SAP Fiori 瓷磚懸停提升效應 */
        .fiori-tile {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        .fiori-tile:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
        
        <?php 
        $topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">System Operations / Launchpad</h2>';
        include('../includes/admin_topbar.php'); 
        ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="mb-10">
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Management Launchpad</h1>
                <p class="text-sm text-slate-500 mt-1">Select an operational module to begin data processing.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <a href="pending_requests.php" class="fiori-tile">
                    
                    <div>
                        <div class="fiori-tile-header">
                            <div class="fiori-tile-icon">
                                <i data-lucide="activity"></i>
                            </div>
                            <div class="fiori-tile-title">
                                Pending Requests
                            </div>
                        </div>

                        <div class="fiori-tile-subtext">
                            Monitor all pending bookings
                        </div>
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