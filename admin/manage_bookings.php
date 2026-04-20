<?php
// File: admin/manage_bookings.php

session_start();
require_once("../config/db.php");
require_once('../includes/admin_auth.php'); // 💡 注入安全閘道器 (已內建 session_start)
require_once("../config/db.php");

$bookings = [];
$sql_bookings = "
    SELECT 
        b.booking_id AS raw_id, 
        CONCAT('BKG-', LPAD(b.booking_id, 4, '0')) AS ref_id, 
        u.full_name AS entity, 
        CONCAT('UID-', LPAD(u.user_id, 4, '0')) AS uid, 
        v.venue_name AS venue, 
        b.booking_date AS date, 
        CONCAT(DATE_FORMAT(b.start_time, '%H:%i'), ' - ', DATE_FORMAT(b.end_time, '%H:%i')) AS time, 
        b.booking_status AS status 
    FROM bookings b 
    JOIN users u ON b.user_id = u.user_id 
    JOIN venues v ON b.venue_id = v.venue_id 
    WHERE b.payment_status != 'Pending'  /* 💡 INVARIANT: Hide unpaid temporary blocks */
    ORDER BY b.created_at DESC";

$result = $conn->query($sql_bookings);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}
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
                    <input type="text" placeholder="Filter Reference ID..." class="bg-transparent border-none outline-none w-64 text-sm focus:ring-0">
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
            <div class="mb-8">
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Booking Matrix</h1>
                <p class="text-sm text-slate-500 mt-1">Operational state management for venue reservations.</p>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-widest">
                            <th class="px-6 py-4 border-b border-slate-200">Reference</th>
                            <th class="px-6 py-4 border-b border-slate-200">Applicant Entity</th>
                            <th class="px-6 py-4 border-b border-slate-200">System State</th>
                            <th class="px-6 py-4 border-b border-slate-200 text-right">Execution</th>
                        </tr>
                    </thead>

                    <tbody class="text-sm text-slate-700 divide-y divide-slate-100">
                        <?php foreach($bookings as $row): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs font-bold text-mmu-blue"><?php echo $row['ref_id']; ?></td>
                            <td class="px-6 py-4 font-bold"><?php echo htmlspecialchars($row['entity']); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-amber-50 text-amber-600 border border-amber-200 rounded text-[10px] font-black uppercase"><?php echo $row['status']; ?></span>
                            </td>
                            
                            
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end space-x-2">
                                    <?php if($row['status'] === 'Pending'): ?>
                                        
                                        <a href="../actions/process_approval.php?id=<?php echo $row['raw_id']; ?>&action=approve" 
                                        onclick="triggerCustomConfirm(event, 'Confirm APPROVAL for <?php echo $row['ref_id']; ?>? This action will notify the applicant.', this.href);"
                                        class="p-1.5 text-emerald-600 bg-white border border-slate-200 rounded hover:bg-emerald-50 transition shadow-sm inline-block tooltip" 
                                        title="Approve">
                                            <i data-lucide="check" class="w-4 h-4"></i>
                                        </a>

                                        <a href="../actions/process_approval.php?id=<?php echo $row['raw_id']; ?>&action=reject" 
                                        onclick="triggerCustomConfirm(event, 'Confirm REJECTION and deposit refund for <?php echo $row['ref_id']; ?>? This action cannot be reversed.', this.href);"
                                        class="p-1.5 text-red-600 bg-white border border-slate-200 rounded hover:bg-red-50 transition shadow-sm inline-block tooltip" 
                                        title="Reject">
                                            <i data-lucide="x" class="w-4 h-4"></i>
                                        </a>
                                        
                                    <?php else: ?>
                                        <span class="text-[10px] font-mono text-slate-400 tracking-widest mt-1">PROCESSED</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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