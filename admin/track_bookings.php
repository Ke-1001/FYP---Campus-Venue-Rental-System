<?php
// File: admin/track_bookings.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

// 💡 1. 提取 Filter 參數
$filter_bid = $_GET['f_bid'] ?? '';
$filter_status = $_GET['f_status'] ?? '';

// 💡 2. 構建動態查詢 (Dynamic Query Construction)
$sql = "SELECT b.bid, b.date_booked, b.status, b.payment_status, u.username, v.vname, b.created_at
        FROM booking b
        JOIN user u ON b.uid = u.uid
        JOIN venue v ON b.vid = v.vid
        WHERE 1=1";

if (!empty($filter_bid)) {
    $sql .= " AND b.bid LIKE '%" . $conn->real_escape_string($filter_bid) . "%'";
}
if (!empty($filter_status)) {
    $sql .= " AND b.status = '" . $conn->real_escape_string($filter_status) . "'";
}

$sql .= " ORDER BY b.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Track Bookings</title>
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
            $topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Reporting / Track Bookings</h2>';
            include('../includes/admin_topbar.php'); 
            ?>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 mb-6">
                <form method="GET" class="flex flex-wrap md:flex-nowrap gap-6 items-end">
                    <div class="w-full md:w-1/3">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2">Booking ID</label>
                        <input type="text" name="f_bid" value="<?php echo htmlspecialchars($filter_bid); ?>" placeholder="Search ID..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all">
                    </div>
                    <div class="w-full md:w-1/3">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2">Status</label>
                        <select name="f_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none bg-white transition-all">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php if($filter_status==='pending') echo 'selected'; ?>>Pending</option>
                            <option value="approved" <?php if($filter_status==='approved') echo 'selected'; ?>>Approved</option>
                            <option value="rejected" <?php if($filter_status==='rejected') echo 'selected'; ?>>Rejected</option>
                            <option value="completed" <?php if($filter_status==='completed') echo 'selected'; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="w-full md:w-auto flex space-x-2">
                        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition shadow-sm">Go</button>
                        <a href="track_bookings.php" class="px-5 py-2 bg-slate-100 text-slate-600 text-sm font-bold rounded-lg hover:bg-slate-200 transition">Reset</a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                    <h3 class="text-[11px] font-black text-slate-500 uppercase tracking-widest">Booking History (<?php echo $result->num_rows; ?>)</h3>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white text-[10px] text-slate-400 font-black uppercase tracking-widest border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3">ID</th>
                            <th class="px-6 py-3">Student & Venue</th>
                            <th class="px-6 py-3">Schedule</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Flow</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-50">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-indigo-50/50 cursor-pointer transition-colors group" onclick="window.location.href='process_flow.php?bid=<?php echo urlencode($row['bid']); ?>'">
                                <td class="px-6 py-4 font-mono font-bold text-indigo-600"><?php echo $row['bid']; ?></td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-700 block"><?php echo htmlspecialchars($row['username']); ?></span>
                                    <span class="text-xs text-slate-400"><?php echo htmlspecialchars($row['vname']); ?></span>
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-600"><?php echo $row['date_booked']; ?></td>
                                <td class="px-6 py-4">
                                    <?php 
                                    $c = match($row['status']) {
                                        'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'approved' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'rejected' => 'bg-red-50 text-red-700 border-red-200',
                                        default => 'bg-slate-100'
                                    };
                                    ?>
                                    <span class="px-2 py-1 border <?php echo $c; ?> rounded text-[10px] font-black uppercase tracking-wider">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-slate-300 group-hover:text-indigo-600 transition-colors">
                                    <i data-lucide="chevron-right" class="w-5 h-5 ml-auto"></i>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium">No records found matching criteria.</td></tr>
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