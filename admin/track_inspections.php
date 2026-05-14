<?php
// File: admin/track_inspections.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

// 💡 1. 提取多維度 Filter 參數
$filter_bid = trim($_GET['f_bid'] ?? '');
$filter_student = trim($_GET['f_student'] ?? '');
$filter_venue = trim($_GET['f_venue'] ?? '');
$filter_date = trim($_GET['f_date'] ?? '');
$filter_inspector = trim($_GET['f_inspector'] ?? '');
$filter_res = trim($_GET['f_res'] ?? '');

// 💡 2. 構建聚合查詢 (適配 rental_venue 4.sql 結構)
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
            vc.category AS venue_category,
            s.staff_name AS inspector_name
        FROM inspection i
        JOIN booking b ON i.bid = b.bid
        JOIN user u ON b.uid = u.uid
        JOIN venue v ON b.vid = v.vid
        JOIN vcategory vc ON v.vcid = vc.vcid
        JOIN staff s ON i.sid = s.sid
        WHERE i.ins_status IN ('passed', 'failed')";

// 💡 3. 動態注入過濾條件 (修正 vc.category)
if (!empty($filter_bid)) {
    $sql .= " AND i.bid LIKE '%" . $conn->real_escape_string($filter_bid) . "%'";
}
if (!empty($filter_student)) {
    $sql .= " AND (u.username LIKE '%" . $conn->real_escape_string($filter_student) . "%' OR u.uid LIKE '%" . $conn->real_escape_string($filter_student) . "%')";
}
if (!empty($filter_venue)) {
    $sql .= " AND (v.vname LIKE '%" . $conn->real_escape_string($filter_venue) . "%' OR vc.category LIKE '%" . $conn->real_escape_string($filter_venue) . "%')";
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
        tailwind.config = { theme: { extend: { colors: { cstyle: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori_forms.css">
</head>
<body class="bg-[#f4f4f4] text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0 bg-white">
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
                <p class="text-sm text-slate-600 mt-1">Trace historical venue assessments and financial penalties.</p>
            </div>

            <div class="bg-white p-5 rounded-lg shadow-sm border border-slate-200 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-4 items-end">
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Ref ID</label>
                        <input type="text" name="f_bid" value="<?php echo htmlspecialchars($filter_bid); ?>" placeholder="Search BID..." class="fiori-input w-full">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Student Entity</label>
                        <input type="text" name="f_student" value="<?php echo htmlspecialchars($filter_student); ?>" placeholder="Name or ID..." class="fiori-input w-full">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Asset</label>
                        <input type="text" name="f_venue" value="<?php echo htmlspecialchars($filter_venue); ?>" placeholder="Venue or Category..." class="fiori-input w-full">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Event Date</label>
                        <input type="date" name="f_date" value="<?php echo htmlspecialchars($filter_date); ?>" class="fiori-input w-full">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Inspector</label>
                        <input type="text" name="f_inspector" value="<?php echo htmlspecialchars($filter_inspector); ?>" placeholder="Personnel..." class="fiori-input w-full">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Result State</label>
                        <select name="f_res" class="fiori-input w-full bg-white font-bold text-slate-700 cursor-pointer">
                            <option value="">All Results</option>
                            <option value="passed" <?php if($filter_res==='passed') echo 'selected'; ?>>Passed</option>
                            <option value="failed" <?php if($filter_res==='failed') echo 'selected'; ?>>Failed</option>
                        </select>
                    </div>

                    <div class="flex space-x-2">
                        <button type="submit" class="w-full py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition shadow-sm">
                            Apply
                        </button>
                        <a href="track_inspections.php" class="px-4 py-2 bg-slate-100 text-slate-600 text-sm font-bold rounded-lg hover:bg-slate-200 transition flex items-center justify-center" title="Reset">
                            <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
                    <h3 class="text-xs font-black text-slate-600 uppercase tracking-widest">Global Record Log (<?php echo $result->num_rows; ?>)</h3>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white text-xs text-slate-500 font-black uppercase tracking-widest border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 w-40">Reference</th>
                            <th class="px-6 py-4 w-56">Student Profile</th>
                            <th class="px-6 py-4 w-64">Asset & Personnel</th>
                            <th class="px-6 py-4">Assessment Outcome</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-100">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $is_failed = ($row['ins_status'] === 'failed');
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors align-top">
                                <td class="px-6 py-5">
                                    <a href="process_flow.php?bid=<?php echo urlencode($row['bid']); ?>" class="font-mono text-sm font-extrabold text-[#0a6ed1] hover:text-[#085caf] hover:underline block mb-2 transition-colors">
                                        <?php echo htmlspecialchars($row['bid']); ?>
                                    </a>
                                    <span class="text-[10px] text-slate-500 font-mono font-bold block mb-1">Evt: <?php echo htmlspecialchars($row['date_booked']); ?></span>
                                    <span class="text-[10px] text-indigo-500 font-mono font-bold block">Log: <?php echo $row['inspected_at'] ? date('m-d, H:i', strtotime($row['inspected_at'])) : '--'; ?></span>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="font-extrabold text-slate-800 text-base block mb-1"><?php echo htmlspecialchars($row['student_name']); ?></span>
                                    <span class="text-xs font-mono font-bold text-slate-600 block"><?php echo htmlspecialchars($row['student_id']); ?></span>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="font-extrabold text-slate-800 text-base block mb-1"><?php echo htmlspecialchars($row['venue_name']); ?></span>
                                    <span class="px-2 py-1 bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-wider rounded border border-slate-200 inline-block mb-3"><?php echo htmlspecialchars($row['venue_category']); ?></span>
                                    
                                    <div class="text-xs font-bold text-slate-700">
                                        <span class="text-[10px] font-sans text-slate-500 uppercase block mb-0.5">Inspector:</span>
                                        <?php echo htmlspecialchars($row['inspector_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <?php if ($is_failed): ?>
                                        <div class="mb-3 flex items-center">
                                            <span class="px-2 py-1 border bg-red-50 text-red-700 border-red-200 rounded text-xs font-black uppercase tracking-wider">Failed</span>
                                            <span class="ml-3 font-mono font-extrabold text-red-600 text-sm">-RM <?php echo number_format($row['penalty'], 2); ?></span>
                                        </div>
                                        <div class="text-xs text-slate-700 font-medium leading-relaxed whitespace-normal bg-red-50/30 p-3 border border-red-100 rounded-lg">
                                            <span class="text-[10px] font-black text-red-400 uppercase tracking-widest block mb-1">Observations:</span>
                                            <?php echo nl2br(htmlspecialchars($row['damage_desc'])); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="inline-block px-2 py-1 border bg-emerald-50 text-emerald-700 border-emerald-200 rounded text-xs font-black uppercase tracking-wider mb-2">Passed</span>
                                        <p class="text-xs text-slate-500 font-medium italic">Clearance granted. Full refund authorized.</p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-16 text-center text-slate-500 font-medium">
                                    <span class="block text-4xl mb-3">🔍</span>
                                    No historical records match the criteria.
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