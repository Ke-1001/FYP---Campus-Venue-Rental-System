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
    <link rel="stylesheet" href="../assets/css/layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori_forms.css">
    <link rel="stylesheet" href="../assets/css/table.css"> </head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">

            <?php 
            $topbar_content = '<div class="flex items-center">
                <a href="manage_bookings.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
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

            <div class="bg-white p-5 rounded-md border border-slate-200 shadow-[0_1px_2px_0_rgba(0,0,0,0.05)] hover:border-slate-400 transition-colors mb-6">
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
                        <button type="submit" class="w-full py-2 bg-[#004aad] text-white text-sm font-semibold rounded-md hover:bg-[#003882] transition shadow-sm border border-[#004aad]">
                            Apply Filter
                        </button>
                        <a href="pending_requests.php" class="px-4 py-2 bg-transparent text-slate-600 text-sm font-semibold rounded-md hover:bg-slate-100 border border-transparent transition flex items-center justify-center" title="Reset">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="custom-table-container">
                <div class="px-4 py-3 border-b border-slate-200 bg-[#f8fafc] flex justify-between items-center">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest">Pending Requests (<?php echo $result->num_rows; ?>)</h3>
                </div>
                
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th class="w-32">Reference</th>
                            <th class="w-48">Student Profile</th>
                            <th class="w-56">Venue & Time</th>
                            <th>Deposit & Purpose</th>
                            <th class="text-center w-36">Decision</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $time_range = date('H:i', strtotime($row['time_start'])) . ' - ' . date('H:i', strtotime($row['time_end']));
                            ?>
                            <tr>
                                <td>
                                    <a href="process_flow.php?bid=<?php echo urlencode($row['bid']); ?>" class="td-text-mono mb-1">
                                        <?php echo htmlspecialchars($row['bid']); ?>
                                    </a>
                                    <span class="text-[10px] text-slate-500 font-mono uppercase font-bold block">Req: <?php echo date('M d', strtotime($row['created_at'])); ?></span>
                                    <span class="text-[10px] text-slate-500 font-mono uppercase font-bold block"><?php echo date('H:i', strtotime($row['created_at'])); ?></span>
                                </td>
                                <td>
                                    <span class="font-semibold text-slate-800 text-sm block mb-1"><?php echo htmlspecialchars($row['student_name']); ?></span>
                                    <span class="td-text-mono block text-slate-600 mb-2"><?php echo htmlspecialchars($row['student_id']); ?></span>
                                    <div class="text-xs text-slate-600 font-mono space-y-1">
                                        <p><?php echo htmlspecialchars($row['phone_num']); ?></p>
                                        <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="text-[#004aad] hover:underline">
                                            <?php echo htmlspecialchars($row['email']); ?>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <span class="font-semibold text-slate-800 text-sm block mb-1"><?php echo htmlspecialchars($row['venue_name']); ?></span>
                                    <span class="px-2 py-0.5 bg-[#f1f5f9] text-slate-600 text-[10px] font-bold uppercase tracking-wider rounded-sm border border-[#e2e8f0] inline-block mb-3"><?php echo htmlspecialchars($row['venue_category']); ?></span>
                                    
                                    <div class="td-text-mono text-slate-700 bg-slate-50 border border-slate-200 p-2 rounded-md">
                                        <span class="block mb-1 text-slate-400 font-sans text-[10px] uppercase font-bold tracking-widest">Reserved Slot:</span>
                                        <?php echo htmlspecialchars($row['date_booked']); ?><br>
                                        <?php echo $time_range; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="inline-block px-2 py-0.5 bg-[#ecfdf5] border border-[#a7f3d0] font-mono text-xs font-bold text-[#059669] rounded-sm mb-2">Deposit: RM <?php echo number_format($row['deposit'], 2); ?></span>
                                    <div class="text-xs text-slate-700 font-medium leading-relaxed whitespace-normal">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Declared Purpose:</span>
                                        <?php echo nl2br(htmlspecialchars($row['purpose'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex flex-col space-y-2 px-2">
                                        <button onclick="triggerDecisionModal('<?php echo $row['bid']; ?>', 'approve')" class="w-full px-3 py-1.5 bg-white text-[#059669] border border-[#059669] hover:bg-[#059669] hover:text-white rounded-md text-xs font-semibold transition-colors shadow-sm">
                                            Approve
                                        </button>
                                        <button onclick="triggerDecisionModal('<?php echo $row['bid']; ?>', 'reject')" class="w-full px-3 py-1.5 bg-white text-[#dc2626] border border-[#dc2626] hover:bg-[#dc2626] hover:text-white rounded-md text-xs font-semibold transition-colors shadow-sm">
                                            Reject
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="py-16 text-center text-slate-500 border-none hover:bg-transparent cursor-default">
                                    <span class="block text-3xl mb-3">📭</span>
                                    <span class="text-sm font-medium">No pending requests match the criteria.</span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="decision-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center transition-opacity opacity-0">
        <div class="bg-white rounded-md shadow-lg border border-slate-200 w-full max-w-sm overflow-hidden transform scale-95 transition-transform" id="decision-panel">
            
            <form action="../actions/process_booking_action.php" method="POST" id="decision-form">
                <input type="hidden" name="booking_id" id="modal-bid" value="">
                <input type="hidden" name="action_type" id="modal-action-type" value="">
                
                <div class="p-6 text-center">
                    <h3 class="text-lg font-bold text-slate-800 mb-2" id="modal-title">Confirm Action</h3>
                    <p class="text-sm font-bold text-[#004aad] bg-[#f1f5f9] inline-block px-3 py-1 rounded-sm border border-slate-200 font-mono mb-4" id="modal-bid-display"></p>
                    <p class="text-sm text-slate-600 font-medium leading-relaxed" id="modal-msg"></p>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end space-x-3">
                    <button type="button" onclick="closeDecisionModal()" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-transparent hover:bg-slate-200 border border-transparent rounded-md transition-colors">Cancel</button>
                    <button type="submit" id="modal-confirm-btn" onclick="this.innerHTML='Processing...'; this.classList.add('opacity-70', 'cursor-not-allowed');" class="px-4 py-2 text-sm font-semibold text-white rounded-md shadow-sm transition-colors border">
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

        function triggerDecisionModal(bid, actionType) {
            document.getElementById('modal-bid').value = bid;
            document.getElementById('modal-bid-display').innerText = bid;
            document.getElementById('modal-action-type').value = actionType;
            
            const titleEl = document.getElementById('modal-title');
            const msgEl = document.getElementById('modal-msg');
            const btnEl = document.getElementById('modal-confirm-btn');

            // ∴ 与 Fiori 的 Status tag 色值保持一致 (emerald-600 vs red-600)
            if (actionType === 'approve') {
                titleEl.innerText = 'Approve Booking';
                msgEl.innerText = 'Are you sure you want to approve this request? The venue slot will be locked.';
                btnEl.className = 'px-4 py-2 text-sm font-semibold text-white rounded-md shadow-sm transition-colors bg-[#059669] hover:bg-[#047857] border border-[#059669]';
                btnEl.innerText = 'Confirm Approval';
            } else if (actionType === 'reject') {
                titleEl.innerText = 'Reject Booking';
                msgEl.innerText = 'Are you sure you want to reject this request? The deposit will be flagged for refund.';
                btnEl.className = 'px-4 py-2 text-sm font-semibold text-white rounded-md shadow-sm transition-colors bg-[#dc2626] hover:bg-[#b91c1c] border border-[#dc2626]';
                btnEl.innerText = 'Confirm Rejection';
            }

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