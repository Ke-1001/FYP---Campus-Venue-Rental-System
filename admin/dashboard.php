<?php
// File: admin/dashboard.php
session_start();
require_once("../config/db.php");

// 💡 1. 系統狀態矩陣運算 (System State Metrics)
$kpi_requests = $conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0] ?? 0;
$kpi_pending = $conn->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'Pending'")->fetch_row()[0] ?? 0;
$kpi_conflicts = $conn->query("SELECT COUNT(*) FROM inspections WHERE inspection_status != 'Good'")->fetch_row()[0] ?? 0;

// 由於缺乏實際時間軸的利用率運算，暫時使用基礎佔用比率近似值
$total_venues = $conn->query("SELECT COUNT(*) FROM venues WHERE status = 'Available'")->fetch_row()[0] ?? 1;
$kpi_utilization = min(round(($kpi_requests / $total_venues) * 20, 1), 100); 

// 💡 2. 待處理佇列萃取 (Pending Queue Extraction)
$pending_list = [];
$sql_pending = "
    SELECT 
        CONCAT('BKG-', LPAD(b.booking_id, 4, '0')) AS id, 
        u.full_name AS applicant, 
        u.email AS uid, 
        v.venue_name AS venue, 
        b.booking_date AS date, 
        CONCAT(DATE_FORMAT(b.start_time, '%H:%i'), ' - ', DATE_FORMAT(b.end_time, '%H:%i')) AS time 
    FROM bookings b 
    JOIN users u ON b.user_id = u.user_id 
    JOIN venues v ON b.venue_id = v.venue_id 
    WHERE b.booking_status = 'Pending' 
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
        
        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0">
            <div class="flex items-center">
                <button onclick="toggleSidebar()" class="p-2 mr-4 text-slate-500 hover:text-mmu-blue transition-colors rounded-lg hover:bg-slate-100 focus:outline-none">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <div class="flex items-center text-slate-500 bg-white px-4 py-2 rounded-lg border border-slate-200 focus-within:border-mmu-blue shadow-sm transition-all">
                    <i data-lucide="search" class="w-4 h-4 mr-2"></i>
                    <input type="text" placeholder="Search system assets..." class="bg-transparent border-none outline-none w-64 text-sm focus:ring-0">
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <button class="relative p-2 text-slate-500 hover:text-mmu-blue transition-colors rounded-full hover:bg-slate-100">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                </button>
                <button class="p-2 text-slate-500 hover:text-mmu-blue rounded-full hover:bg-slate-100">
                    <i data-lucide="user-circle" class="w-5 h-5"></i>
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight mb-8">System Overview</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Active Requests</p>
                    <h3 class="text-4xl font-black text-slate-800 mt-4 font-mono"><?php echo $kpi_requests; ?></h3>
                </div>
                <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Pending Validations</p>
                    <h3 class="text-4xl font-black text-amber-500 mt-4 font-mono"><?php echo $kpi_pending; ?></h3>
                </div>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();

        // 核心狀態機控制函數
        function toggleSidebar() {
            const sidebar = document.getElementById('system-sidebar');
            // 切換 layout.css 中定義的 .sidebar-collapsed 狀態
            sidebar.classList.toggle('sidebar-collapsed');
        }
    </script>
</body>
</html>