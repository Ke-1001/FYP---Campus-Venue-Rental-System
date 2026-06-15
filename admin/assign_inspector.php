<?php
// File: admin/assign_inspector.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/booking_functions.php';
require_once __DIR__ . '/../core/components/datagrid.php'; 

// ∴ 引入核心架構與全新倉儲
require_once __DIR__ . '/../core/components/FilterBuilder.php';
require_once __DIR__ . '/../core/repositories/InspectionRepository.php';

use Core\Components\FilterBuilder;
use Core\Repositories\InspectionRepository;

syncCompletedBookings($conn);

/*
|--------------------------------------------------------------------------
| I: Repository Initialization & System Protocol
|--------------------------------------------------------------------------
*/
// Auto-expire unpaid bookings
expireUnpaidBookings($conn);

// ∴ 1. 實例化檢驗分派倉儲
$inspectionRepo = new InspectionRepository($conn);

// ∴ 2. 建構過濾器矩陣 (Filter Topology)
$filterBuilder = new FilterBuilder('assign_inspector.php', true);
$filterBuilder
    ->addField('text', 'f_bid', 'Booking ID', [], 'Search ID...', 'b.bid', 'LIKE')
    ->addField('text', 'f_student', 'Student Entity', [], 'Name or ID...', 'CONCAT(u.uid, " ", u.username)', 'LIKE')
    ->addField('text', 'f_venue', 'Asset', [], 'Name or Category...', 'CONCAT(v.vname, " ", vc.category)', 'LIKE')
    ->addField('date', 'f_date', 'Date', [], '', 'b.date_booked', '=');

/*
|--------------------------------------------------------------------------
| D: Abstracted Data Execution & View Reshaping
|--------------------------------------------------------------------------
*/
// ∴ 3. 委託倉儲獲取結果集
$result = $inspectionRepo->getPendingAssignments($filterBuilder);

// ∴ 4. 提取資料列以供給 DataGrid 與計數器
$records = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| C: Configuration & Schema Definitions
|--------------------------------------------------------------------------
*/
$page_title = "Assign Inspector";
$page_description = "Delegate post-event venue inspections to designated personnel.";
$topbar_content = '
<div class="flex items-center">
    <a href="manage_bookings.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Bookings / Assign Inspector</h2>
</div>';
$extra_css = [];

// ∴ 核心拓撲：DataGrid Schema 配置字典
$datagrid_schema = [
    'enable_checkbox' => false,
    'primary_key' => 'bid',
    'row_action_url' => 'assign_inspector_detail.php?bid=%s',
    'columns' => [
        ['key' => 'bid', 'label' => 'Reference', 'type' => 'link', 'url_format' => 'assign_inspector_detail.php?bid=%s', 'width' => 'w-28 text-center'],
        ['key' => 'student_id', 'label' => 'Student ID', 'type' => 'link', 'url_format' => 'edit_student.php?uid=%s', 'width' => 'w-28'],
        ['key' => 'student_name', 'label' => 'Student Name', 'type' => 'text_mono', 'width' => 'w-36'],
        ['key' => 'venue_id', 'label' => 'Venue ID', 'type' => 'link', 'url_format' => 'register_venue.php?vid=%s', 'width' => 'w-28'],
        ['key' => 'venue_name', 'label' => 'Venue', 'type' => 'text_mono', 'width' => 'w-40'],
        ['key' => 'venue_category', 'label' => 'Category', 'type' => 'text_mono', 'width' => 'w-32'],
        ['key' => 'date_booked', 'label' => 'Date', 'type' => 'text_mono', 'width' => 'w-28 text-center'],
        ['type' => 'time_range', 'label' => 'Time', 'start_key' => 'time_start', 'end_key' => 'time_end', 'width' => 'w-32']
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
            Pending Assignment (<?php echo count($records); ?>)
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