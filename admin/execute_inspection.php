<?php
// File: admin/execute_inspection.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

$bid = intval($_GET['bid'] ?? 0);

if ($bid === 0) {
    die("Invalid Booking Identifier.");
}

// 💡 1. 提取核心詳情：引入 vcategory 解決崩潰
$sql = "SELECT 
            b.*, u.username, v.vname, v.deposit, vc.category AS venue_category,
            i.ins_id, s.staff_name AS inspector_name
        FROM inspection i
        JOIN booking b ON i.bid = b.bid
        JOIN user u ON b.uid = u.uid
        JOIN venue v ON b.vid = v.vid
        JOIN vcategory vc ON v.vcid = vc.vcid
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
        tailwind.config = { theme: { extend: { colors: { cstyle: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="../assets/css/layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori_forms.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-[#f4f4f4] relative">
        
        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0 bg-white">
            <?php 
            $topbar_content = '
            <div class="flex items-center">
                <a href="pending_inspections.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Operations / Execute Inspection</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>
        </header>

        <form action="../actions/process_inspection.php" method="POST" id="inspectionForm" onsubmit="return validateInspectionForm()" class="flex-1 flex flex-col overflow-hidden">

            <div class="flex-1 overflow-y-auto p-4 md:p-8">
                <div class="bg-white border border-slate-200 rounded-lg shadow-sm">
                    
                    <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-white">
                        <h2 class="text-base font-bold text-slate-800">Post-Usage Assessment</h2>
                    </div>

                    <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">
                        
                        <div class="lg:col-span-6 space-y-4">
                            <h3 class="text-sm font-bold text-slate-800 mb-4">Reference Context</h3>
                            
                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label">Venue Details:</label>
                                <div class="col-span-2">
                                    <input type="text" value="<?php echo htmlspecialchars($data['vname']); ?> [<?php echo htmlspecialchars($data['venue_category']); ?>]" class="fiori-input fiori-readonly" readonly>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label">Allocated User:</label>
                                <div class="col-span-2">
                                    <input type="text" value="<?php echo htmlspecialchars($data['username']); ?>" class="fiori-input fiori-readonly" readonly>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 items-center border-b border-slate-100 pb-4">
                                <label class="col-span-1 text-sm text-fiori-label">Inspector:</label>
                                <div class="col-span-2">
                                    <input type="text" value="<?php echo htmlspecialchars($data['inspector_name']); ?>" class="fiori-input fiori-readonly text-indigo-700 font-bold" readonly>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 items-center pt-2">
                                <label class="col-span-1 text-sm text-fiori-label">Held Deposit:</label>
                                <div class="col-span-2 relative">
                                    <span class="absolute left-3 top-2.5 text-sm text-emerald-600 font-bold">RM</span>
                                    <input type="text" value="<?php echo number_format($data['deposit'], 2); ?>" class="fiori-input fiori-readonly pl-10 text-emerald-700 font-bold" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-6 space-y-4">
                            <h3 class="text-sm font-bold text-slate-800 mb-4">Assessment Submission</h3>
                            
                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label">Final Condition:</label>
                                <div class="col-span-2 relative">
                                    <select name="ins_status" id="ins_status" required onchange="syncPenaltyFields()" class="fiori-input appearance-none pr-8 bg-white cursor-pointer font-bold transition-colors">
                                        <option value="passed" class="text-emerald-600">Passed (Perfect Condition - Full Refund)</option>
                                        <option value="failed" class="text-red-600">Failed (Damages/Dirty - Apply Penalties)</option>
                                    </select>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-fiori-label absolute right-2 top-2 pointer-events-none"></i>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 items-start">
                                <label class="col-span-1 text-sm text-fiori-label mt-2">Observations:</label>
                                <div class="col-span-2">
                                    <textarea name="damage_desc" id="damage_desc" rows="3" class="fiori-input w-full resize-none p-3"></textarea>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label leading-tight">Assessed Penalty<br><span class="text-[10px] font-normal opacity-70">(Max: <?php echo number_format($data['deposit'], 2); ?>)</span></label>
                                <div class="col-span-2 relative">
                                    <span class="absolute left-3 top-2.5 text-sm text-slate-400 font-bold">RM</span>
                                    <input type="number" name="penalty" id="penalty" step="0.01" min="0" max="<?php echo $data['deposit']; ?>" value="0.00" class="fiori-input font-mono pl-10 text-red-600 font-bold">
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200 bg-white px-6 py-3 flex justify-end space-x-2 shrink-0 z-20 shadow-[0_-2px_10px_rgba(0,0,0,0.05)]">
                <button type="button" onclick="window.location.href='pending_inspections.php'" class="px-5 py-2 text-sm font-bold text-indigo-600 hover:bg-indigo-50 rounded transition-colors">
                    Cancel
                </button>
                <button type="submit" id="submit-btn" class="px-5 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded transition-colors shadow-sm">
                    Finalize Settle
                </button>
            </div>

        </form>
    </main>
    <div id="validation-modal" class="fixed inset-0 z-[100] hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center transition-opacity opacity-0">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform scale-95 transition-transform" id="validation-panel">
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="alert-octagon" class="w-7 h-7 text-red-600"></i>
                </div>
                <h3 class="text-lg font-extrabold text-slate-800 mb-2">Validation Incomplete</h3>
                <p id="validation-msg" class="text-sm text-slate-600 font-medium leading-relaxed"></p>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-center">
                <button type="button" onclick="closeValidationModal()" class="w-full py-2.5 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors shadow-sm active:scale-95">
                    Acknowledge
                </button>
            </div>
        </div>
    </div>

    <?php include('../includes/ui_components.php'); ?>

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
                desc.classList.add('fiori-readonly');
                
                penalty.readOnly = true;
                penalty.value = '0.00';
                penalty.classList.add('fiori-readonly');
            } else {
                desc.readOnly = false;
                desc.placeholder = "REQUIRED: Detail the identified issues...";
                desc.classList.remove('fiori-readonly');
                
                penalty.readOnly = false;
                penalty.classList.remove('fiori-readonly');
            }
        }

        // 💡 驗證視窗控制器
        function triggerValidationModal(msg) {
            document.getElementById('validation-msg').innerText = msg;
            const m = document.getElementById('validation-modal');
            const p = document.getElementById('validation-panel');
            m.classList.remove('hidden');
            setTimeout(() => { m.classList.remove('opacity-0'); p.classList.remove('scale-95'); }, 10);
        }

        function closeValidationModal() {
            const m = document.getElementById('validation-modal');
            const p = document.getElementById('validation-panel');
            m.classList.add('opacity-0'); p.classList.add('scale-95');
            setTimeout(() => { m.classList.add('hidden'); }, 200);
        }

        // 💡 升級版表單驗證 (無 Alert 版)
        // 💡 升級版表單驗證 (修復 Loading 卡死 Bug)
        function validateInspectionForm() {
            const status = document.getElementById('ins_status').value;
            const desc = document.getElementById('damage_desc').value.trim();
            const penalty = parseFloat(document.getElementById('penalty').value);

            if (status === 'failed') {
                if (desc === "") {
                    triggerValidationModal("Please provide detailed observations in the text box for the failed assessment.");
                    return false; // 阻斷提交，按鈕狀態不會改變
                }

                if (isNaN(penalty) || penalty <= 0) {
                    triggerValidationModal("For a 'Failed' status, you must apply a penalty amount greater than RM 0.00.");
                    return false; // 阻斷提交，按鈕狀態不會改變
                }
            }

            // 💡 只有在所有驗證都通過後，才將按鈕切換為 Processing 狀態
            const submitBtn = document.getElementById('submit-btn');
            if (submitBtn) {
                submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2 inline"></i> Processing...';
                submitBtn.classList.add('opacity-70', 'cursor-not-allowed', 'pointer-events-none');
                lucide.createIcons();
            }
            
            return true; // 允許提交
        }
        
        window.onload = syncPenaltyFields;
    </script>
</body>
</html>