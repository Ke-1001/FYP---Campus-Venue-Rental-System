<?php
// File: admin/pending_requests.php
session_start();
require_once("../config/db.php");
require_once('../includes/admin_auth.php'); 

// 💡 1. 多維度過濾引擎 (Dynamic Multi-vector Filtering Engine)
$filter_ref = $_GET['ref'] ?? '';
$filter_entity = $_GET['entity'] ?? '';
$filter_date = $_GET['date'] ?? '';

// 💡 適配新架構：status = 'pending' 且 payment_status = 'paid'
$where_clauses = ["b.status = 'pending'", "b.payment_status = 'paid'"];
$params = [];
$types = "";

if (!empty($filter_ref)) {
    // 新架構的 bid 已經是 VARCHAR，直接 LIKE 即可
    $where_clauses[] = "b.bid LIKE ?";
    $params[] = "%" . $filter_ref . "%";
    $types .= "s";
}
if (!empty($filter_entity)) {
    // 新架構使用 username
    $where_clauses[] = "u.username LIKE ?";
    $params[] = "%$filter_entity%";
    $types .= "s";
}
if (!empty($filter_date)) {
    // 新架構使用 date_booked
    $where_clauses[] = "b.date_booked = ?";
    $params[] = $filter_date;
    $types .= "s";
}

$final_where = implode(" AND ", $where_clauses);

// 💡 2. 核心查詢重構
// - 使用 time_start 與 duration 動態計算出 end_time
// - 使用單數表名與新外鍵 (uid, vid)
$sql = "SELECT 
            b.bid AS raw_id, 
            b.bid AS ref_id, 
            u.username AS entity, 
            v.vname AS venue, 
            b.date_booked AS date, 
            CONCAT(
                DATE_FORMAT(b.time_start, '%H:%i'), 
                ' - ', 
                DATE_FORMAT(ADDTIME(b.time_start, SEC_TO_TIME(b.duration * 60)), '%H:%i')
            ) AS time
        FROM booking b 
        JOIN user u ON b.uid = u.uid 
        JOIN venue v ON b.vid = v.vid 
        WHERE $final_where
        ORDER BY b.created_at ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Pending Requests</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b', accent: '#38bdf8' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
</head>
<body class="bg-slate-100 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <?php 
        $topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Launchpad / Pending Requests</h2>';
        include('../includes/admin_topbar.php'); 
        ?>

        <div class="flex-1 overflow-y-auto p-6 scroll-smooth">
            
            <div class="mb-6">
                <a href="manage_bookings.php" class="text-xs font-bold text-mmu-blue hover:underline flex items-center mb-2">
                    <i data-lucide="arrow-left" class="w-3 h-3 mr-1"></i> Back to Launchpad
                </a>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight">Active Booking Requests</h1>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Reference ID</label>
                        <input type="text" name="ref" value="<?php echo htmlspecialchars($filter_ref); ?>" placeholder="e.g. BKG-0001" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-mmu-blue outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Applicant Name</label>
                        <input type="text" name="entity" value="<?php echo htmlspecialchars($filter_entity); ?>" placeholder="Search student..." class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-mmu-blue outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Booking Date</label>
                        <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-mmu-blue outline-none transition-all">
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" class="flex-1 bg-mmu-blue text-white py-2 rounded-lg text-sm font-bold shadow-md hover:bg-blue-700 transition">Go</button>
                        <a href="pending_requests.php" class="px-4 py-2 bg-slate-100 text-slate-500 rounded-lg text-sm font-bold hover:bg-slate-200 transition text-center">Reset</a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-[10px] font-black uppercase tracking-widest">
                            <th class="px-6 py-4 border-b border-slate-200">Reference</th>
                            <th class="px-6 py-4 border-b border-slate-200">Applicant Entity</th>
                            <th class="px-6 py-4 border-b border-slate-200">Target Node</th>
                            <th class="px-6 py-4 border-b border-slate-200">Temporal Vector</th>
                            <th class="px-6 py-4 border-b border-slate-200 text-right">Execution</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-700 divide-y divide-slate-100">
                        <?php if($result->num_rows === 0): ?>
                            <tr><td colspan="5" class="px-6 py-16 text-center text-slate-400 font-bold italic">No pending requests matched the current filter criteria.</td></tr>
                        <?php else: while($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs font-bold text-mmu-blue"><?php echo htmlspecialchars($row['ref_id']); ?></td>
                            <td class="px-6 py-4 font-bold text-slate-800"><?php echo htmlspecialchars($row['entity']); ?></td>
                            <td class="px-6 py-4 font-medium text-slate-600"><?php echo htmlspecialchars($row['venue']); ?></td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-700"><?php echo htmlspecialchars($row['date']); ?></p>
                                <p class="text-[10px] text-slate-500 font-mono"><?php echo htmlspecialchars($row['time']); ?></p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form action="../actions/process_booking_action.php" method="POST" class="inline-flex space-x-2">
                                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($row['raw_id']); ?>">
                                    <button type="submit" name="action_type" value="approve" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-black uppercase rounded shadow-sm transition">
                                        Approve
                                    </button>
                                    <button type="submit" name="action_type" value="reject" onclick="return confirm('Reject this request?');" class="px-4 py-1.5 bg-white border border-red-500 text-red-600 hover:bg-red-50 text-[10px] font-black uppercase rounded transition">
                                        Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 px-2 flex justify-between items-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                <span>Total Items: <?php echo $result->num_rows; ?></span>
                <span>Security Level: Tier-1 Admin Verified</span>
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