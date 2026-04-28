<?php
// File: admin/dashboard.php

session_start();
require_once("../config/db.php");
require_once('../includes/admin_auth.php'); 

// 💡 1. 適配新架構：清道夫邏輯 (將已批准且時間過期的訂單狀態轉為 completed)
// 動態使用 time_start 與 duration 來計算結束時間
$sweep_sql = "
    UPDATE booking 
    SET status = 'completed' 
    WHERE status = 'approved' 
    AND CONCAT(date_booked, ' ', ADDTIME(time_start, SEC_TO_TIME(duration * 60))) <= NOW()
";
$conn->query($sweep_sql);

// 💡 2. 適配新架構：System State Metrics Extraction
$kpi_requests = $conn->query("SELECT COUNT(*) FROM booking")->fetch_row()[0] ?? 0;
// 嚴謹的 Pending 狀態：訂單 pending 且已付款
$kpi_pending = $conn->query("SELECT COUNT(*) FROM booking WHERE status = 'pending' AND payment_status = 'paid'")->fetch_row()[0] ?? 0;
// 衝突狀態：對接 inspection 表格，抓取 failed 數量
$kpi_conflicts = $conn->query("SELECT COUNT(*) FROM inspection WHERE ins_status = 'failed'")->fetch_row()[0] ?? 0;

// 💡 修復 DivisionByZeroError：強制轉型並導入安全防護邏輯
$total_venues = (int)($conn->query("SELECT COUNT(*) FROM venue WHERE status = 'available'")->fetch_row()[0] ?? 0);

// 安全除法向量 (Safe Division Vector)
if ($total_venues > 0) {
    $kpi_utilization = min(round(($kpi_requests / $total_venues) * 20, 1), 100); 
} else {
    $kpi_utilization = 0; // 若無可用場地，利用率絕對為 0
} 

// 💡 3. 適配新架構：Pending Queue Extraction
$pending_list = [];
$sql_pending = "
    SELECT 
        b.bid AS id, 
        u.username AS applicant, 
        u.email AS uid, 
        v.vname AS venue, 
        b.date_booked AS date, 
        CONCAT(DATE_FORMAT(b.time_start, '%H:%i'), ' - ', DATE_FORMAT(ADDTIME(b.time_start, SEC_TO_TIME(b.duration * 60)), '%H:%i')) AS time 
    FROM booking b 
    JOIN user u ON b.uid = u.uid 
    JOIN venue v ON b.vid = v.vid 
    WHERE b.status = 'pending' AND b.payment_status = 'paid'
    ORDER BY b.created_at ASC LIMIT 5";

$result = $conn->query($sql_pending);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pending_list[] = $row;
    }
}
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