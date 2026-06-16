<?php
// This section prepares the admin manage students page.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../core/components/FilterBuilder.php';
require_once __DIR__ . '/../core/components/DataGridBuilder.php';
require_once __DIR__ . '/../core/repositories/StudentRepository.php';
use Core\Components\FilterBuilder;
use Core\Components\DataGridBuilder;
use Core\Repositories\StudentRepository;
$studentRepo = new StudentRepository($conn);
$filterBuilder = new FilterBuilder('manage_students.php', true);
$filterBuilder
    ->addField('text', 'f_query', 'Student Informations', [], 'Search UID, Name, or Email...', 'CONCAT(uid, " ", username, " ", email)', 'LIKE')
    ->addField('select', 'f_sort', 'Sorting', [
        'newest' => 'Newest Registered',
        'oldest' => 'Oldest Registered',
        'name_asc' => 'Alphabetical (A-Z)'
    ], '', '', '=');
$current_sort = $_GET['f_sort'] ?? 'newest';
$result = $studentRepo->getAllStudents($filterBuilder, $current_sort);
$records = [];
if ($result && $result->num_rows > 0)
{
    while($row = $result->fetch_assoc())
    {
        $row['joined_fmt'] = 'Joined: ' . date('M d, Y', strtotime($row['created_at']));
        $records[] = $row;
    }
}
$constraint_error = '';
if (isset($_SESSION['error']) && strpos($_SESSION['error'], 'Failed to Delete') !== false)
{
    $constraint_error = $_SESSION['error'];
 unset($_SESSION['error']);
}
$page_title = "Student Directory";
$page_description = "Manage registered student accounts and review their contacts.";
$topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Identity Management / Student Directory</h2>';
$extra_css = [];
$gridBuilder = new DataGridBuilder('uid', '../actions/process_student.php', 'student entity');
$gridBuilder->setCreateAction('#', 'Register Entity')
    ->setRowActionUrl('edit_student.php?uid=%s')
 ->disableAction('create')
    ->addColumn('uid', 'Reference (UID)', 'link', ['url_format' => 'edit_student.php?uid=%s', 'width' => 'w-32 text-center'])
    ->addColumn('username', 'Student Profile', 'text_bold', ['width' => 'w-48'])
    ->addColumn('email', 'Email Vector', 'text_mono', ['width' => 'w-56'])
    ->addColumn('phone_num', 'Phone', 'text_mono', ['width' => 'w-40'])
    ->addColumn('status', 'Account State', 'map_badge', ['width' => 'w-32 text-center', 'map' => [
        'active' => ['label' => 'Active', 'class' => 'bg-emerald-50 text-emerald-600 border-emerald-100']
    ]])
    ->addColumn('joined_fmt', 'Record', 'text_muted_mono', ['width' => 'w-40']);
$controller_config = [
    'edit_url_base' => 'edit_student.php?uid=',
    'delete_entity_name' => 'student entity'
];
require_once __DIR__ . '/../core/components/datagrid_controller.php';
ob_start();
?>
<?php echo $filterBuilder->render(); ?>
<?php echo $gridBuilder->render($records); ?>
<div id="constraint-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] hidden flex items-center justify-center transition-opacity opacity-0">
    <div class="bg-white rounded-md shadow-2xl border border-slate-200 w-full max-w-lg p-6 transform scale-95 transition-transform" id="constraint-panel">
        <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-amber-50 rounded-full border border-amber-100">
            <i data-lucide="shield-alert" class="w-6 h-6 text-amber-600"></i>
        </div>
        <h3 class="text-lg font-black text-slate-800 mb-2 text-center tracking-tight">System Action Intercepted</h3>
        <p class="text-xs font-mono text-slate-600 leading-relaxed mb-6 bg-slate-50 p-4 rounded border border-slate-200" id="constraint-msg">
            </p>
        <div class="flex justify-center">
            <button type="button" onclick="closeConstraintModal()" class="px-6 py-2.5 text-sm font-semibold text-white rounded-md shadow-sm transition-colors bg-slate-800 hover:bg-slate-700">
                Acknowledge & Close
            </button>
        </div>
    </div>
</div>
<script>
    const closeConstraintModal = () =>
    {
        const modal = document.getElementById('constraint-modal');
        const panel = document.getElementById('constraint-panel');
        modal.classList.add('opacity-0');
        panel.classList.add('scale-95');
        setTimeout(() =>
        {
            modal.classList.add('hidden');
        }
, 200);
    }
;
    <?php if (!empty($constraint_error)): ?>
    document.addEventListener('DOMContentLoaded', function()
    {
        const modal = document.getElementById('constraint-modal');
        const panel = document.getElementById('constraint-panel');
        const msgNode = document.getElementById('constraint-msg');
        msgNode.innerText = <?php echo json_encode($constraint_error); ?>;
        modal.classList.remove('hidden');
 void modal.offsetWidth;
        modal.classList.remove('opacity-0');
        panel.classList.remove('scale-95');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
);
    <?php endif; ?>
</script>
<?php
$page_content = ob_get_clean();
require_once __DIR__ . '/../core/layout.php';
?>
