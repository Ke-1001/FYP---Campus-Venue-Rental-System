<?php
// File: admin/track_inspections.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

// 💡 1. 提取多維度 Filter 參數 (涵蓋 5 個實體維度)
$filter_bid = trim($_GET['f_bid'] ?? '');
$filter_student = trim($_GET['f_student'] ?? '');
$filter_venue = trim($_GET['f_venue'] ?? '');
$filter_date = trim($_GET['f_date'] ?? '');
$filter_inspector = trim($_GET['f_inspector'] ?? '');
$filter_res = trim($_GET['f_res'] ?? '');

// 💡 2. 構建聚合查詢，提取深層實體屬性與損壞紀錄
$sql = "SELECT 
            i.ins_id,
            i.bid,
            i.ins_status,
            i.damage_desc,
            i.penalty,
            i.inspected_at,
            b.date_booked,
            u.uid AS student_id,
            u.username AS student_name,
            v.vname AS venue_name,
            v.category AS venue_category,
            s.staff_name AS inspector_name
        FROM inspection i
        JOIN booking b ON i.bid = b.bid
        JOIN user u ON b.uid = u.uid
        JOIN venue v ON b.vid = v.vid
        JOIN staff s ON i.sid = s.sid
        WHERE i.ins_status IN ('passed', 'failed')";

// 💡 3. 動態注入過濾條件 (Dynamic WHERE Clauses)
if (!empty($filter_bid)) {
    $sql .= " AND i.bid LIKE '%" . $conn->real_escape_string($filter_bid) . "%'";
}
if (!empty($filter_student)) {
    $sql .= " AND (u.username LIKE '%" . $conn->real_escape_string($filter_student) . "%' OR u.uid LIKE '%" . $conn->real_escape_string($filter_student) . "%')";
}
if (!empty($filter_venue)) {
    $sql .= " AND (v.vname LIKE '%" . $conn->real_escape_string($filter_venue) . "%' OR v.category LIKE '%" . $conn->real_escape_string($filter_venue) . "%')";
}
if (!empty($filter_date)) {
    $sql .= " AND b.date_booked = '" . $conn->real_escape_string($filter_date) . "'";
}
if (!empty($filter_inspector)) {
    $sql .= " AND s.staff_name LIKE '%" . $conn->real_escape_string($filter_inspector) . "%'";
}
if (!empty($filter_res)) {
    $sql .= " AND i.ins_status = '" . $conn->real_escape_string($filter_res) . "'";
}

$sql .= " ORDER BY i.inspected_at DESC, i.ins_id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Track Inspections</title>
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
        
        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0">
            <?php 
            $topbar_content = '
            <div class="flex items-center">
                <a href="inspections.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Reporting / Inspection History</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="mb-6">
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Inspection History</h1>
                <p class="text-xs text-slate-500 mt-1">Trace historical venue assessments and financial penalties using multidimensional queries.</p>
            </div>

            <!-- 💡 多維度過濾矩陣 (Multi-dimensional Filter Matrix) -->
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-4 items-end">
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Booking ID</label>
                        <input type="text" name="f_bid" value="<?php echo htmlspecialchars($filter_bid); ?>" placeholder="Search ID..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Student Entity</label>
                        <input type="text" name="f_student" value="<?php echo htmlspecialchars($filter_student); ?>" placeholder="Name or ID..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Asset Query</label>
                        <input type="text" name="f_venue" value="<?php echo htmlspecialchars($filter_venue); ?>" placeholder="Venue or Category..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Usage Date</label>
                        <input type="date" name="f_date" value="<?php echo htmlspecialchars($filter_date); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none text-slate-600">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Inspector</label>
                        <input type="text" name="f_inspector" value="<?php echo htmlspecialchars($filter_inspector); ?>" placeholder="Personnel Name..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Result State</label>
                        <select name="f_res" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none bg-white font-medium text-slate-700">
                            <option value="">All Results</option>
                            <option value="passed" <?php if($filter_res==='passed') echo 'selected'; ?>>Passed</option>
                            <option value="failed" <?php if($filter_res==='failed') echo 'selected'; ?>>Failed</option>
                        </select>
                    </div>

                    <div class="flex space-x-2">
                        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition shadow-sm flex items-center justify-center">
                            <i data-lucide="filter" class="w-4 h-4 mr-1"></i>
                        </button>
                        <a href="track_inspections.php" class="px-4 py-2 bg-slate-100 text-slate-600 text-sm font-bold rounded-lg hover:bg-slate-200 transition flex items-center justify-center" title="Reset Filters">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        </a>
                    </div>

                </form>
            </div>

            <!-- 💡 擴展資料表格 -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="text-[11px] font-black text-slate-500 uppercase tracking-widest">Global Record Log (<?php echo $result->num_rows; ?>)</h3>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white text-[10px] text-slate-400 font-black uppercase tracking-widest border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3">Reference</th>
                            <th class="px-6 py-3">Student & Asset</th>
                            <th class="px-6 py-3">Inspector Auth</th>
                            <th class="px-6 py-3">Assessment Outcome</th>
                            <th class="px-6 py-3 text-right">Tracing Flow</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-50">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $is_failed = ($row['ins_status'] === 'failed');
                            ?>
                            <tr class="hover:bg-indigo-50/50 cursor-pointer transition-colors group" onclick="window.location.href='process_flow.php?bid=<?php echo urlencode($row['bid']); ?>'">
                                <td class="px-6 py-4">
                                    <span class="font-mono text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded border border-indigo-100"><?php echo htmlspecialchars($row['bid']); ?></span>
                                    <p class="text-[9px] text-slate-400 mt-2 font-mono uppercase">Usage: <?php echo htmlspecialchars($row['date_booked']); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-800 block"><?php echo htmlspecialchars($row['student_name']); ?> <span class="text-[10px] font-mono text-slate-500 font-normal">(<?php echo htmlspecialchars($row['student_id']); ?>)</span></span>
                                    <div class="flex items-center mt-1">
                                        <span class="text-xs text-slate-600 mr-2"><?php echo htmlspecialchars($row['venue_name']); ?></span>
                                        <span class="px-1.5 py-0.5 bg-slate-100 text-slate-500 text-[8px] font-bold uppercase tracking-wider rounded border border-slate-200"><?php echo htmlspecialchars(strtoupper($row['venue_category'])); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-700 block text-xs flex items-center">
                                        <i data-lucide="shield-check" class="w-3 h-3 mr-1 text-slate-400"></i> <?php echo htmlspecialchars($row['inspector_name']); ?>
                                    </span>
                                    <span class="text-[9px] font-mono text-slate-400 block mt-1 uppercase">Log: <?php echo $row['inspected_at'] ? date('M d, H:i', strtotime($row['inspected_at'])) : '--'; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($is_failed): ?>
                                        <div class="mb-1">
                                            <span class="px-2 py-0.5 border bg-red-50 text-red-700 border-red-200 rounded text-[10px] font-black uppercase tracking-wider">Failed</span>
                                            <span class="ml-2 font-mono font-bold text-red-600 text-xs">-RM <?php echo number_format($row['penalty'], 2); ?></span>
                                        </div>
                                        <p class="text-[10px] text-slate-500 italic max-w-[200px] truncate" title="<?php echo htmlspecialchars($row['damage_desc']); ?>">
                                            "<?php echo htmlspecialchars($row['damage_desc']); ?>"
                                        </p>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 border bg-emerald-50 text-emerald-700 border-emerald-200 rounded text-[10px] font-black uppercase tracking-wider">Passed</span>
                                        <p class="text-[10px] text-slate-400 mt-1 italic">Clearance granted. Full refund authorized.</p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right text-slate-300 group-hover:text-indigo-600 transition-colors">
                                    <span class="text-xs font-bold uppercase tracking-widest mr-2 opacity-0 group-hover:opacity-100 transition-opacity">Trace</span>
                                    <i data-lucide="arrow-right-circle" class="w-6 h-6 inline"></i>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500 font-medium">
                                    <i data-lucide="search-x" class="w-12 h-12 mx-auto text-slate-300 mb-3 opacity-50"></i>
                                    Query returned zero vectors. No historical records match criteria.
                                </td>
                            </tr>
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