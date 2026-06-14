<?php
// File: admin/track_inspections.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../core/components/datagrid.php'; 

// ∴ 引入核心組件與倉儲依賴
require_once __DIR__ . '/../core/components/FilterBuilder.php';
require_once __DIR__ . '/../core/repositories/InspectionRepository.php';

use Core\Components\FilterBuilder;
use Core\Repositories\InspectionRepository;

/*
|--------------------------------------------------------------------------
| I: Repository Initialization & System Protocol
|--------------------------------------------------------------------------
*/
// ∴ 1. 實例化檢驗倉儲
$inspectionRepo = new InspectionRepository($conn);

// ∴ 2. 建構過濾器矩陣 (Filter Topology)
$filterBuilder = new FilterBuilder('track_inspections.php', true);
$filterBuilder
    ->addField('text', 'f_bid', 'Ref ID', [], 'Search BID...', 'i.bid', 'LIKE')
    ->addField('text', 'f_student', 'Student Entity', [], 'Name or ID...', 'CONCAT(u.uid, " ", u.username)', 'LIKE')
    ->addField('text', 'f_venue', 'Asset', [], 'Venue or Category...', 'CONCAT(v.vname, " ", vc.category)', 'LIKE')
    ->addField('date', 'f_date', 'Event Date', [], '', 'b.date_booked', '=')
    ->addField('text', 'f_inspector', 'Inspector', [], 'Personnel...', 's.staff_name', 'LIKE')
    ->addField('select', 'f_res', 'Result State', [
        'passed' => 'Passed',
        'overdue' => 'Overdue',
        'failed' => 'Failed'
    ], 'All Results', 'i.ins_status', '=');

/*
|--------------------------------------------------------------------------
| D: Abstracted Data Execution & View Reshaping
|--------------------------------------------------------------------------
*/
// ∴ 3. 委託倉儲獲取結果集
$result = $inspectionRepo->getInspectionHistory($filterBuilder);

// ∴ 4. 提取資料列並執行 Data Flattening (資料展平)
$records = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // 時序格式化
        $row['inspected_at_fmt'] = $row['inspected_at'] ? date('y/m/d H:i', strtotime($row['inspected_at'])) : '--';
        $row['date_booked_fmt'] = date('y/m/d', strtotime($row['date_booked']));
        
        // 邏輯展平：將條件化的文字與罰金狀態統一化以適配 DataGrid
        if ($row['ins_status'] === 'passed') {
            $row['penalty_val'] = 0.00;
            $row['observations'] = 'Clearance granted. Refund authorized.';
        } else {
            $row['penalty_val'] = (float)$row['penalty'];
            $row['observations'] = $row['damage_desc'] ?: 'No details provided.';
        }
        
        $records[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| C: Configuration & Schema Definitions
|--------------------------------------------------------------------------
*/
$page_title = "Inspection History";
$page_description = "Trace historical venue assessments and financial penalties.";
$topbar_content = '
<div class="flex items-center">
    <a href="inspections.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Reporting / Inspection History</h2>
</div>';
$extra_css = ["../assets/css/fiori_forms.css", "../assets/css/table.css"];

// ∴ 核心拓撲：DataGrid Schema 配置字典
$datagrid_schema = [
    'enable_checkbox' => false,
    'primary_key' => 'bid',
    'row_action_url' => 'process_flow.php?bid=%s',
    'columns' => [
        ['key' => 'bid', 'label' => 'Reference', 'type' => 'link', 'url_format' => 'process_flow.php?bid=%s', 'width' => 'w-24 text-center'],
        ['key' => 'student_id', 'label' => 'Student ID', 'type' => 'text_mono', 'width' => 'w-28'],
        ['key' => 'student_name', 'label' => 'Student Name', 'type' => 'text_bold', 'width' => 'w-36'],
        ['key' => 'venue_name', 'label' => 'Asset', 'type' => 'text_bold', 'width' => 'w-36'],
        ['key' => 'inspector_name', 'label' => 'Inspector', 'type' => 'text_mono', 'width' => 'w-32'],
        ['key' => 'inspected_at_fmt', 'label' => 'Log Time', 'type' => 'text_mono', 'width' => 'w-32 text-center'],
        ['key' => 'ins_status', 'label' => 'Outcome', 'type' => 'map_badge', 'width' => 'w-28 text-center', 'map' => [
            'passed' => ['label' => 'Passed', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'overdue' => ['label' => 'Overdue', 'class' => 'bg-yellow-50 text-yellow-700 border-yellow-200'],
            'failed' => ['label' => 'Failed', 'class' => 'bg-red-50 text-red-700 border-red-200']
        ]],
        ['key' => 'penalty_val', 'label' => 'Penalty', 'type' => 'currency', 'prefix' => '-RM ', 'width' => 'w-28 text-right'],
        ['key' => 'observations', 'label' => 'Observations', 'type' => 'text_muted_mono', 'width' => 'w-56']
    ]
];

/*
|--------------------------------------------------------------------------
| V: View Rendering (Output Buffer)
|--------------------------------------------------------------------------
*/
ob_start();
?>

<?php echo $filterBuilder->render(); ?>

<div class="custom-table-container flex-1 overflow-hidden flex flex-col">
    <div class="px-4 py-3 border-b border-slate-200 bg-[#f8fafc] flex justify-between items-center shrink-0">
        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest">
            Global Record Log (<?php echo count($records); ?>)
        </h3>
    </div>
    
    <div class="flex-1 overflow-y-auto">
        <?php echo render_datagrid($datagrid_schema, $records); ?>
    </div>
</div>

<?php
$page_content = ob_get_clean();

/*
|--------------------------------------------------------------------------
| L: Global Layout Engine
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../core/layout.php';
?>