<?php
// File: admin/academic_schedule.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

$f_sem = isset($_GET['filter_sem']) ? intval($_GET['filter_sem']) : 0;
$f_vid = isset($_GET['filter_vid']) ? trim($_GET['filter_vid']) : '';
$f_day = isset($_GET['filter_day']) ? trim($_GET['filter_day']) : '';

$venues = $conn->query("SELECT vid, vname FROM venue ORDER BY vid ASC");
$semesters = $conn->query("SELECT sem_id, sem_name, is_active FROM semester_config ORDER BY start_date DESC");

$sql = "SELECT s.*, v.vname, sem.sem_name 
        FROM academic_schedule s 
        JOIN venue v ON s.vid = v.vid 
        JOIN semester_config sem ON s.sem_id = sem.sem_id
        WHERE 1=1";

if ($f_sem > 0) $sql .= " AND s.sem_id = " . $f_sem;
if (!empty($f_vid)) $sql .= " AND s.vid = '" . $conn->real_escape_string($f_vid) . "'";
if (!empty($f_day)) $sql .= " AND s.day_of_week = '" . $conn->real_escape_string($f_day) . "'";

$sql .= " ORDER BY sem.start_date DESC, FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), s.start_time ASC";
$result = $conn->query($sql);

$calendar_events = [];
while($row = $result->fetch_assoc()) {
    $calendar_events[] = [
        'id' => $row['sch_id'],
        'venue' => $row['vid'],
        'semester' => $row['sem_name'],
        'day' => $row['day_of_week'],
        'start' => substr($row['start_time'], 0, 5),
        'end' => substr($row['end_time'], 0, 5),
        'subject' => $row['subject_name']
    ];
}
$result->data_seek(0); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Admin | Academic Schedule</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>tailwind.config = { theme: { extend: { colors: { fiori: { text: '#1d2d3e', label: '#6b7280', blue: '#0a6ed1' } } } } }</script>
    <link rel="stylesheet" href="layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori_forms.css">
    <link rel="stylesheet" href="../assets/css/table.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <?php 
        $topbar_content = '<div class="flex items-center">
            <a href="academic.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
            </a>
            <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Academic / Class Schedule</h2>
        </div>';
        include('../includes/admin_topbar.php'); 
        ?>

        <div class="flex-1 overflow-y-auto p-8 flex flex-col">
            
            <div class="mb-6">
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Class Schedule</h1>
                <p class="text-sm text-slate-600 mt-1">Manage weekly class schedules and block venue availability.</p>
            </div>
            
            <div class="bg-white p-5 rounded-md border border-slate-200 shadow-[0_1px_2px_0_rgba(0,0,0,0.05)] mb-4 shrink-0">
                <form method="GET" action="academic_schedule.php" id="filterForm" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Semester</label>
                        <select name="filter_sem" onchange="this.form.submit()" class="fiori-input w-full bg-white font-bold text-[#004aad] cursor-pointer">
                            <option value="0">All Semesters</option>
                            <?php while($sem = $semesters->fetch_assoc()): ?>
                                <option value="<?php echo $sem['sem_id']; ?>" <?php echo ($f_sem == $sem['sem_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($sem['sem_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Venue</label>
                        <select name="filter_vid" onchange="this.form.submit()" class="fiori-input w-full bg-white font-bold text-slate-700 cursor-pointer">
                            <option value="">All Venues</option>
                            <?php while($v = $venues->fetch_assoc()): ?>
                                <option value="<?php echo $v['vid']; ?>" <?php echo ($f_vid == $v['vid']) ? 'selected' : ''; ?>>[<?php echo $v['vid']; ?>] <?php echo htmlspecialchars($v['vname']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Day</label>
                        <select name="filter_day" onchange="this.form.submit()" class="fiori-input w-full bg-white font-bold text-slate-700 cursor-pointer">
                            <option value="">All Days</option>
                            <?php foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?>
                                <option value="<?php echo $d; ?>" <?php echo ($f_day === $d) ? 'selected' : ''; ?>><?php echo $d; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex justify-end">
                        <a href="academic_schedule.php" class="px-4 py-2 bg-transparent text-slate-600 text-sm font-semibold rounded-md hover:bg-slate-100 border border-transparent transition flex items-center justify-center">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="flex items-center justify-between mb-4 bg-white p-3 rounded-md border border-slate-200 shadow-[0_1px_2px_0_rgba(0,0,0,0.05)] shrink-0">
                <div class="flex space-x-1 border-r border-slate-200 pr-4 mr-4">
                    <button onclick="switchView('table')" id="tab-btn-table" class="px-4 py-2 text-xs font-bold rounded-md transition-all flex items-center bg-[#f1f5f9] text-[#004aad] shadow-sm"><i data-lucide="list" class="w-3.5 h-3.5 mr-1.5"></i> Table View</button>
                    <button onclick="switchView('calendar')" id="tab-btn-calendar" class="px-4 py-2 text-xs font-bold rounded-md transition-all flex items-center text-slate-500 hover:bg-slate-50"><i data-lucide="calendar" class="w-3.5 h-3.5 mr-1.5"></i> Calendar View</button>
                </div>
                
                <div class="flex space-x-2">
                    <button onclick="window.location.href='add_exclusion.php'" class="px-4 py-2 text-xs font-semibold text-white bg-[#004aad] hover:bg-[#003882] rounded-md shadow-sm transition border border-[#004aad]"><i data-lucide="plus" class="w-3.5 h-3.5 inline mr-1"></i> Add Schedule</button>
                    <button id="btn-edit" disabled onclick="executeAction('edit')" class="px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200"><i data-lucide="edit-3" class="w-3.5 h-3.5 inline mr-1"></i> Edit</button>
                    <button id="btn-delete" disabled onclick="executeAction('delete')" class="px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200"><i data-lucide="trash-2" class="w-3.5 h-3.5 inline mr-1"></i> Delete</button>
                </div>
            </div>

            <div class="flex-1 custom-table-container flex flex-col relative overflow-hidden">
                
                <form id="bulkActionForm" action="../actions/process_schedule.php" method="POST" class="flex-1 overflow-hidden flex flex-col">
                    <input type="hidden" name="action" id="bulk_action_type" value="">
                    
                    <div id="view-table-container" class="flex-1 overflow-y-auto">
                        <table class="custom-table w-full">
                            <thead class="sticky top-0 z-10 bg-white">
                                <tr>
                                    <th class="w-12 text-center"><input type="checkbox" id="selectAll" onclick="toggleAll(this)" class="w-4 h-4 rounded border-slate-300 text-[#004aad] focus:ring-[#004aad]"></th>
                                    <th class="w-40">Semester</th>
                                    <th class="w-48">Subject</th>
                                    <th class="w-32">Venue ID</th>
                                    <th class="w-32">Day</th>
                                    <th class="w-40">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="text-center"><input type="checkbox" name="sch_ids[]" value="<?php echo $row['sch_id']; ?>" onclick="updateButtonStates()" class="row-cb w-4 h-4 rounded border-slate-300 text-[#004aad] focus:ring-[#004aad]"></td>
                                        <td>
                                            <span class="font-semibold text-slate-700 text-sm"><?php echo htmlspecialchars($row['sem_name']); ?></span>
                                        </td>
                                        <td>
                                            <span class="font-semibold text-slate-800 text-sm block"><?php echo htmlspecialchars($row['subject_name']); ?></span>
                                        </td>
                                        <td>
                                            <a href="edit_venue.php?vid=<?php echo urlencode($row['vid']); ?>" class="td-text-mono">
                                                <?php echo htmlspecialchars($row['vid']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="px-2 py-0.5 bg-[#f1f5f9] text-slate-600 text-[10px] font-bold uppercase tracking-wider rounded-sm border border-[#e2e8f0] inline-block">
                                                <?php echo htmlspecialchars($row['day_of_week']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="td-text-mono">
                                                <span class="text-[#059669]"><?php echo date('H:i', strtotime($row['start_time'])); ?></span>
                                                <span class="mx-1 text-slate-400">-</span>
                                                <span class="text-[#dc2626]"><?php echo date('H:i', strtotime($row['end_time'])); ?></span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="py-16 text-center text-slate-500 border-none hover:bg-transparent cursor-default">
                                            <span class="text-sm font-medium tracking-wide">No schedules found matching the selected criteria.</span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>

                <div id="view-calendar-container" class="hidden flex-1 p-6 flex-col overflow-hidden bg-slate-50">
                    <div class="grid grid-cols-7 gap-4 text-center font-black text-[10px] uppercase tracking-widest text-slate-400 border-b border-slate-200 pb-3 mb-4">
                        <div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div>
                    </div>
                    <div class="grid grid-cols-7 gap-4 flex-1 overflow-y-auto" id="calendar-matrix-grid"></div>
                </div>

            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();

        function switchView(target) {
            const tableTab = document.getElementById('tab-btn-table');
            const calendarTab = document.getElementById('tab-btn-calendar');
            // ∴ Target the entire form instead of the inner table
            const tableForm = document.getElementById('bulkActionForm');
            const calendarContainer = document.getElementById('view-calendar-container');

            if (target === 'table') {
                tableTab.className = "px-4 py-2 text-xs font-bold rounded-md transition-all flex items-center bg-[#f1f5f9] text-[#004aad] shadow-sm";
                calendarTab.className = "px-4 py-2 text-xs font-bold rounded-md transition-all flex items-center text-slate-500 hover:bg-slate-50";
                
                tableForm.classList.remove('hidden');
                tableForm.classList.add('flex');
                calendarContainer.classList.add('hidden');
                calendarContainer.classList.remove('flex');
            } else {
                calendarTab.className = "px-4 py-2 text-xs font-bold rounded-md transition-all flex items-center bg-[#f1f5f9] text-[#004aad] shadow-sm";
                tableTab.className = "px-4 py-2 text-xs font-bold rounded-md transition-all flex items-center text-slate-500 hover:bg-slate-50";
                
                calendarContainer.classList.remove('hidden');
                calendarContainer.classList.add('flex');
                tableForm.classList.add('hidden');
                tableForm.classList.remove('flex');
                
                renderCalendar(); 
            }
        }

        const rawEvents = <?php echo json_encode($calendar_events); ?>;
        
        // ∴ P_4: Renamed from renderSpatialCalendar to plain language
        function renderCalendar() {
            const gridContainer = document.getElementById('calendar-matrix-grid');
            gridContainer.innerHTML = '';
            const daysOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            const columnMaps = {}; 
            daysOrder.forEach(day => { columnMaps[day] = []; });
            rawEvents.forEach(evt => { if(columnMaps[evt.day]) columnMaps[evt.day].push(evt); });
            
            daysOrder.forEach(day => {
                let colHtml = `<div class="space-y-3 min-h-[400px]">`;
                if(columnMaps[day].length === 0) { colHtml += ``; } 
                else {
                    columnMaps[day].sort((a,b) => a.start.localeCompare(b.start));
                    columnMaps[day].forEach(evt => {
                        colHtml += `<div class="bg-white border-l-4 border-[#004aad] rounded-md p-3 shadow-[0_1px_2px_0_rgba(0,0,0,0.05)] border border-slate-200">
                                <span class="block text-xs font-extrabold text-slate-800 truncate" title="${evt.subject}">${evt.subject}</span>
                                <span class="block text-[9px] text-[#004aad] font-mono uppercase font-bold mt-1">${evt.venue}</span>
                                <div class="mt-2 text-[10px] font-mono font-bold text-slate-500 bg-slate-50 px-1.5 py-0.5 rounded-sm w-fit border border-slate-100">${evt.start} - ${evt.end}</div>
                            </div>`;
                    });
                }
                colHtml += `</div>`; gridContainer.innerHTML += colHtml;
            });
        }

        function toggleAll(source) {
            checkboxes = document.querySelectorAll('.row-cb');
            for(var i=0, n=checkboxes.length; i<n; i++) checkboxes[i].checked = source.checked;
            updateButtonStates();
        }

        function updateButtonStates() {
            const checkedCount = document.querySelectorAll('.row-cb:checked').length;
            const btnEdit = document.getElementById('btn-edit');
            const btnDelete = document.getElementById('btn-delete');

            if (checkedCount === 1) {
                btnEdit.disabled = false; btnEdit.className = "px-4 py-2 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-md shadow-sm transition";
                btnDelete.disabled = false; btnDelete.className = "px-4 py-2 text-xs font-semibold text-[#dc2626] bg-white hover:bg-red-50 border border-red-200 rounded-md shadow-sm transition";
            } else if (checkedCount > 1) {
                btnEdit.disabled = true; btnEdit.className = "px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200";
                btnDelete.disabled = false; btnDelete.className = "px-4 py-2 text-xs font-semibold text-[#dc2626] bg-white hover:bg-red-50 border border-red-200 rounded-md shadow-sm transition";
            } else {
                btnEdit.disabled = true; btnEdit.className = "px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200";
                btnDelete.disabled = true; btnDelete.className = "px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200";
            }
        }

        function executeAction(type) {
            const form = document.getElementById('bulkActionForm');
            const selected = document.querySelectorAll('.row-cb:checked');
            
            if (type === 'edit') {
                if (selected.length !== 1) return;
                window.location.href = `add_exclusion.php?id=${selected[0].value}`;
            } else if (type === 'delete') {
                if (!confirm(`Are you sure you want to delete the ${selected.length} selected schedule(s)?`)) return;
                document.getElementById('bulk_action_type').value = 'bulk_delete';
                form.submit();
            }
        }
    </script>
</body>
</html>