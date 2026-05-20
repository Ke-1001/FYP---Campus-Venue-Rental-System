<?php
// File: admin/academic_schedule.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../core/components/datagrid.php'; 
require_once __DIR__ . '/../core/components/filter_engine.php'; // ∴ 引入过滤器引擎

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
| C: Configuration & Schema Definitions
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

// ∴ 数据集降维转换 (Dataset Dimensionality Reduction)
$sem_options = [];
if ($semesters && $semesters->num_rows > 0) {
    while($sem = $semesters->fetch_assoc()) {
        $sem_options[$sem['sem_id']] = $sem['sem_name'];
    }
}

$vid_options = [];
if ($venues && $venues->num_rows > 0) {
    while($v = $venues->fetch_assoc()) {
        $vid_options[$v['vid']] = '[' . $v['vid'] . '] ' . $v['vname'];
    }
}

// ∴ 核心拓扑一：Filter Schema 配置字典
$filter_schema = [
    'action' => 'academic_schedule.php',
    'show_submit_btn' => false, // 禁用提交按钮，依赖 auto_submit
    'fields' => [
        [
            'type' => 'select',
            'name' => 'filter_sem',
            'label' => 'Semester',
            'value' => ($f_sem > 0) ? (string)$f_sem : '',
            'placeholder' => 'All Semesters',
            'auto_submit' => true,
            'options' => $sem_options,
            'width' => 'w-56' // 增加宽度以适配长学期名
        ],
        [
            'type' => 'select',
            'name' => 'filter_vid',
            'label' => 'Venue',
            'value' => $f_vid,
            'placeholder' => 'All Venues',
            'auto_submit' => true,
            'options' => $vid_options,
            'width' => 'w-48'
        ],
        [
            'type' => 'select',
            'name' => 'filter_day',
            'label' => 'Day',
            'value' => $f_day,
            'placeholder' => 'All Days',
            'auto_submit' => true,
            'options' => [
                'Monday' => 'Monday',
                'Tuesday' => 'Tuesday',
                'Wednesday' => 'Wednesday',
                'Thursday' => 'Thursday',
                'Friday' => 'Friday',
                'Saturday' => 'Saturday',
                'Sunday' => 'Sunday'
            ],
            'width' => 'w-36'
        ]
    ]
];

// ∴ 核心拓扑二：DataGrid Schema 配置字典
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

// ∴ 控制器注入字典 (隔离外部 JavaScript)
$controller_config = [
    'edit_url_base' => 'add_exclusion.php?id=',
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

<?php echo render_filter_engine($filter_schema); ?>

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
            <?php echo render_datagrid($datagrid_schema, $result); ?>
        </div>
    </form>

    <div id="view-calendar-container" class="hidden flex-1 p-6 flex-col overflow-hidden bg-slate-50">
        <div class="grid grid-cols-7 gap-4 text-center font-black text-[10px] uppercase tracking-widest text-slate-400 border-b border-slate-200 pb-3 mb-4">
            <div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div>
        </div>
        <div class="grid grid-cols-7 gap-4 flex-1 overflow-y-auto" id="calendar-matrix-grid"></div>
    </div>
</div>

<script>
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
                            <div class="mt-2 text-[14px] font-mono font-bold text-slate-500 bg-slate-50 px-1.5 py-0.5 rounded-sm w-fit border border-slate-100">${evt.start} - ${evt.end}</div>
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