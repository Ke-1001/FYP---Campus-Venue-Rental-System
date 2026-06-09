<?php
// File: admin/pending_inspections.php
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
$filterBuilder = new FilterBuilder('pending_inspections.php', true);
$filterBuilder
    ->addField('text', 'f_bid', 'Ref ID', [], 'Search BID...', 'b.bid', 'LIKE')
    ->addField('text', 'f_student', 'Student Entity', [], 'Name or ID...', 'CONCAT(u.uid, " ", u.username)', 'LIKE')
    ->addField('text', 'f_venue', 'Asset', [], 'Name or Category...', 'CONCAT(v.vname, " ", vc.category)', 'LIKE')
    ->addField('date', 'f_date', 'Date', [], '', 'b.date_booked', '=')
    ->addField('text', 'f_inspector', 'Personnel', [], 'Inspector Name...', 's.staff_name', 'LIKE');

/*
|--------------------------------------------------------------------------
| D: Abstracted Data Execution & View Reshaping
|--------------------------------------------------------------------------
*/
// ∴ 3. 委託倉儲獲取結果集
$result = $inspectionRepo->getPendingInspections($filterBuilder);

// ∴ 4. 提取資料列並執行 Spatiotemporal Validation (時空推演)
$tz = new DateTimeZone('Asia/Kuala_Lumpur');
$now = new DateTime('now', $tz);
$records = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $start_dt = new DateTime($row['date_booked'] . ' ' . $row['time_start'], $tz);
        $end_dt = new DateTime($row['date_booked'] . ' ' . $row['time_end'], $tz);
        
        // ∴ 計算 $\mathcal{T}(t)$ 狀態向量並注入虛擬欄位 execution_state
        if ($now >= $end_dt || $row['booking_status'] === 'completed') {
            $row['execution_state'] = 'ready';
        } else {
            if ($now >= $start_dt && $now < $end_dt) {
                $row['execution_state'] = 'in_use';
            } else {
                $row['execution_state'] = 'awaiting';
            }
        }
        $records[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| C: Configuration & Schema Definitions
|--------------------------------------------------------------------------
*/
$page_title = "Pending Inspections";
$page_description = "Review upcoming physical assessments for post-event venues.";
$topbar_content = '
<div class="flex items-center">
    <a href="inspections.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Operations / Pending Inspections</h2>
</div>';
$extra_css = ["../assets/css/fiori_forms.css", "../assets/css/table.css"];

// ∴ 核心拓撲：DataGrid Schema 配置字典
// 藉由 map_badge 將複雜的 IF/ELSE 渲染邏輯抽象為聲明式配置
$datagrid_schema = [
    'enable_checkbox' => false,
    'primary_key' => 'bid',
    'row_action_url' => 'execute_inspection.php?bid=%s',
    'columns' => [
        ['key' => 'bid', 'label' => 'Reference', 'type' => 'link', 'url_format' => 'execute_inspection.php?bid=%s', 'width' => 'w-28 text-center'],
        ['key' => 'student_id', 'label' => 'Student ID', 'type' => 'text_mono', 'width' => 'w-28'],
        ['key' => 'student_name', 'label' => 'Student Name', 'type' => 'text_bold', 'width' => 'w-36'],
        ['key' => 'venue_name', 'label' => 'Asset', 'type' => 'text_bold', 'width' => 'w-40'],
        ['key' => 'venue_category', 'label' => 'Category', 'type' => 'badge', 'width' => 'w-32'],
        ['key' => 'date_booked', 'label' => 'Date', 'type' => 'text_mono', 'width' => 'w-28 text-center'],
        ['type' => 'time_range', 'label' => 'Reserved Slot', 'start_key' => 'time_start', 'end_key' => 'time_end', 'width' => 'w-32'],
        ['key' => 'inspector_name', 'label' => 'Inspector', 'type' => 'text_bold', 'width' => 'w-36'],
        ['key' => 'execution_state', 'label' => 'State', 'type' => 'map_badge', 'width' => 'w-32 text-center', 'map' => [
            'ready' => ['label' => 'Ready', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
            'in_use' => ['label' => 'In Use', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
            'awaiting' => ['label' => 'Awaiting', 'class' => 'bg-slate-50 text-slate-500 border-slate-200']
        ]]
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
    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center shrink-0">
        <h3 class="text-xs font-black text-slate-600 uppercase tracking-widest">
            Inspection Queue (<?php echo count($records); ?>)
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