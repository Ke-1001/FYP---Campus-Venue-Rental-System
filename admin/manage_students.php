<?php
// File: admin/manage_students.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

// ∴ 嚴格引入 OOP 組件矩陣與倉儲
require_once __DIR__ . '/../core/components/FilterBuilder.php';
require_once __DIR__ . '/../core/components/DataGridBuilder.php';
require_once __DIR__ . '/../core/repositories/StudentRepository.php';

use Core\Components\FilterBuilder;
use Core\Components\DataGridBuilder;
use Core\Repositories\StudentRepository;

/*
|--------------------------------------------------------------------------
| I: Repository Initialization
|--------------------------------------------------------------------------
*/
$studentRepo = new StudentRepository($conn);

// ∴ 建構過濾器與排序器組合
$filterBuilder = new FilterBuilder('manage_students.php', true);
$filterBuilder
    ->addField('text', 'f_query', 'Student Informations', [], 'Search UID, Name, or Email...', 'CONCAT(uid, " ", username, " ", email)', 'LIKE')
    // 將 db_column 設為空字串，以防止 FilterBuilder 自動拼接 WHERE 子句
    ->addField('select', 'f_sort', 'Sorting', [
        'newest' => 'Newest Registered',
        'oldest' => 'Oldest Registered',
        'name_asc' => 'Alphabetical (A-Z)'
    ], '', '', '=');

/*
|--------------------------------------------------------------------------
| D: Data Execution & Reshaping
|--------------------------------------------------------------------------
*/
$current_sort = $_GET['f_sort'] ?? 'newest';
$result = $studentRepo->getAllStudents($filterBuilder, $current_sort);

$records = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $row['status'] = 'active'; // 依據原邏輯硬編碼為活躍狀態
        $row['joined_fmt'] = 'Joined: ' . date('M d, Y', strtotime($row['created_at']));
        $records[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| C: Configuration & OOP Schema Definitions
|--------------------------------------------------------------------------
*/
$page_title = "Student Directory";
$page_description = "Manage registered student accounts and review their contacts.";
$topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Identity Management / Student Directory</h2>';
$extra_css = ["../assets/css/fiori_forms.css", "../assets/css/table.css"];

// ∴ 啟動 DataGridBuilder 渲染引擎
$gridBuilder = new DataGridBuilder('uid', '../actions/process_student_action.php', 'student entity');
$gridBuilder->setCreateAction('#', 'Register Entity') // 保留介面一致性
    ->setRowActionUrl('edit_student.php?uid=%s')
    ->disableAction('edit')
    ->addColumn('uid', 'Reference (UID)', 'link', ['url_format' => 'edit_student.php?uid=%s', 'width' => 'w-32 text-center'])
    ->addColumn('username', 'Student Profile', 'text_bold', ['width' => 'w-48'])
    ->addColumn('email', 'Email Vector', 'text_mono', ['width' => 'w-56'])
    ->addColumn('phone_num', 'Phone', 'text_mono', ['width' => 'w-40'])
    ->addColumn('status', 'Account State', 'map_badge', ['width' => 'w-32 text-center', 'map' => [
        'active' => ['label' => 'Active', 'class' => 'bg-emerald-50 text-emerald-600 border-emerald-100']
    ]])
    ->addColumn('joined_fmt', 'Record', 'text_muted_mono', ['width' => 'w-40']);

// ∴ 注入全域批次控制器字典
$controller_config = [
    'edit_url_base' => 'edit_student.php?uid=',
    'delete_entity_name' => 'student entity'
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