<?php
// File: admin/staff_directory.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

// ∴ 嚴格引入 OOP 組件矩陣與倉儲
require_once __DIR__ . '/../core/components/FilterBuilder.php';
require_once __DIR__ . '/../core/components/DataGridBuilder.php';
require_once __DIR__ . '/../core/repositories/PersonnelRepository.php';

use Core\Components\FilterBuilder;
use Core\Components\DataGridBuilder;
use Core\Repositories\PersonnelRepository;

/*
|--------------------------------------------------------------------------
| I: Repository Initialization
|--------------------------------------------------------------------------
*/
$personnelRepo = new PersonnelRepository($conn);

$filterBuilder = new FilterBuilder('staff_directory.php', true);
$filterBuilder
    ->addField('text', 'f_query', 'Identity', [], 'Search ID, Name...', 'CONCAT(entity_id, " ", name, " ", email)', 'LIKE')
    ->addField('select', 'f_role', 'Access Privilege', [
        'super_admin' => 'Super Admin',
        'admin'       => 'Standard Admin',
        'inspector'   => 'Inspector'
    ], 'All Roles', 'access_level', '=');

/*
|--------------------------------------------------------------------------
| D: Data Execution & Reshaping
|--------------------------------------------------------------------------
*/
$result = $personnelRepo->getUnifiedDirectory($filterBuilder);

$records = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $row['compound_id'] = $row['entity_type'] . '|' . $row['entity_id'];
        $row['joined_fmt'] = 'Joined: ' . date('M Y', strtotime($row['created_at']));
        $records[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| C: Configuration & OOP Schema Definitions
|--------------------------------------------------------------------------
*/
$page_title = "Manage Existing Staff";
$page_description = "Manage existing personnel details, cross-entity permissions, and access status.";
$topbar_content = '
<div class="flex items-center">
    <a href="manage_admins.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Identity Management / Staff Directory</h2>
</div>';
$extra_css = [];

// ∴ 啟動 DataGridBuilder：徹底接管 HTML 節點的生成
$gridBuilder = new DataGridBuilder('compound_id', '../actions/process_personnel_deletion.php', 'personnel entity');
$gridBuilder->setCreateAction('add_staff.php', 'Register Staff')
    ->setRowActionUrl('route_personnel.php?ref=%s')
    ->addColumn('entity_id', 'Reference', 'text_mono', ['width' => 'w-28 text-center'])
    ->addColumn('name', 'Personnel Name', 'text_bold', ['width' => 'w-48'])
    ->addColumn('entity_type', 'Entity', 'badge', ['width' => 'w-24 text-center'])
    ->addColumn('email', 'Contact Vector', 'text_mono', ['width' => 'w-56'])
    ->addColumn('access_level', 'Privilege', 'map_badge', ['width' => 'w-32 text-center', 'map' => [
        'super_admin' => ['label' => 'Super Admin', 'class' => 'bg-purple-50 text-purple-700 border-purple-200'],
        'admin'       => ['label' => 'Admin', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
        'inspector'   => ['label' => 'Inspector', 'class' => 'bg-amber-50 text-amber-700 border-amber-200']
    ]])
    ->addColumn('status', 'Status', 'map_badge', ['width' => 'w-24 text-center', 'map' => [
        'active' => ['label' => 'Active', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'inactive' => ['label' => 'Inactive', 'class' => 'bg-slate-100 text-slate-500 border-slate-300']
    ]])
    ->addColumn('joined_fmt', 'Record', 'text_muted_mono', ['width' => 'w-32']);

// ∴ 注入全域控制器字典：徹底接管 JavaScript 與 Modal 邏輯
$controller_config = [
    'edit_url_base' => 'route_personnel.php?ref=',
    'delete_entity_name' => 'personnel entity'
];
require_once __DIR__ . '/../core/components/datagrid_controller.php';

/*
|--------------------------------------------------------------------------
| V: View Rendering
|--------------------------------------------------------------------------
*/
ob_start();
?>

<?php echo $filterBuilder->render(); ?>
<?php echo $gridBuilder->render($records); ?>

<?php
$page_content = ob_get_clean();

/*
|--------------------------------------------------------------------------
| L: Global Layout Engine
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../core/layout.php';
?>