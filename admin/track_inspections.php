<?php
// File: admin/track_inspections.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

$filter_bid = $_GET['f_bid'] ?? '';
$filter_res = $_GET['f_res'] ?? '';

// 💡 2. 查詢歷史檢驗紀錄 (Completed only)
$sql = "SELECT 
            i.*, b.date_booked, u.username, v.vname, s.staff_name AS inspector_name
        FROM inspection i
        JOIN booking b ON i.bid = b.bid
        JOIN user u ON b.uid = u.uid
        JOIN venue v ON b.vid = v.vid
        JOIN staff s ON i.sid = s.sid
        WHERE i.ins_status IN ('passed', 'failed')";

if (!empty($filter_bid)) $sql .= " AND i.bid LIKE '%" . $conn->real_escape_string($filter_bid) . "%'";
if (!empty($filter_res)) $sql .= " AND i.ins_status = '" . $conn->real_escape_string($filter_res) . "'";

$sql .= " ORDER BY i.ins_id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Admin | Track Inspections</title>
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
                <a href="inspections.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Reporting / Inspection History</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="mb-6">
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Inspection History</h1>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-6">
                <form method="GET" class="flex flex-wrap md:flex-nowrap gap-4 items-end">
                    <div class="w-full md:w-1/3">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Booking ID</label>
                        <input type="text" name="f_bid" value="<?php echo htmlspecialchars($filter_bid); ?>" placeholder="Search BID..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none">
                    </div>
                    <div class="w-full md:w-1/3">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Result</label>
                        <select name="f_res" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none bg-white">
                            <option value="">All Results</option>
                            <option value="passed" <?php if($filter_res==='passed') echo 'selected'; ?>>Passed</option>
                            <option value="failed" <?php if($filter_res==='failed') echo 'selected'; ?>>Failed</option>
                        </select>
                    </div>
                    <div class="w-full md:w-auto flex space-x-2">
                        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition">Go</button>
                        <a href="track_inspections.php" class="px-5 py-2 bg-slate-100 text-slate-600 text-sm font-bold rounded-lg hover:bg-slate-200 transition">Reset</a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-400 text-[10px] font-black uppercase tracking-widest border-b border-slate-100">
                            <th class="px-6 py-4">BID</th>
                            <th class="px-6 py-4">Assessment Result</th>
                            <th class="px-6 py-4">Inspector</th>
                            <th class="px-6 py-4">Penalty Info</th>
                            <th class="px-6 py-4 text-right">View Flow</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-50">
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-indigo-50/30 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-indigo-600"><?php echo $row['bid']; ?></td>
                            <td class="px-6 py-4">
                                <?php 
                                $c = ($row['ins_status'] === 'passed') ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200';
                                ?>
                                <span class="px-2 py-1 border <?php echo $c; ?> rounded text-[10px] font-black uppercase"><?php echo $row['ins_status']; ?></span>
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-600"><?php echo htmlspecialchars($row['inspector_name']); ?></td>
                            <td class="px-6 py-4">
                                <?php if($row['ins_status'] === 'failed'): ?>
                                    <span class="font-mono font-bold text-red-600">- RM <?php echo number_format($row['penalty'], 2); ?></span>
                                <?php else: ?>
                                    <span class="text-slate-300">--</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="process_flow.php?bid=<?php echo $row['bid']; ?>" class="text-indigo-400 hover:text-indigo-700 transition"><i data-lucide="external-link" class="w-4 h-4 ml-auto"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
        function toggleSidebar() { document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed'); }
    </script>
</body>
</html>