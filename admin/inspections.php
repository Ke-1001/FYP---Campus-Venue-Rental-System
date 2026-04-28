<?php
// File: admin/inspections.php
session_start();
require_once '../config/db.php';
require_once('../includes/admin_auth.php'); 

// 💡 1. 狀態機同步：清道夫邏輯 (Sweep Logic) - v3.2 端點模式
$sweep_sql = "
    UPDATE booking 
    SET status = 'completed' 
    WHERE status = 'approved' 
    AND CONCAT(date_booked, ' ', time_end) <= NOW()
";
$conn->query($sweep_sql);

// 💡 2. 提取 Pending Inspections (使用 time_end 渲染時間向量)
$sql = "SELECT 
            b.bid AS raw_id, 
            b.bid AS ref_id,
            b.date_booked AS booking_date, 
            CONCAT(DATE_FORMAT(b.time_start, '%H:%i'), ' - ', DATE_FORMAT(b.time_end, '%H:%i')) AS time_slot, 
            b.status AS booking_status,
            u.username AS student_name, 
            v.vname AS venue_name, 
            v.deposit AS deposit_paid
        FROM booking b
        JOIN user u ON b.uid = u.uid
        JOIN venue v ON b.vid = v.vid
        WHERE b.status = 'completed' AND b.payment_status = 'paid'
        ORDER BY b.date_booked ASC, b.time_start ASC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Admin | Pending Inspections</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">
    <?php include('../includes/admin_sidebar.php'); ?>
    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
        <?php 
        $topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Operations / Inspections</h2>';
        include('../includes/admin_topbar.php'); 
        ?>
        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            <div class="mb-8">
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Pending Inspections</h1>
                <p class="text-sm text-slate-500 mt-1">Execute post-usage venue assessments and settle financial deposits.</p>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 text-[10px] text-slate-400 font-black uppercase tracking-widest border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3">Reference</th>
                            <th class="px-6 py-3">Entity & Venue</th>
                            <th class="px-6 py-3">Temporal Vector</th>
                            <th class="px-6 py-3">Deposit Blocked</th>
                            <th class="px-6 py-3 text-right">Execution</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-50">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-indigo-50/50 transition-colors">
                                <td class="px-6 py-4 font-mono font-bold text-indigo-600"><?php echo $row['ref_id']; ?></td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-700 block"><?php echo htmlspecialchars($row['student_name']); ?></span>
                                    <span class="text-xs text-slate-400 flex items-center"><i data-lucide="map-pin" class="w-3 h-3 mr-1"></i><?php echo htmlspecialchars($row['venue_name']); ?></span>
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-600">
                                    <span class="block"><?php echo $row['booking_date']; ?></span>
                                    <span class="text-xs font-mono text-slate-400"><?php echo $row['time_slot']; ?></span>
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-emerald-600">RM <?php echo number_format($row['deposit_paid'], 2); ?></td>
                                <td class="px-6 py-4 text-right">
                                    <button onclick="window.location.href='execute_inspection.php?bid=<?php echo urlencode($row['raw_id']); ?>'" class="px-3 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded transition shadow-sm">
                                        Inspect <i data-lucide="chevron-right" class="w-3 h-3 inline ml-1"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium">System Clear: No pending inspections required at this time.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <script>lucide.createIcons();</script>
</body>
</html>