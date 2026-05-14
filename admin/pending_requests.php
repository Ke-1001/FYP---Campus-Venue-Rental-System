<?php
// File: admin/pending_requests.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

// 💡 1. 接收多維度過濾參數
$filter_bid = trim($_GET['f_bid'] ?? '');
$filter_student = trim($_GET['f_student'] ?? '');
$filter_venue = trim($_GET['f_venue'] ?? '');
$filter_date = trim($_GET['f_date'] ?? '');

// 💡 2. 構建聚合查詢 (嚴格對齊 rental_venue (4).sql)
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
            vc.category AS venue_category, 
            v.deposit
        FROM booking b
        JOIN user u ON b.uid = u.uid
        JOIN venue v ON b.vid = v.vid
        JOIN vcategory vc ON v.vcid = vc.vcid 
        WHERE b.status = 'pending' AND b.payment_status = 'paid'";

// 💡 3. 動態注入過濾條件
if (!empty($filter_bid)) {
    $sql .= " AND b.bid LIKE '%" . $conn->real_escape_string($filter_bid) . "%'";
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
        tailwind.config = { theme: { extend: { colors: { cstyle: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori_forms.css">
</head>
<body class="bg-[#f4f4f4] text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
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
                <p class="text-sm text-slate-600 mt-1">Review full details below and execute booking decisions.</p>
            </div>

            <div class="bg-white p-5 rounded-lg shadow-sm border border-slate-200 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
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
                        <input type="text" name="f_venue" value="<?php echo htmlspecialchars($filter_venue); ?>" placeholder="Name or Category..." class="fiori-input w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Date</label>
                        <input type="date" name="f_date" value="<?php echo htmlspecialchars($filter_date); ?>" class="fiori-input w-full">
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" class="w-full py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition shadow-sm">
                            Apply Filter
                        </button>
                        <a href="pending_requests.php" class="px-4 py-2 bg-slate-100 text-slate-600 text-sm font-bold rounded-lg hover:bg-slate-200 transition flex items-center justify-center" title="Reset">
                            <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
                    <h3 class="text-xs font-black text-slate-600 uppercase tracking-widest">Pending Requests (<?php echo $result->num_rows; ?>)</h3>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white text-xs text-slate-500 font-black uppercase tracking-widest border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 w-32">Reference</th>
                            <th class="px-6 py-4 w-48">Student Profile</th>
                            <th class="px-6 py-4 w-56">Venue & Time</th>
                            <th class="px-6 py-4">Deposit & Full Purpose</th>
                            <th class="px-6 py-4 text-center w-36">Decision</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-100">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $time_range = date('H:i', strtotime($row['time_start'])) . ' - ' . date('H:i', strtotime($row['time_end']));
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors align-top">
                                <td class="px-6 py-5">
                                    <a href="process_flow.php?bid=<?php echo urlencode($row['bid']); ?>" class="font-mono text-sm font-extrabold text-[#0a6ed1] hover:text-[#085caf] hover:underline block mb-2 transition-colors">
                                        <?php echo htmlspecialchars($row['bid']); ?>
                                    </a>
                                    <span class="text-[10px] text-slate-500 font-mono uppercase font-bold block text-center">Req: <?php echo date('M d', strtotime($row['created_at'])); ?></span>
                                    <span class="text-[10px] text-slate-500 font-mono uppercase font-bold block text-center"><?php echo date('H:i', strtotime($row['created_at'])); ?></span>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="font-extrabold text-slate-800 text-base block mb-1"><?php echo htmlspecialchars($row['student_name']); ?></span>
                                    <span class="text-xs font-mono font-bold text-slate-600 block mb-2"><?php echo htmlspecialchars($row['student_id']); ?></span>
                                    <div class="text-xs text-slate-600 font-medium space-y-1">
                                        <p><?php echo htmlspecialchars($row['phone_num']); ?></p>
                                        <!-- 核心修改点：将 'email' 替换为 PHP 输出变量 -->
                                        <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="text-indigo-600 hover:underline">
                                            <?php echo htmlspecialchars($row['email']); ?>
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="font-extrabold text-slate-800 text-base block mb-1"><?php echo htmlspecialchars($row['venue_name']); ?></span>
                                    <span class="px-2 py-1 bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-wider rounded border border-slate-200 inline-block mb-3"><?php echo htmlspecialchars($row['venue_category']); ?></span>
                                    
                                    <div class="bg-blue-50/50 border border-blue-100 p-2 rounded text-xs font-mono font-bold text-blue-800">
                                        <span class="block mb-1 text-slate-500 font-sans text-[10px] uppercase">Reserved Slot:</span>
                                        <?php echo htmlspecialchars($row['date_booked']); ?><br>
                                        <?php echo $time_range; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="inline-block px-2 py-1 bg-emerald-50 border border-emerald-200 font-mono text-xs font-extrabold text-emerald-700 rounded mb-3">Deposit: RM <?php echo number_format($row['deposit'], 2); ?></span>
                                    <div class="text-sm text-slate-700 font-medium leading-relaxed whitespace-normal bg-slate-50 p-3 border border-slate-200 rounded-lg">
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Declared Purpose:</span>
                                        <?php echo nl2br(htmlspecialchars($row['purpose'])); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex flex-col space-y-2">
                                        <button onclick="triggerDecisionModal('<?php echo $row['bid']; ?>', 'approve')" class="w-full px-3 py-2 bg-white text-emerald-600 border-2 border-emerald-200 hover:bg-emerald-600 hover:border-emerald-600 hover:text-white rounded-lg text-xs font-bold transition-colors shadow-sm text-center">
                                            Approve
                                        </button>
                                        <button onclick="triggerDecisionModal('<?php echo $row['bid']; ?>', 'reject')" class="w-full px-3 py-2 bg-white text-red-600 border-2 border-red-200 hover:bg-red-600 hover:border-red-600 hover:text-white rounded-lg text-xs font-bold transition-colors shadow-sm text-center">
                                            Reject
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center text-slate-500 font-medium">
                                    <span class="block text-4xl mb-3">📭</span>
                                    No pending requests match the criteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="decision-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center transition-opacity opacity-0">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform scale-95 transition-transform" id="decision-panel">
            
            <form action="../actions/process_booking_action.php" method="POST" id="decision-form">
                <input type="hidden" name="booking_id" id="modal-bid" value="">
                <input type="hidden" name="action_type" id="modal-action-type" value="">
                
                <div class="p-6 text-center">
                    
                    <h3 class="text-xl font-extrabold text-slate-800 mb-2" id="modal-title">Confirm Action</h3>
                    <p class="text-sm font-bold text-indigo-600 bg-indigo-50 inline-block px-3 py-1 rounded-md border border-indigo-100 font-mono mb-4" id="modal-bid-display"></p>
                    <p class="text-sm text-slate-600 font-medium leading-relaxed" id="modal-msg"></p>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end space-x-3">
                    <button type="button" onclick="closeDecisionModal()" class="px-5 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-200 rounded-lg transition-colors">Cancel</button>
                    <button type="submit" id="modal-confirm-btn" onclick="this.innerHTML='Processing...'; this.classList.add('opacity-70', 'cursor-not-allowed');" class="px-5 py-2.5 text-sm font-bold text-white rounded-lg shadow-sm transition-colors">
                        Confirm
                    </button>
                </div>
            </form>

        </div>
    </div>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();
        function toggleSidebar() { document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed'); }

        // 💡 決策視窗動態引擎
        function triggerDecisionModal(bid, actionType) {
            document.getElementById('modal-bid').value = bid;
            document.getElementById('modal-bid-display').innerText = bid;
            document.getElementById('modal-action-type').value = actionType;
            
            const iconContainer = document.getElementById('modal-icon-container');
            const iconSpan = document.getElementById('modal-icon');
            const titleEl = document.getElementById('modal-title');
            const msgEl = document.getElementById('modal-msg');
            const btnEl = document.getElementById('modal-confirm-btn');

            if (actionType === 'approve') {
                titleEl.innerText = 'Approve Booking';
                msgEl.innerText = 'Are you sure you want to approve this request? The venue slot will be permanently locked for this student.';
                btnEl.className = 'px-5 py-2.5 text-sm font-bold text-white rounded-lg shadow-sm transition-colors bg-emerald-600 hover:bg-emerald-700';
                btnEl.innerText = 'Confirm Approval';
            } else if (actionType === 'reject') {
                titleEl.innerText = 'Reject Booking';
                msgEl.innerText = 'Are you sure you want to reject this request? The reservation will be cancelled and the deposit will be flagged for refund.';
                btnEl.className = 'px-5 py-2.5 text-sm font-bold text-white rounded-lg shadow-sm transition-colors bg-red-600 hover:bg-red-700';
                btnEl.innerText = 'Confirm Rejection';
            }

            lucide.createIcons();

            // 啟動動畫
            const modal = document.getElementById('decision-modal');
            const panel = document.getElementById('decision-panel');
            modal.classList.remove('hidden');
            setTimeout(() => { 
                modal.classList.remove('opacity-0'); 
                panel.classList.remove('scale-95'); 
            }, 10);
        }

        function closeDecisionModal() { 
            const modal = document.getElementById('decision-modal');
            const panel = document.getElementById('decision-panel');
            modal.classList.add('opacity-0'); 
            panel.classList.add('scale-95');
            setTimeout(() => { modal.classList.add('hidden'); }, 200);
        }
    </script>
</body>
</html>