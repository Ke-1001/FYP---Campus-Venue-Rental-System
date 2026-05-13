<?php
// File: admin/semester_management.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

// 💡 提取要編輯的特定學期資料 (Update Mode)
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_data = null;

if ($edit_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM semester_config WHERE sem_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// 💡 提取所有學期配置，按時間降冪排列
$sql = "SELECT * FROM semester_config ORDER BY start_date DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Semester Configuration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { cstyle: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori_forms.css">
    <style>
        input[type="date"]::-webkit-calendar-picker-indicator { display: none; -webkit-appearance: none; }
    </style>
</head>
<body class="bg-[#f4f4f4] text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0 bg-white">
            <?php 
            $topbar_content = '
            <div class="flex items-center">
                <a href="academic.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">System Core / Semester Management</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth flex flex-col xl:flex-row gap-8">
            
            <div class="w-full xl:w-1/3 shrink-0">
                <div class="mb-6">
                    <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Boundary Config</h1>
                    <p class="text-xs text-slate-500 mt-1">Define academic temporal limits.</p>
                </div>

                <form action="../actions/process_semester.php" method="POST" class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                    <input type="hidden" name="action" value="<?php echo $edit_data ? 'update' : 'create'; ?>">
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="sem_id" value="<?php echo $edit_id; ?>">
                    <?php endif; ?>

                    <div class="px-6 py-4 border-b border-slate-200 bg-white">
                        <h2 class="text-sm font-bold text-slate-800 flex items-center">
                            <?php if ($edit_data): ?>
                                <i data-lucide="edit" class="w-4 h-4 mr-2 text-amber-500"></i> Modify Semester Node
                            <?php else: ?>
                                <i data-lucide="calendar-plus" class="w-4 h-4 mr-2 text-indigo-600"></i> Initialize Semester
                            <?php endif; ?>
                        </h2>
                    </div>
                    <div class="p-6 space-y-5 flex-1">
                        
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Semester Nomenclature</label>
                            <input type="text" name="sem_name" value="<?php echo $edit_data ? htmlspecialchars($edit_data['sem_name']) : ''; ?>" required placeholder="e.g., Trimester 1, 2026/2027" class="fiori-input w-full">
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Temporal Start ($S_{start}$)</label>
                            <div class="relative">
                                <input type="date" name="start_date" id="start_date" value="<?php echo $edit_data ? $edit_data['start_date'] : ''; ?>" required class="fiori-input w-full uppercase font-mono pr-8">
                                <i data-lucide="calendar" onclick="document.getElementById('start_date').showPicker()" class="w-4 h-4 text-slate-400 absolute right-3 top-2.5 cursor-pointer hover:text-indigo-600"></i>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Temporal End ($S_{end}$)</label>
                            <div class="relative">
                                <input type="date" name="end_date" id="end_date" value="<?php echo $edit_data ? $edit_data['end_date'] : ''; ?>" required class="fiori-input w-full uppercase font-mono pr-8">
                                <i data-lucide="calendar" onclick="document.getElementById('end_date').showPicker()" class="w-4 h-4 text-slate-400 absolute right-3 top-2.5 cursor-pointer hover:text-indigo-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="border-t border-slate-200 bg-white px-6 py-3 flex justify-end space-x-2 shrink-0 z-20 shadow-[0_-2px_10px_rgba(0,0,0,0.05)]">
                        <?php if ($edit_data): ?>
                            <button type="button" onclick="window.location.href='semester_management.php'" class="px-5 py-2 text-sm font-bold text-indigo-600 hover:bg-indigo-50 rounded transition-colors">
                                Discard
                            </button>
                            <button type="submit" class="px-5 py-2 text-sm font-bold text-white bg-amber-500 hover:bg-amber-600 rounded transition-colors shadow-sm">
                                Save Changes
                            </button>
                        <?php else: ?>
                            <button type="submit" class="px-5 py-2 text-sm font-bold text-white bg-[#0a6ed1] hover:bg-[#085caf] rounded transition-colors shadow-sm">
                                Initialize
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="w-full xl:w-2/3 flex flex-col">
                <div class="mb-6">
                    <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Trimester List</h1>
                    <p class="text-xs text-slate-500 mt-1">Control active status and student booking permissions.</p>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-slate-200 flex-1 overflow-hidden flex flex-col">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[700px]">
                            <thead class="bg-slate-50 text-[10px] text-slate-400 font-black uppercase tracking-widest border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4">Semester Node</th>
                                    <th class="px-6 py-4">Temporal Boundary</th>
                                    <th class="px-6 py-4 text-center">Global State</th>
                                    <th class="px-6 py-4 text-center">Booking Gateway</th>
                                    <th class="px-6 py-4 text-right">Execution</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): 
                                        $is_active = (int)$row['is_active'] === 1;
                                        $is_open = (int)$row['is_booking_open'] === 1;
                                    ?>
                                    <tr class="hover:bg-slate-50 transition-colors <?php echo $is_active ? 'bg-indigo-50/20' : ''; ?>">
                                        <td class="px-6 py-4">
                                            <span class="font-bold text-slate-800 block"><?php echo htmlspecialchars($row['sem_name']); ?></span>
                                            <span class="text-[9px] text-slate-400 font-mono uppercase tracking-wider mt-1 block">ID: SEM-<?php echo $row['sem_id']; ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="font-mono text-xs text-slate-600 block"><span class="mr-1 font-sans font-bold text-[10px] text-slate-400">START</span> <?php echo date('M d, Y', strtotime($row['start_date'])); ?></span>
                                            <span class="font-mono text-xs text-slate-600 block mt-1"><span class="mr-3 font-sans font-bold text-[10px] text-slate-400">END</span> <?php echo date('M d, Y', strtotime($row['end_date'])); ?></span>
                                        </td>
                                        
                                        <td class="px-6 py-4 text-center">
                                            <form action="../actions/process_semester.php" method="POST" class="inline-block">
                                                <input type="hidden" name="action" value="toggle_active">
                                                <input type="hidden" name="sem_id" value="<?php echo $row['sem_id']; ?>">
                                                <?php if ($is_active): ?>
                                                    <button type="button" disabled class="px-3 py-1.5 bg-emerald-100 text-emerald-700 border border-emerald-200 rounded text-[10px] font-black uppercase tracking-widest cursor-default flex items-center mx-auto">
                                                        <i data-lucide="check-circle-2" class="w-3 h-3 mr-1.5"></i> Current
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" onclick="triggerCustomModal(this.closest('form'), 'Promote this semester to Current Active State? All others will be deactivated.')" class="px-3 py-1.5 bg-white text-slate-500 border border-slate-300 hover:border-emerald-400 hover:text-emerald-600 hover:bg-emerald-50 rounded text-[10px] font-black uppercase tracking-widest transition-colors flex items-center mx-auto shadow-sm">
                                                        Set Active
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                        
                                        <td class="px-6 py-4 text-center">
                                            <form action="../actions/process_semester.php" method="POST" class="inline-block">
                                                <input type="hidden" name="action" value="toggle_booking">
                                                <input type="hidden" name="sem_id" value="<?php echo $row['sem_id']; ?>">
                                                <?php if ($is_open): ?>
                                                    <button type="button" onclick="triggerCustomModal(this.closest('form'), 'Lock the booking gateway? Students will immediately be blocked from reserving venues.')" class="px-3 py-1.5 bg-blue-50 text-blue-600 border border-blue-200 hover:bg-red-50 hover:text-red-600 hover:border-red-200 rounded text-[10px] font-black uppercase tracking-widest transition-colors flex items-center mx-auto shadow-sm group">
                                                        <i data-lucide="unlock" class="w-3 h-3 mr-1.5 group-hover:hidden"></i>
                                                        <i data-lucide="lock" class="w-3 h-3 mr-1.5 hidden group-hover:inline"></i>
                                                        Open
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" onclick="triggerCustomModal(this.closest('form'), 'Unlock the booking gateway? Students will be able to submit reservations.')" class="px-3 py-1.5 bg-slate-100 text-slate-500 border border-slate-300 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 rounded text-[10px] font-black uppercase tracking-widest transition-colors flex items-center mx-auto shadow-sm group">
                                                        <i data-lucide="lock" class="w-3 h-3 mr-1.5 group-hover:hidden"></i>
                                                        <i data-lucide="unlock" class="w-3 h-3 mr-1.5 hidden group-hover:inline"></i>
                                                        Locked
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                        
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end space-x-1">
                                                <a href="?edit=<?php echo $row['sem_id']; ?>" class="p-2 text-indigo-500 hover:bg-indigo-50 rounded transition" title="Modify Record">
                                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                                </a>
                                                <form action="../actions/process_semester.php" method="POST" class="inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="sem_id" value="<?php echo $row['sem_id']; ?>">
                                                    <button type="button" onclick="triggerCustomModal(this.closest('form'), 'CRITICAL WARNING: Purge this semester boundary? This action is irreversible.')" class="p-2 text-red-400 hover:bg-red-50 hover:text-red-600 rounded transition" title="Purge Boundary">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium">
                                            <i data-lucide="layers" class="w-10 h-10 mx-auto text-slate-300 mb-3 opacity-50"></i>
                                            Zero bounds detected. Initialize a semester to begin.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <div id="custom-action-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center transition-opacity opacity-0">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform scale-95 transition-transform" id="custom-modal-panel">
            <div class="p-6">
                <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center mb-4">
                    <i data-lucide="alert-triangle" class="w-6 h-6 text-amber-600"></i>
                </div>
                <h3 class="text-lg font-extrabold text-slate-800 mb-2">Execution Confirmation</h3>
                <p id="custom-modal-msg" class="text-sm text-slate-500 font-medium leading-relaxed"></p>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end space-x-3">
                <button type="button" onclick="closeCustomModal()" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="button" id="custom-modal-confirm-btn" class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm">
                    Proceed
                </button>
            </div>
        </div>
    </div>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();
        function toggleSidebar() { document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed'); }

        // Custom Modal Logic Engine
        let pendingFormToSubmit = null;

        function triggerCustomModal(formElement, message) {
            pendingFormToSubmit = formElement;
            document.getElementById('custom-modal-msg').innerText = message;
            
            const modal = document.getElementById('custom-action-modal');
            const panel = document.getElementById('custom-modal-panel');
            
            modal.classList.remove('hidden');
            // Trigger reflow
            void modal.offsetWidth;
            
            modal.classList.remove('opacity-0');
            panel.classList.remove('scale-95');
        }

        function closeCustomModal() {
            const modal = document.getElementById('custom-action-modal');
            const panel = document.getElementById('custom-modal-panel');
            
            modal.classList.add('opacity-0');
            panel.classList.add('scale-95');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                pendingFormToSubmit = null;
            }, 200);
        }

        document.getElementById('custom-modal-confirm-btn').addEventListener('click', function() {
            if (pendingFormToSubmit) {
                // To display spinner or loading state
                this.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>';
                lucide.createIcons();
                this.classList.add('opacity-70', 'cursor-not-allowed');
                pendingFormToSubmit.submit();
            }
        });
    </script>
</body>
</html>