<?php
// File: admin/pending_requests.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

// 💡 1. 接收多維度過濾參數 (Multi-dimensional Filter Parameters)
$filter_bid = trim($_GET['f_bid'] ?? '');
$filter_student = trim($_GET['f_student'] ?? '');
$filter_venue = trim($_GET['f_venue'] ?? '');
$filter_date = trim($_GET['f_date'] ?? '');

// 💡 2. 構建聚合查詢 (Aggregated Query via JOINs)
$sql = "SELECT 
            b.bid, 
            b.date_booked, 
            b.time_start,
            b.time_end,
            b.purpose, 
            b.created_at,
            u.uid AS student_id,
            u.username AS student_name, 
            u.phone_num,
            u.email,
            v.vname AS venue_name, 
            v.category AS venue_category,
            v.deposit
        FROM booking b
        JOIN user u ON b.uid = u.uid
        JOIN venue v ON b.vid = v.vid
        WHERE b.status = 'pending' AND b.payment_status = 'paid'";

// 💡 3. 動態注入過濾條件 (Dynamic WHERE Clauses)
if (!empty($filter_bid)) {
    $sql .= " AND b.bid LIKE '%" . $conn->real_escape_string($filter_bid) . "%'";
}
if (!empty($filter_student)) {
    // 聯集匹配：姓名或學號
    $sql .= " AND (u.username LIKE '%" . $conn->real_escape_string($filter_student) . "%' OR u.uid LIKE '%" . $conn->real_escape_string($filter_student) . "%')";
}
if (!empty($filter_venue)) {
    // 聯集匹配：場地名稱或類別
    $sql .= " AND (v.vname LIKE '%" . $conn->real_escape_string($filter_venue) . "%' OR v.category LIKE '%" . $conn->real_escape_string($filter_venue) . "%')";
}
if (!empty($filter_date)) {
    // 精確匹配：日期
    $sql .= " AND b.date_booked = '" . $conn->real_escape_string($filter_date) . "'";
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
        

            <?php 
            $topbar_content = '<div class="flex items-center">
                <a href="manage_bookings.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Bookings / Pending Requests</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>


        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="mb-6">
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Pending Requests</h1>
                <p class="text-xs text-slate-500 mt-1">Review and process new venue booking requests.</p>
            </div>

            <!-- 💡 多維度過濾矩陣 (Multi-dimensional Filter Matrix) -->
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Ref ID</label>
                        <input type="text" name="f_bid" value="<?php echo htmlspecialchars($filter_bid); ?>" placeholder="Search BID..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Student Entity</label>
                        <input type="text" name="f_student" value="<?php echo htmlspecialchars($filter_student); ?>" placeholder="Name or Student ID..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Asset</label>
                        <input type="text" name="f_venue" value="<?php echo htmlspecialchars($filter_venue); ?>" placeholder="Venue Name or Category..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Date</label>
                        <input type="date" name="f_date" value="<?php echo htmlspecialchars($filter_date); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none text-slate-600">
                    </div>

                    <div class="flex space-x-2">
                        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition shadow-sm flex items-center justify-center">
                            <i data-lucide="filter" class="w-4 h-4 mr-1"></i> Apply
                        </button>
                        <a href="pending_requests.php" class="px-4 py-2 bg-slate-100 text-slate-600 text-sm font-bold rounded-lg hover:bg-slate-200 transition flex items-center justify-center" title="Reset Filters">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        </a>
                    </div>

                </form>
            </div>

            <!-- 💡 擴展資料表格 -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="text-[11px] font-black text-slate-500 uppercase tracking-widest">Pending Requests (<?php echo $result->num_rows; ?>)</h3>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white text-[10px] text-slate-400 font-black uppercase tracking-widest border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3">Reference</th>
                            <th class="px-6 py-3">Student Context</th>
                            <th class="px-6 py-3">Asset & Time</th>
                            <th class="px-6 py-3">Deposit & Purpose</th>
                            <th class="px-6 py-3 text-right">Execution</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-50">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $time_range = date('H:i', strtotime($row['time_start'])) . ' - ' . date('H:i', strtotime($row['time_end']));
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-mono text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded border border-indigo-100"><?php echo htmlspecialchars($row['bid']); ?></span>
                                    <p class="text-[9px] text-slate-400 mt-2 font-mono uppercase">Req: <?php echo date('M d, H:i', strtotime($row['created_at'])); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-800 block"><?php echo htmlspecialchars($row['student_name']); ?></span>
                                    <span class="text-[10px] font-mono text-slate-500 block"><?php echo htmlspecialchars($row['student_id']); ?></span>
                                    <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="text-[10px] text-indigo-500 hover:underline mt-1 inline-block"><i data-lucide="mail" class="w-3 h-3 inline pb-0.5"></i> Contact</a>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center mb-1">
                                        <span class="font-bold text-slate-700 mr-2"><?php echo htmlspecialchars($row['venue_name']); ?></span>
                                        <span class="px-1.5 py-0.5 bg-slate-100 text-slate-500 text-[9px] font-bold uppercase tracking-wider rounded border border-slate-200"><?php echo htmlspecialchars(strtoupper($row['venue_category'])); ?></span>
                                    </div>
                                    <span class="text-xs text-slate-500 font-mono"><i data-lucide="calendar" class="w-3 h-3 inline pb-0.5"></i> <?php echo htmlspecialchars($row['date_booked']); ?> | <?php echo $time_range; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="block font-mono text-xs font-bold text-emerald-600 mb-1">Dep: RM <?php echo number_format($row['deposit'], 2); ?></span>
                                    <p class="text-xs text-slate-600 italic max-w-[180px] truncate" title="<?php echo htmlspecialchars($row['purpose']); ?>">"<?php echo htmlspecialchars($row['purpose']); ?>"</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <!-- 💡 傳遞完整的多維度資料至 Modal -->
                                    <button onclick='openActionModal(<?php echo json_encode([
                                        "bid" => $row['bid'],
                                        "student" => $row['student_name'] . " (" . $row['student_id'] . ")",
                                        "contact" => $row['phone_num'] . " | " . $row['email'],
                                        "venue" => $row['venue_name'] . " [" . strtoupper($row['venue_category']) . "]",
                                        "date" => $row['date_booked'],
                                        "time" => $time_range,
                                        "deposit" => "RM " . number_format($row['deposit'], 2),
                                        "purpose" => $row['purpose']
                                    ]); ?>)' class="px-4 py-2 text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-600 hover:text-white border border-indigo-200 rounded-lg transition-all shadow-sm">
                                        Review Request
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500 font-medium">
                                    <i data-lucide="search-x" class="w-12 h-12 mx-auto text-slate-300 mb-3"></i>
                                    No pending requests match criteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- 💡 擴展的 Action Modal -->
    <div id="action-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-200 transform scale-100 transition-transform">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-widest flex items-center">
                    <i data-lucide="shield-alert" class="w-4 h-4 mr-2 text-indigo-600"></i> Execute Booking Request
                </h3>
                <button onclick="closeActionModal()" class="text-slate-400 hover:text-slate-600 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <form action="../actions/process_booking_action.php" method="POST">
                <input type="hidden" name="booking_id" id="modal-bid" value="">
                
                <div class="p-6">
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 space-y-3 mb-6 text-sm">
                        
                        <div class="flex justify-between border-b border-slate-200 pb-2">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-[10px]">Booking ID</span>
                            <span id="modal-bid-display" class="font-mono font-bold text-indigo-600"></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-[10px]">Student Entity</span>
                            <span id="modal-student" class="font-bold text-slate-800"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-[10px]">Contact</span>
                            <span id="modal-contact" class="font-mono text-xs text-slate-500"></span>
                        </div>

                        <div class="border-t border-slate-200 pt-2 mt-2"></div>

                        <div class="flex justify-between">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-[10px]">Venue</span>
                            <span id="modal-venue" class="font-bold text-slate-800"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-[10px]">Date & Time</span>
                            <span id="modal-datetime" class="font-mono text-slate-700"></span>
                        </div>
                        <div class="flex justify-between text-emerald-600">
                            <span class="font-bold uppercase tracking-wider text-[10px]">Deposit Cleared</span>
                            <span id="modal-deposit" class="font-mono font-bold text-xs"></span>
                        </div>

                        <div class="border-t border-slate-200 pt-3 mt-2">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-[10px] block mb-1">Purpose</span>
                            <span id="modal-purpose" class="text-slate-700 italic text-xs leading-relaxed"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Execute Decision</label>
                        <select name="action_type" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-white font-bold text-slate-700 transition-all">
                            <option value="" disabled selected>-- Select Action --</option>
                            <option value="approve" class="text-emerald-600">Approve Booking</option>
                            <option value="reject" class="text-red-600">Reject & Issue Refund</option>
                        </select>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end space-x-3">
                    <button type="button" onclick="closeActionModal()" class="px-5 py-2 text-sm font-bold text-slate-500 hover:bg-slate-200 rounded-lg transition">Cancel</button>
                    <button type="submit" class="px-5 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition transform active:scale-95">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();

        function toggleSidebar() { document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed'); }

        function openActionModal(data) {
            document.getElementById('modal-bid').value = data.bid;
            document.getElementById('modal-bid-display').innerText = data.bid;
            document.getElementById('modal-student').innerText = data.student;
            document.getElementById('modal-contact').innerText = data.contact;
            document.getElementById('modal-venue').innerText = data.venue;
            document.getElementById('modal-datetime').innerText = data.date + ' | ' + data.time;
            document.getElementById('modal-deposit').innerText = data.deposit;
            document.getElementById('modal-purpose').innerText = '"' + data.purpose + '"';
            
            document.getElementById('action-modal').classList.remove('hidden');
        }

        function closeActionModal() { document.getElementById('action-modal').classList.add('hidden'); }
    </script>
</body>
</html>