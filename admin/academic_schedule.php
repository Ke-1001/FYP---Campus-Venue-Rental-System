<?php
// File: admin/academic_schedule.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../core/components/datagrid.php'; // ∴ 引入引擎

/*
|--------------------------------------------------------------------------
| D: Data Retrieval & Logic
|--------------------------------------------------------------------------
*/
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

/*
|--------------------------------------------------------------------------
| C: Page Configuration & Schema Definition
|--------------------------------------------------------------------------
*/
$page_title = "Class Schedule";
$page_description = "Manage weekly class schedules and block venue availability.";
$topbar_content = '
<div class="flex items-center">
    <a href="academic.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Academic / Class Schedule</h2>
</div>';
$extra_css = ["../assets/css/fiori_forms.css", "../assets/css/table.css"];

// ∴ 核心拓扑：DataGrid 配置字典
$datagrid_schema = [
    'enable_checkbox' => true,
    'primary_key' => 'sch_id',
    'checkbox_name' => 'sch_ids',
    'columns' => [
        ['key' => 'sem_name', 'label' => 'Semester', 'type' => 'text', 'width' => 'w-40'],
        ['key' => 'subject_name', 'label' => 'Subject', 'type' => 'text_bold', 'width' => 'w-48'],
        ['key' => 'vid', 'label' => 'Venue ID', 'type' => 'link', 'url_format' => 'register_venue.php?vid=%s', 'width' => 'w-32'],
        ['key' => 'day_of_week', 'label' => 'Day', 'type' => 'text', 'width' => 'w-40'],
        ['type' => 'time_range', 'label' => 'Time', 'start_key' => 'start_time', 'end_key' => 'end_time', 'width' => 'w-40']
    ]
];

// ∴ 修正后的控制器注入字典
$controller_config = [
    'edit_url_base' => 'add_exclusion.php?id=', // 必须严格对齐目标文件的 $_GET['id']
    'delete_entity_name' => 'schedule record'
];
require_once __DIR__ . '/../core/components/datagrid_controller.php';

/*
|--------------------------------------------------------------------------
| V: View Rendering (Output Buffer)
|--------------------------------------------------------------------------
*/
ob_start();
?>

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
            <select name="filter_vid" onchange="this.form.submit()" class="fiori-input w-full bg-white font-bold text-slate-700 cursor-pointer"><option value="">All Venues</option></select>
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Day</label>
            <select name="filter_day" onchange="this.form.submit()" class="fiori-input w-full bg-white font-bold text-slate-700 cursor-pointer"><option value="">All Days</option></select>
        </div>
        <div class="flex justify-end">
            <a href="academic_schedule.php" class="px-4 py-2 bg-transparent text-slate-600 text-sm font-semibold rounded-md hover:bg-slate-100 border border-transparent transition flex items-center justify-center">Reset</a>
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
            <?php 
                // ∴ 注入渲染函数，原有的数十行 <table> 代码已被完全剥离
                echo render_datagrid($datagrid_schema, $result); 
            ?>
        </div>
    </form>

    <div id="view-calendar-container" class="hidden flex-1 p-6 flex-col overflow-hidden bg-slate-50">
        <div class="grid grid-cols-7 gap-4 text-center font-black text-[10px] uppercase tracking-widest text-slate-400 border-b border-slate-200 pb-3 mb-4">
            <div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div>
        </div>
        <div class="grid grid-cols-7 gap-4 flex-1 overflow-y-auto" id="calendar-matrix-grid"></div>
    </div>
</div>

<div id="custom-delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm opacity-0 transition-opacity duration-200">
    <div id="custom-modal-panel" class="bg-white rounded-md shadow-lg w-full max-w-sm p-6 transform scale-95 transition-transform duration-200 border border-slate-200">
        <h3 class="text-base font-bold text-slate-900 mb-2">Remove Exclusion</h3>
        <p class="text-sm text-slate-600 mb-6" id="custom-modal-msg">Are you sure you want to delete the selected record(s)?</p>
        <div class="flex justify-end space-x-3">
            <button type="button" onclick="closeCustomModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-transparent hover:bg-slate-100 rounded-md transition-colors">Cancel</button>
            <button type="button" id="custom-modal-confirm-btn" class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-md shadow-sm transition-colors border border-red-600">Delete</button>
        </div>
    </div>
</div>

<script>
    // ∴ 视图控制器 (View Controller)
    function switchView(target) {
        const tableTab = document.getElementById('tab-btn-table');
        const calendarTab = document.getElementById('tab-btn-calendar');
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

    // ∴ 空间矩阵渲染器 (Spatial Matrix Renderer)
    const rawEvents = <?php echo json_encode($calendar_events); ?>;
    
    function renderCalendar() {
        const gridContainer = document.getElementById('calendar-matrix-grid');
        gridContainer.innerHTML = '';
        const daysOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        const columnMaps = {}; 
        
        daysOrder.forEach(day => { columnMaps[day] = []; });
        rawEvents.forEach(evt => { if(columnMaps[evt.day]) columnMaps[evt.day].push(evt); });
        
        daysOrder.forEach(day => {
            let colHtml = `<div class="space-y-3 min-h-[400px]">`;
            if(columnMaps[day].length > 0) {
                columnMaps[day].sort((a,b) => a.start.localeCompare(b.start));
                columnMaps[day].forEach(evt => {
                    colHtml += `<div class="bg-white border-l-4 border-[#004aad] rounded-md p-3 shadow-[0_1px_2px_0_rgba(0,0,0,0.05)] border border-slate-200">
                            <span class="block text-l font-extrabold text-slate-800 truncate" title="${evt.subject}">${evt.subject}</span>
                            <span class="block text-[12px] text-[#004aad] font-mono uppercase font-bold mt-1">${evt.venue}</span>
                            <div class="mt-2 text-[10px] font-mono font-bold text-slate-500 bg-slate-50 px-1.5 py-0.5 rounded-sm w-fit border border-slate-100">${evt.start} - ${evt.end}</div>
                        </div>`;
                });
            }
            colHtml += `</div>`; 
            gridContainer.innerHTML += colHtml;
        });
    }
</script>

<?php
$page_content = ob_get_clean();

/*
|--------------------------------------------------------------------------
| L: Global Layout Engine
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../core/layout.php';
?>