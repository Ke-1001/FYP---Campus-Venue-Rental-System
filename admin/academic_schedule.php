<?php
// File: admin/academic_schedule.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

// 💡 編輯模式數據加載 (Update Mode)
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_data = null;
if ($edit_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM academic_schedule WHERE sch_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
}

$venues = $conn->query("SELECT vid, vname FROM venue ORDER BY vid ASC");

$sql = "SELECT s.*, v.vname FROM academic_schedule s 
        JOIN venue v ON s.vid = v.vid 
        ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), s.start_time ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Academic Schedule</title>
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
            <a href="academic.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
            </a>
            <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Academic / Semester</h2>
        </div>';
        include('../includes/admin_topbar.php'); 
        ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth flex flex-col xl:flex-row gap-8">
            
            <div class="w-full xl:w-1/3 shrink-0">
                <div class="mb-6">
                    <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Pre-book Slot</h1>
                    <p class="text-xs text-slate-500 mt-1">Lock venues for periodic academic sessions.</p>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                    
                    <form action="../actions/process_schedule.php" method="POST" class="flex flex-col">
                        <input type="hidden" name="action" value="<?php echo $edit_data ? 'update' : 'create'; ?>">
                        <?php if ($edit_data): ?><input type="hidden" name="sch_id" value="<?php echo $edit_id; ?>"><?php endif; ?>

                        <div class="px-6 py-4 border-b border-slate-200 bg-white flex items-center">
                            <h2 class="text-sm font-bold text-slate-400">Schedule Parameter</h2>
                        </div>

                        <div class="p-6 space-y-5">
                            <div>
                                <label class="block text-[12px] font-black text-slate-400 tracking-widest mb-2">Target Venue</label>
                                <div class="relative">
                                    <select name="vid" required class="fiori-input w-full appearance-none pr-8 bg-white cursor-pointer font-bold text-slate-700">
                                        <option value="" disabled selected>Select Venue Node</option>
                                        <?php while($v = $venues->fetch_assoc()): ?>
                                            <option value="<?php echo $v['vid']; ?>" <?php echo ($edit_data && $edit_data['vid'] == $v['vid']) ? 'selected' : ''; ?>>
                                                [<?php echo $v['vid']; ?>] <?php echo htmlspecialchars($v['vname']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 absolute right-3 top-2.5 pointer-events-none"></i>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[12px] font-black text-slate-400 tracking-widest mb-2">Day</label>
                                <select name="day_of_week" required class="fiori-input w-full">
                                    <?php 
                                    $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                                    foreach($days as $day) {
                                        $sel = ($edit_data && $edit_data['day_of_week'] == $day) ? 'selected' : '';
                                        echo "<option value='$day' $sel>$day</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[12px] font-black text-slate-400 tracking-widest mb-2">Start Time</label>
                                    <input type="time" name="start_time" value="<?php echo $edit_data ? $edit_data['start_time'] : '08:00'; ?>" required class="fiori-input w-full font-mono">
                                </div>
                                <div>
                                    <label class="block text-[12px] font-black text-slate-400 tracking-widest mb-2">End Time</label>
                                    <input type="time" name="end_time" value="<?php echo $edit_data ? $edit_data['end_time'] : '10:00'; ?>" required class="fiori-input w-full font-mono">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[12px] font-black text-slate-400 tracking-widest mb-2">Subject / Reference</label>
                                <input type="text" name="subject" value="<?php echo $edit_data ? htmlspecialchars($edit_data['subject_name']) : ''; ?>" required placeholder="e.g., Database Systems Lecture" class="fiori-input w-full">
                            </div>
                        </div>

                        <div class="border-t border-slate-200 bg-white px-6 py-3 flex justify-end space-x-2 shrink-0">
                            <?php if ($edit_data): ?>
                                <a href="academic_schedule.php" class="px-5 py-2 text-sm font-bold text-indigo-600 hover:bg-indigo-50 rounded transition-colors">Discard</a>
                                <button type="submit" class="px-5 py-2 text-sm font-bold text-white bg-amber-500 hover:bg-amber-600 rounded shadow-sm">Save Changes</button>
                            <?php else: ?>
                                <button type="submit" class="px-5 py-2 text-sm font-bold text-white bg-[#0a6ed1] hover:bg-[#085caf] rounded shadow-sm">Lock Slot</button>
                            <?php endif; ?>
                        </div>
                    </form>

                    <?php if (!$edit_data): ?>
                    <form action="../actions/process_schedule.php" method="POST" enctype="multipart/form-data" class="bg-slate-50 border-t border-slate-200 p-4">
                        <input type="hidden" name="action" value="batch_import">
                        <div class="flex items-center space-x-3">
                            <div class="flex-1 relative">
                                <input type="file" name="csv_file" accept=".csv" required class="absolute inset-0 opacity-0 cursor-pointer z-10 w-full h-full" onchange="document.getElementById('file-name-display').innerText = this.files[0].name || 'Choose .csv file...'">
                                <div class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2.5 text-xs text-slate-500 font-mono flex justify-between items-center group hover:border-emerald-400 transition-colors">
                                    <span id="file-name-display" class="truncate pr-2">Choose .csv file...</span>
                                    <i data-lucide="upload" class="w-4 h-4 text-slate-400 group-hover:text-emerald-500 shrink-0"></i>
                                </div>
                            </div>
                            <button type="submit" class="px-5 py-2.5 bg-emerald-600 text-white text-xs font-bold rounded-lg shadow-sm hover:bg-emerald-700 transition-colors whitespace-nowrap flex items-center">
                                <i data-lucide="file-up" class="w-3 h-3 mr-1.5"></i> CSV Sync
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="w-full xl:w-2/3 flex flex-col">
                
                <?php if (isset($_SESSION['batch_results'])): 
                    $results = $_SESSION['batch_results'];
                    unset($_SESSION['batch_results']); 
                ?>
                <div id="batch-report-panel" class="mb-6 p-6 bg-white border border-red-100 rounded-2xl shadow-sm relative animate-[fadeIn_0.3s_ease-out]">
                    <button onclick="document.getElementById('batch-report-panel').remove()" class="absolute top-4 right-4 p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded transition-colors" title="Dismiss Report">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                    
                    <div class="flex items-center space-x-3 mb-4">
                        <h3 class="text-sm font-bold text-slate-800">Batch Synchronization Report</h3>
                        <span class="px-3 py-1 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-full text-[10px] font-black uppercase tracking-widest">
                            Success: <?php echo $results['success']; ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($results['conflicts'])): ?>
                        <div class="space-y-3">
                            <p class="text-[10px] font-bold text-red-500 uppercase tracking-widest">Conflict Anomalies Detected (<?php echo count($results['conflicts']); ?>)</p>
                            <div class="max-h-60 overflow-y-auto border border-slate-100 rounded-lg shadow-inner bg-slate-50/50">
                                <table class="w-full text-[11px] text-left">
                                    <thead class="bg-white sticky top-0 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                                        <tr class="text-slate-400 font-bold uppercase tracking-wider">
                                            <th class="px-4 py-3">Venue</th>
                                            <th class="px-4 py-3">Time Window</th>
                                            <th class="px-4 py-3">Conflict Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <?php foreach ($results['conflicts'] as $err): ?>
                                        <tr class="hover:bg-red-50/30 transition-colors">
                                            <td class="px-4 py-3 font-bold text-slate-700"><?php echo htmlspecialchars($err['vid']); ?></td>
                                            <td class="px-4 py-3 font-mono text-slate-500"><?php echo htmlspecialchars($err['day']); ?> <?php echo htmlspecialchars($err['start']); ?>-<?php echo htmlspecialchars($err['end']); ?></td>
                                            <td class="px-4 py-3 text-red-600 font-medium"><?php echo htmlspecialchars($err['reason']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="mb-6">
                    <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Active Exclusions</h1>
                    <p class="text-xs text-slate-500 mt-1">Periodic time windows locked for academic priority.</p>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-slate-200 flex-1 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[750px]">
                            <thead class="bg-slate-50 text-[10px] text-slate-400 font-black uppercase tracking-widest border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4">Venue & Subject</th>
                                    <th class="px-6 py-4">Day of Week</th>
                                    <th class="px-6 py-4">Time Window</th>
                                    <th class="px-6 py-4 text-right">Execution</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <span class="font-bold text-slate-800 block"><?php echo htmlspecialchars($row['subject_name']); ?></span>
                                            <span class="text-[12px] text-indigo-500 font-mono uppercase mt-1 block"><?php echo $row['vid']; ?> (<?php echo htmlspecialchars($row['vname']); ?>)</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-slate-600 rounded text-[12px] font-black uppercase ">
                                                <?php echo $row['day_of_week']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 font-mono text-xs">
                                            <span class="font-mono text-xs text-slate-600 block"><span class="mr-1 font-sans font-bold text-[10px] text-slate-400">START</span><?php echo date('H:i', strtotime($row['start_time'])); ?></span>
                                            <span class="font-mono text-xs text-slate-600 block mt-1"><span class="mr-3 font-sans font-bold text-[10px] text-slate-400">END</span><?php echo date('H:i', strtotime($row['end_time'])); ?></span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end space-x-1">
                                                <a href="?edit=<?php echo $row['sch_id']; ?>" class="p-2 text-indigo-500 hover:bg-indigo-50 rounded transition"><i data-lucide="edit-3" class="w-4 h-4"></i></a>
                                                <form action="../actions/process_schedule.php" method="POST" class="inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="sch_id" value="<?php echo $row['sch_id']; ?>">
                                                    <button type="button" onclick="triggerCustomModal(this.closest('form'), 'Drop this pre-book slot? The venue will become available for student booking.')" class="p-2 text-red-400 hover:bg-red-50 hover:text-red-600 rounded transition">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="px-6 py-12 text-center text-slate-400 font-medium"><i data-lucide="calendar-x" class="w-8 h-8 mx-auto text-slate-300 mb-2 opacity-50"></i>No academic exclusions defined.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="custom-modal" class="fixed inset-0 z-[100] hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center transition-opacity opacity-0">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform scale-95 transition-transform" id="modal-panel">
            <div class="p-6">
                <h3 class="text-lg font-extrabold text-slate-800 mb-2">Warning</h3>
                <p id="modal-msg" class="text-sm text-slate-500 font-medium leading-relaxed"></p>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end space-x-2">
                <button type="button" onclick="closeCustomModal()" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-200 rounded-lg transition-colors">Cancel</button>
                <button type="button" id="confirm-btn" class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors">Proceed</button>
            </div>
        </div>
    </div>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();
        function toggleSidebar() { document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed'); }

        let activeForm = null;
        function triggerCustomModal(form, msg) {
            activeForm = form;
            document.getElementById('modal-msg').innerText = msg;
            const m = document.getElementById('custom-modal');
            const p = document.getElementById('modal-panel');
            m.classList.remove('hidden');
            setTimeout(() => { m.classList.remove('opacity-0'); p.classList.remove('scale-95'); }, 10);
        }
        function closeCustomModal() {
            const m = document.getElementById('custom-modal');
            const p = document.getElementById('modal-panel');
            m.classList.add('opacity-0'); p.classList.add('scale-95');
            setTimeout(() => { m.classList.add('hidden'); activeForm = null; }, 200);
        }
        document.getElementById('confirm-btn').addEventListener('click', () => {
            if(activeForm) {
                document.getElementById('confirm-btn').innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>';
                lucide.createIcons();
                activeForm.submit();
            }
        });
    </script>
</body>
</html>