<?php
// File path: admin/pending_requests.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

// 💡 1. 引入 Filter 參數 (SAP Fiori 標準)
$filter_bid = $_GET['f_bid'] ?? '';
$filter_venue = $_GET['f_venue'] ?? '';

// 💡 2. 動態構建查詢 (Dynamic Query Construction)
$sql = "SELECT 
            b.bid, 
            b.date_booked, 
            b.time_start,
            b.duration,
            b.purpose, 
            b.created_at,
            u.uid AS student_id,
            u.username AS student_name, 
            u.phone_num,
            v.vname AS venue_name, 
            v.deposit
        FROM booking b
        JOIN user u ON b.uid = u.uid
        JOIN venue v ON b.vid = v.vid
        WHERE b.status = 'pending' AND b.payment_status = 'paid'";

if (!empty($filter_bid)) {
    $sql .= " AND b.bid LIKE '%" . $conn->real_escape_string($filter_bid) . "%'";
}
if (!empty($filter_venue)) {
    $sql .= " AND v.vname LIKE '%" . $conn->real_escape_string($filter_venue) . "%'";
}

$sql .= " ORDER BY b.created_at ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Approval Queue</title>
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
            $topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Bookings / Approval Queue</h2>';
            include('../includes/admin_topbar.php'); 
            ?>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="mb-6 flex justify-between items-end">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Approval Queue</h1>
                    <p class="text-xs text-slate-500 mt-1">Review and process new venue booking requests from students.</p>
                </div>
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
                        <a href="pending_requests.php" class="px-5 py-2 bg-slate-100 text-slate-600 text-sm font-bold rounded-lg hover:bg-slate-200 transition">Reset</a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="text-[11px] font-black text-slate-500 uppercase tracking-widest">Action Queue (<?php echo $result->num_rows; ?>)</h3>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest"><i data-lucide="clock" class="w-3 h-3 inline pb-0.5"></i> Pending Authorization</span>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white text-slate-400 text-[10px] font-black uppercase tracking-widest border-b border-slate-100">
                            <th class="px-6 py-3">Booking ID</th>
                            <th class="px-6 py-3">Student Info</th>
                            <th class="px-6 py-3">Venue & Time</th>
                            <th class="px-6 py-3">Purpose</th>
                            <th class="px-6 py-3 text-right">Quick Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-50">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $start_dt = new DateTime($row['time_start']);
                                $end_dt = clone $start_dt;
                                $end_dt->modify("+{$row['duration']} minutes");
                                $time_range = $start_dt->format('H:i') . ' - ' . $end_dt->format('H:i');
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-mono text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded border border-indigo-100"><?php echo htmlspecialchars($row['bid']); ?></span>
                                    <p class="text-[9px] text-slate-400 mt-2 font-mono uppercase">Req: <?php echo date('M d, H:i', strtotime($row['created_at'])); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-800 block"><?php echo htmlspecialchars($row['student_name']); ?></span>
                                    <span class="text-[10px] font-mono text-slate-500"><?php echo htmlspecialchars($row['student_id']); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-700 block"><?php echo htmlspecialchars($row['venue_name']); ?></span>
                                    <span class="text-xs text-slate-500 font-mono"><?php echo htmlspecialchars($row['date_booked']); ?> | <?php echo $time_range; ?></span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 italic max-w-[200px] truncate">
                                    "<?php echo htmlspecialchars($row['purpose']); ?>"
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button onclick='openActionModal(<?php echo json_encode([
                                        "bid" => $row['bid'],
                                        "student" => $row['student_name'] . " (" . $row['student_id'] . ")",
                                        "venue" => $row['venue_name'],
                                        "date" => $row['date_booked'],
                                        "time" => $time_range,
                                        "purpose" => $row['purpose']
                                    ]); ?>)' class="px-4 py-2 text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-600 hover:text-white border border-indigo-200 rounded-lg transition-all shadow-sm">
                                        Review Request
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium">
                                    <i data-lucide="check-circle" class="w-12 h-12 mx-auto text-emerald-400 mb-3 opacity-50"></i>
                                    Queue is clear. No pending requests.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="action-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-200 transform scale-100 transition-transform">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-widest flex items-center">
                    <i data-lucide="shield-alert" class="w-4 h-4 mr-2 text-indigo-600"></i> Authorization Protocol
                </h3>
                <button onclick="closeActionModal()" class="text-slate-400 hover:text-slate-600 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <form action="../actions/process_booking_action.php" method="POST">
                <input type="hidden" name="booking_id" id="modal-bid" value="">
                
                <div class="p-6">
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 space-y-3 mb-6 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-[10px]">Booking ID</span>
                            <span id="modal-bid-display" class="font-mono font-bold text-indigo-600"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-[10px]">Student</span>
                            <span id="modal-student" class="font-bold text-slate-800"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-[10px]">Venue</span>
                            <span id="modal-venue" class="font-bold text-slate-800"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-[10px]">Date & Time</span>
                            <span id="modal-datetime" class="font-mono text-slate-700"></span>
                        </div>
                        <div class="border-t border-slate-200 pt-3 mt-2">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-[10px] block mb-1">Declared Purpose</span>
                            <span id="modal-purpose" class="text-slate-700 italic text-xs leading-relaxed"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Execute Decision</label>
                        <select name="action_type" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-white font-bold text-slate-700 transition-all">
                            <option value="" disabled selected>-- Select Authorization State --</option>
                            <option value="approve" class="text-emerald-600">Approve Booking</option>
                            <option value="reject" class="text-red-600">Reject & Issue Refund</option>
                        </select>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end space-x-3">
                    <button type="button" onclick="closeActionModal()" class="px-5 py-2 text-sm font-bold text-slate-500 hover:bg-slate-200 rounded-lg transition">Cancel</button>
                    <button type="submit" class="px-5 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition">Confirm Execution</button>
                </div>
            </form>
        </div>
    </div>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();

        function toggleSidebar() {
            document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed');
        }

        function openActionModal(data) {
            document.getElementById('modal-bid').value = data.bid;
            document.getElementById('modal-bid-display').innerText = data.bid;
            document.getElementById('modal-student').innerText = data.student;
            document.getElementById('modal-venue').innerText = data.venue;
            document.getElementById('modal-datetime').innerText = data.date + ' | ' + data.time;
            document.getElementById('modal-purpose').innerText = '"' + data.purpose + '"';
            
            document.getElementById('action-modal').classList.remove('hidden');
        }

        function closeActionModal() {
            document.getElementById('action-modal').classList.add('hidden');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>