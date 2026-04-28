<?php
// File: admin/execute_inspection.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

$bid = intval($_GET['bid'] ?? 0);

if ($bid === 0) {
    die("Invalid Booking Identifier.");
}

// 💡 1. 提取核心詳情：包含預約、場地、學生與指派的檢驗員
$sql = "SELECT 
            b.*, u.username, v.vname, v.deposit, v.category,
            i.ins_id, s.staff_name AS inspector_name
        FROM inspection i
        JOIN booking b ON i.bid = b.bid
        JOIN user u ON b.uid = u.uid
        JOIN venue v ON b.vid = v.vid
        JOIN staff s ON i.sid = s.sid
        WHERE b.bid = ? AND i.ins_status = 'pending'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bid);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("No pending inspection record found for this ID.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Admin | Execute Assessment</title>
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
            $topbar_content = '
            <div class="flex items-center">
                <a href="pending_inspections.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Operations / Execute Inspection</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            <div class="max-w-5xl mx-auto">
                
                <div class="mb-8">
                    <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Post-Usage Assessment</h1>
                    <p class="text-sm text-slate-500 mt-1">Settle venue conditions for Booking #<?php echo $bid; ?>.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center">
                                <i data-lucide="database" class="w-3.5 h-3.5 mr-1.5"></i> Reference Context
                            </h3>
                            <div class="space-y-4 text-sm">
                                <div>
                                    <p class="text-slate-500 font-bold text-[10px] uppercase">Venue Details</p>
                                    <p class="font-bold text-slate-800"><?php echo htmlspecialchars($data['vname']); ?></p>
                                    <p class="text-xs text-slate-400"><?php echo htmlspecialchars($data['category']); ?></p>
                                </div>
                                <div>
                                    <p class="text-slate-500 font-bold text-[10px] uppercase">Allocated User</p>
                                    <p class="font-bold text-slate-800"><?php echo htmlspecialchars($data['username']); ?></p>
                                </div>
                                <div>
                                    <p class="text-slate-500 font-bold text-[10px] uppercase">Assigned Inspector</p>
                                    <p class="font-bold text-indigo-600"><?php echo htmlspecialchars($data['inspector_name']); ?></p>
                                </div>
                                <div class="pt-4 border-t border-slate-100">
                                    <p class="text-slate-500 font-bold text-[10px] uppercase">Held Deposit</p>
                                    <p class="font-mono font-black text-emerald-600 text-lg">RM <?php echo number_format($data['deposit'], 2); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="px-8 py-4 bg-slate-50 border-b border-slate-100">
                                <h3 class="font-bold text-slate-700">Assessment Submission</h3>
                            </div>
                            
                            <form action="../actions/process_inspection.php" method="POST" class="p-8 space-y-6">
                                <input type="hidden" name="bid" value="<?php echo $bid; ?>">
                                
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Final Condition Result</label>
                                    <select name="ins_status" id="ins_status" required onchange="syncPenaltyFields()" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                                        <option value="passed">Passed (Perfect Condition - Full Refund)</option>
                                        <option value="failed">Failed (Damages/Dirty - Apply Penalties)</option>
                                    </select>
                                </div>

                                <div id="penalty-block" class="space-y-6">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Detailed Observations</label>
                                        <textarea name="damage_desc" id="damage_desc" rows="3" placeholder="Describe the current state of the venue..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all"></textarea>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Assessed Penalty Amount (RM)</label>
                                        <input type="number" name="penalty" id="penalty" step="0.01" min="0" value="0.00" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                                        <p class="text-[10px] text-slate-400 mt-2">* Amount will be deducted from the student's deposit.</p>
                                    </div>
                                </div>

                                <div class="pt-6 border-t border-slate-100 flex justify-end">
                                    <button type="submit" class="px-8 py-3 bg-indigo-600 text-white text-sm font-bold rounded-xl shadow-md hover:bg-indigo-700 transition transform active:scale-[0.98] flex items-center">
                                        <i data-lucide="check-circle" class="w-4 h-4 mr-2"></i> Submit & Finalize Settle
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
        function toggleSidebar() { document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed'); }

        function syncPenaltyFields() {
            const status = document.getElementById('ins_status').value;
            const desc = document.getElementById('damage_desc');
            const penalty = document.getElementById('penalty');

            if (status === 'passed') {
                desc.readOnly = true;
                desc.value = '';
                desc.placeholder = "Venue is in standard condition.";
                desc.classList.add('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
                
                penalty.readOnly = true;
                penalty.value = '0.00';
                penalty.classList.add('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
            } else {
                desc.readOnly = false;
                desc.placeholder = "REQUIRED: Detail the identified issues...";
                desc.classList.remove('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
                
                penalty.readOnly = false;
                penalty.classList.remove('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
            }
        }
        window.onload = syncPenaltyFields;
    </script>
</body>
</html>