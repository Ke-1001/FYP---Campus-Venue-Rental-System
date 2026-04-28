<?php
// File: admin/inspections.php
session_start();
require_once '../config/db.php';
require_once('../includes/admin_auth.php'); 

// 💡 1. 適配新架構：清道夫邏輯 (Sweep Logic)
// 將時間已過的 approved 轉為 completed
$sweep_sql = "
    UPDATE booking 
    SET status = 'completed' 
    WHERE status = 'approved' 
    AND CONCAT(date_booked, ' ', ADDTIME(time_start, SEC_TO_TIME(duration * 60))) <= NOW()
";
$conn->query($sweep_sql);

// 💡 2. 適配新架構：過濾出 pending inspections
// 條件：status = 'completed' 且 payment_status = 'paid' (尚未退款結算)
$sql = "SELECT 
            b.bid AS raw_id, 
            b.bid AS ref_id,
            b.date_booked AS booking_date, 
            CONCAT(DATE_FORMAT(b.time_start, '%H:%i'), ' - ', DATE_FORMAT(ADDTIME(b.time_start, SEC_TO_TIME(b.duration * 60)), '%H:%i')) AS time_slot, 
            b.status AS booking_status,
            u.username AS student_name, 
            v.vname AS venue_name, 
            v.deposit AS deposit_paid /* 💡 直接從 venue 表提取押金基數 */
        FROM booking b
        JOIN user u ON b.uid = u.uid
        JOIN venue v ON b.vid = v.vid
        WHERE b.status = 'completed' AND b.payment_status = 'paid'
        ORDER BY b.date_booked ASC, b.time_start ASC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Pending Inspections</title>
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
        
        <?php 
        $topbar_content = '
        <div class="flex items-center text-slate-500 bg-white px-4 py-2 rounded-lg border border-slate-200 focus-within:border-mmu-blue shadow-sm transition-all">
            <i data-lucide="search" class="w-4 h-4 mr-2"></i>
            <input type="text" placeholder="Search system assets..." class="bg-transparent border-none outline-none w-64 text-sm focus:ring-0">
        </div>';
        
        include('../includes/admin_topbar.php'); 
        ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            <div class="mb-8">
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Pending Inspections</h1>
                <p class="text-sm text-slate-500 mt-1">Execute post-usage venue assessments and settle financial deposits.</p>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-widest">
                            <th class="px-6 py-4 border-b border-slate-200">Reference</th>
                            <th class="px-6 py-4 border-b border-slate-200">Entity & Venue</th>
                            <th class="px-6 py-4 border-b border-slate-200">Temporal Vector</th>
                            <th class="px-6 py-4 border-b border-slate-200">Deposit Blocked</th>
                            <th class="px-6 py-4 border-b border-slate-200 text-right">Execution</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-700 divide-y divide-slate-100">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-mono text-xs font-bold text-mmu-blue"><?php echo htmlspecialchars($row['ref_id']); ?></td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-800"><?php echo htmlspecialchars($row['student_name']); ?></p>
                                    <p class="text-[10px] font-medium text-slate-500 flex items-center mt-0.5">
                                        <i data-lucide="map-pin" class="w-3 h-3 mr-1 text-slate-400"></i>
                                        <?php echo htmlspecialchars($row['venue_name']); ?>
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-700"><?php echo htmlspecialchars($row['booking_date']); ?></p>
                                    <p class="text-xs text-slate-500 font-mono"><?php echo htmlspecialchars($row['time_slot']); ?></p>
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-slate-800">
                                    RM <?php echo number_format((float)$row['deposit_paid'], 2); ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button onclick="openInspectModal(this)"
                                            data-id="<?php echo htmlspecialchars($row['raw_id']); ?>"
                                            data-ref="<?php echo htmlspecialchars($row['ref_id']); ?>"
                                            data-student="<?php echo htmlspecialchars($row['student_name']); ?>"
                                            data-venue="<?php echo htmlspecialchars($row['venue_name']); ?>"
                                            data-deposit="<?php echo number_format((float)$row['deposit_paid'], 2); ?>"
                                            class="px-3 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded transition shadow-sm flex items-center ml-auto">
                                        <i data-lucide="clipboard-check" class="w-4 h-4 mr-1"></i> Inspect
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500 font-medium">
                                    <i data-lucide="shield-check" class="w-12 h-12 mx-auto text-slate-300 mb-3"></i>
                                    System Clear: No pending inspections required at this time.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="inspect-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-200 transform scale-100 transition-transform">
            
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="text-lg font-extrabold text-slate-800 flex items-center">
                    <i data-lucide="clipboard-list" class="w-5 h-5 mr-2 text-indigo-600"></i> Venue Inspection Form
                </h3>
                <button onclick="closeInspectModal()" class="text-slate-400 hover:text-slate-600 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form action="../actions/process_inspection.php" method="POST">
                <input type="hidden" name="bid" id="modal-booking-id" value="">
                
                <div class="p-6">
                    <div class="mb-6 p-4 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                        <div class="flex justify-between mb-2">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-xs">Reference</span>
                            <span id="modal-ref" class="font-mono font-bold text-mmu-blue"></span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-xs">Entity</span>
                            <span id="modal-student" class="font-bold text-slate-800"></span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-xs">Target Node</span>
                            <span id="modal-venue" class="font-bold text-slate-800"></span>
                        </div>
                        <div class="flex justify-between border-t border-slate-200 pt-2 mt-2">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-xs">Held Deposit</span>
                            <span id="modal-deposit" class="font-mono font-bold text-emerald-600"></span>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Venue State Assessment</label>
                            <select name="ins_status" id="modal-status" onchange="togglePenaltyFields()" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-sm bg-white transition-all">
                                <option value="passed">Passed (Good Condition - Full Refund)</option>
                                <option value="failed">Failed (Damages or Dirty - Apply Deduction)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Damage Description Log</label>
                            <textarea name="damage_desc" id="modal-desc" rows="2" placeholder="Leave blank if venue is in good condition..." class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-sm transition-all"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Assessed Penalty (RM)</label>
                            <input type="number" name="penalty" id="modal-penalty" step="0.01" min="0" value="0.00" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-sm font-mono transition-all">
                            <p class="text-[10px] text-slate-400 mt-1">* Enter 0.00 for automatic full deposit refund.</p>
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end space-x-3">
                    <button type="button" onclick="closeInspectModal()" class="px-4 py-2 text-sm font-bold text-slate-500 hover:bg-slate-200 rounded-lg transition">Cancel</button>
                    <button type="submit" class="px-6 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition shadow">Confirm & Settle</button>
                </div>
            </form>
        </div>
    </div>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();

        function toggleSidebar() {
            const sidebar = document.getElementById('system-sidebar');
            sidebar.classList.toggle('sidebar-collapsed');
        }

        // 💡 Dynamic Form Validation Engine
        function togglePenaltyFields() {
            const status = document.getElementById('modal-status').value;
            const desc = document.getElementById('modal-desc');
            const penalty = document.getElementById('modal-penalty');

            // 💡 改為比對 'passed'
            if (status === 'passed') {
                desc.readOnly = true;
                desc.required = false;
                desc.value = '';
                desc.classList.add('bg-slate-100', 'cursor-not-allowed', 'placeholder-slate-300');
                
                penalty.readOnly = true;
                penalty.value = '0.00';
                penalty.classList.add('bg-slate-100', 'cursor-not-allowed');
            } else {
                desc.readOnly = false;
                desc.required = true;
                desc.classList.remove('bg-slate-100', 'cursor-not-allowed', 'placeholder-slate-300');
                desc.placeholder = "Required: Detail the damages or cleanliness issues...";
                
                penalty.readOnly = false;
                penalty.classList.remove('bg-slate-100', 'cursor-not-allowed');
                
                if (penalty.value === '0.00' || penalty.value === '0') {
                    penalty.value = ''; 
                }
            }
        }

        // Modal Injection Logic
        function openInspectModal(btn) {
            document.getElementById('modal-booking-id').value = btn.getAttribute('data-id');
            document.getElementById('modal-ref').innerText = btn.getAttribute('data-ref');
            document.getElementById('modal-student').innerText = btn.getAttribute('data-student');
            document.getElementById('modal-venue').innerText = btn.getAttribute('data-venue');
            document.getElementById('modal-deposit').innerText = btn.getAttribute('data-deposit');
            
            document.querySelector('#inspect-modal form').reset();
            togglePenaltyFields();
            
            document.getElementById('inspect-modal').classList.remove('hidden');
        }

        function closeInspectModal() {
            document.getElementById('inspect-modal').classList.add('hidden');
        }
    </script>
</body>
</html>
<?php 
if (isset($conn)) {
    $conn->close(); 
}
?>