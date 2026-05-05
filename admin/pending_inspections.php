<?php
// File: admin/pending_inspections.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

$filter_bid = $_GET['f_bid'] ?? '';
$filter_venue = $_GET['f_venue'] ?? '';

// 💡 直接讀取 time_end
$sql = "SELECT 
            b.bid, 
            b.date_booked, 
            b.time_start,
            b.time_end,
            b.status AS booking_status,
            u.username, 
            v.vname, 
            s.staff_name AS inspector_name
        FROM inspection i
        JOIN booking b ON i.bid = b.bid
        JOIN user u ON b.uid = u.uid
        JOIN venue v ON b.vid = v.vid
        JOIN staff s ON i.sid = s.sid
        WHERE i.ins_status = 'pending'";

if (!empty($filter_bid)) {
    $sql .= " AND b.bid LIKE '%" . $conn->real_escape_string($filter_bid) . "%'";
}
if (!empty($filter_venue)) {
    $sql .= " AND v.vname LIKE '%" . $conn->real_escape_string($filter_venue) . "%'";
}

$sql .= " ORDER BY b.date_booked ASC, b.time_start ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Pending Inspections</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
</head>
<body class="bg-[#f7f9fa] text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
        
            <?php 
            $topbar_content = '
            <div class="flex items-center">
                <a href="inspections.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Operations / Pending Inspections</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="mb-6 flex justify-between items-end">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Pending Inspections</h1>
                    <p class="text-xs text-slate-500 mt-1">Monitor upcoming and ready-to-execute post-event physical assessments.</p>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-6">
                <form method="GET" class="flex flex-wrap md:flex-nowrap gap-4 items-end">
                    <div class="w-full md:w-1/3">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Booking ID</label>
                        <input type="text" name="f_bid" value="<?php echo htmlspecialchars($filter_bid); ?>" placeholder="Search ID..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none transition-all">
                    </div>
                    <div class="w-full md:w-1/3">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Venue Name</label>
                        <input type="text" name="f_venue" value="<?php echo htmlspecialchars($filter_venue); ?>" placeholder="Search Venue..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none transition-all">
                    </div>
                    <div class="w-full md:w-auto flex space-x-2">
                        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition shadow-sm">Go</button>
                        <a href="pending_inspections.php" class="px-5 py-2 bg-slate-100 text-slate-600 text-sm font-bold rounded-lg hover:bg-slate-200 transition">Reset</a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="text-[11px] font-black text-slate-500 uppercase tracking-widest">Inspection Queue (<?php echo $result->num_rows; ?>)</h3>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white text-[10px] text-slate-400 font-black uppercase tracking-widest border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3">Booking ID</th>
                            <th class="px-6 py-3">Student & Venue</th>
                            <th class="px-6 py-3">Assigned Inspector</th>
                            <th class="px-6 py-3">Scheduled Time</th>
                            <th class="px-6 py-3 text-right">Execution State</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-50">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                // 💡 絕對端點提取，無時間偏移
                                $time_range = date('H:i', strtotime($row['time_start'])) . ' - ' . date('H:i', strtotime($row['time_end']));
                                
                                // 狀態解鎖條件不變，取決於 sweep_sql
                                $is_ready = ($row['booking_status'] === 'completed');
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-mono font-bold <?php echo $is_ready ? 'text-indigo-600' : 'text-slate-400'; ?>"><?php echo htmlspecialchars($row['bid']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="font-bold <?php echo $is_ready ? 'text-slate-700' : 'text-slate-500'; ?> block"><?php echo htmlspecialchars($row['username']); ?></span>
                                    <span class="text-xs text-slate-400"><?php echo htmlspecialchars($row['vname']); ?></span>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-600">
                                    <i data-lucide="user-check" class="w-3 h-3 inline mr-1 text-slate-400"></i><?php echo htmlspecialchars($row['inspector_name']); ?>
                                </td>
                                <td class="px-6 py-4 font-medium">
                                    <span class="block <?php echo $is_ready ? 'text-slate-700' : 'text-slate-500'; ?>"><?php echo htmlspecialchars($row['date_booked']); ?></span>
                                    <span class="text-xs font-mono text-slate-400"><?php echo $time_range; ?></span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <?php if ($is_ready): ?>
                                        <button onclick="window.location.href='execute_inspection.php?bid=<?php echo urlencode($row['bid']); ?>'" class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition shadow-sm flex items-center ml-auto">
                                            Execute <i data-lucide="chevron-right" class="w-4 h-4 ml-1"></i>
                                        </button>
                                    <?php else: ?>
                                        <div class="inline-flex items-center px-3 py-1.5 bg-slate-100 border border-slate-200 text-slate-400 rounded-lg text-[10px] font-black uppercase tracking-widest cursor-not-allowed" title="Venue is currently in use or awaiting usage.">
                                            <i data-lucide="lock" class="w-3 h-3 mr-1.5"></i> In Use
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium">No pending inspections in the queue.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();
        function toggleSidebar() { document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed'); }
    </script>
</body>
</html>