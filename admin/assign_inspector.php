<?php
// File: admin/assign_inspector.php
session_start();
require_once '../config/db.php';
require_once('../includes/admin_auth.php');

$filter_bid = $_GET['f_bid'] ?? '';
$filter_venue = $_GET['f_venue'] ?? '';

// 🔍 查詢：僅顯示已批准且尚未指派的訂單
$sql = "SELECT b.bid, b.date_booked, u.username, v.vname, b.payment_status
        FROM booking b
        JOIN user u ON b.uid = u.uid
        JOIN venue v ON b.vid = v.vid
        LEFT JOIN inspection i ON b.bid = i.bid
        WHERE b.status IN ('approved', 'completed') 
          AND b.payment_status = 'paid'
          AND i.ins_id IS NULL";

if ($filter_bid) $sql .= " AND b.bid LIKE '%" . $conn->real_escape_string($filter_bid) . "%'";
if ($filter_venue) $sql .= " AND v.vname LIKE '%" . $conn->real_escape_string($filter_venue) . "%'";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Assign Inspector</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b', accent: '#38bdf8' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
        
        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0">
            <?php 
            $topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Operations / Assign Inspector</h2>';
            include('../includes/admin_topbar.php'); 
            ?>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="mb-6">
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Assign Inspector</h1>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-6">
                <form method="GET" class="flex flex-wrap md:flex-nowrap gap-4 items-end">
                    <div class="w-full md:w-1/3">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Booking ID</label>
                        <input type="text" name="f_bid" value="<?php echo htmlspecialchars($filter_bid); ?>" placeholder="Search ID..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all">
                    </div>
                    <div class="w-full md:w-1/3">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Venue Name</label>
                        <input type="text" name="f_venue" value="<?php echo htmlspecialchars($filter_venue); ?>" placeholder="Search Venue..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all">
                    </div>
                    <div class="w-full md:w-auto flex space-x-2">
                        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition shadow-sm">Go</button>
                        <a href="assign_inspector.php" class="px-5 py-2 bg-slate-100 text-slate-600 text-sm font-bold rounded-lg hover:bg-slate-200 transition">Reset</a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="text-[11px] font-black text-slate-500 uppercase tracking-widest">Worklist (<?php echo $result->num_rows; ?>)</h3>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white text-slate-400 text-[10px] font-black uppercase tracking-widest border-b border-slate-100">
                            <th class="px-6 py-3">Booking ID</th>
                            <th class="px-6 py-3">Student</th>
                            <th class="px-6 py-3">Venue</th>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-50">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-indigo-50/50 cursor-pointer transition-colors group" onclick="window.location.href='assign_inspector_detail.php?bid=<?php echo urlencode($row['bid']); ?>'">
                                <td class="px-6 py-4 font-mono font-bold text-indigo-600"><?php echo htmlspecialchars($row['bid']); ?></td>
                                <td class="px-6 py-4 font-bold text-slate-700"><?php echo htmlspecialchars($row['username']); ?></td>
                                <td class="px-6 py-4 text-slate-500"><?php echo htmlspecialchars($row['vname']); ?></td>
                                <td class="px-6 py-4 font-medium text-slate-600"><?php echo htmlspecialchars($row['date_booked']); ?></td>
                                <td class="px-6 py-4 text-right text-slate-300 group-hover:text-indigo-600 transition-colors">
                                    <i data-lucide="chevron-right" class="w-5 h-5 ml-auto"></i>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium">No items found matching the criteria.</td>
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
        function toggleSidebar() {
            document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed');
        }
    </script>
</body>
</html>