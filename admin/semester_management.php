<?php
// This section prepares the admin semester management page.
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    echo "<pre>";
 print_r($_POST);
    exit;
}
?>
<?php

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

require_once __DIR__ . '/../core/components/FilterBuilder.php';
require_once __DIR__ . '/../core/components/DataGridBuilder.php';
require_once __DIR__ . '/../core/repositories/SemesterRepository.php';

use Core\Components\FilterBuilder;
use Core\Components\DataGridBuilder;
use Core\Repositories\SemesterRepository;


$semesterRepo = new SemesterRepository($conn);


$filterBuilder = new FilterBuilder('semester_management.php', true);
$filterBuilder
    ->addField('text', 'filter_search', 'Search', [], 'e.g., 2610', 'sem_name', 'LIKE')
    ->addField('select', 'filter_status', 'Status', [
        '1' => 'Active Only',
        '0' => 'Inactive Only'
    ], 'All Status', 'is_active', '=', true);


$gridBuilder = new DataGridBuilder('sem_id', '../actions/process_semester.php', 'semester');


$gridBuilder->setCheckboxName('selected_ids');
$gridBuilder->setCreateAction('add_semester.php', 'Create Semester');
$gridBuilder->setRowActionUrl('add_semester.php?id=%s');
$gridBuilder
    ->addColumn('sem_id', 'Semester ID', 'text_muted_mono', ['prefix' => '#', 'width' => 'w-32'])
    ->addColumn('sem_name', 'Semester Name', 'link', ['url_format' => 'add_semester.php?id=%s', 'width' => 'w-48'])
    ->addColumn('start_date', 'Start Date', 'text_mono', ['width' => 'w-40'])
    ->addColumn('end_date', 'End Date', 'text_mono', ['width' => 'w-40'])
    ->addColumn('is_active', 'Status', 'boolean_badge', [
        'width' => 'w-32 text-center',
        'true_label' => 'Active', 'true_class' => 'text-emerald-700 bg-emerald-50 border-emerald-200',
        'false_label' => 'Inactive', 'false_class' => 'text-slate-600 bg-slate-50 border-slate-200'
    ]);

$result = $semesterRepo->getAllWithFilters($filterBuilder);

$page_title = "Semester Management";
$page_description = "Manage academic terms, dates, and status settings.";
$topbar_content = '
<div class="flex items-center">
    <a href="academic.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Academic / Semester Management</h2>
</div>';
$extra_css = [];

$controller_config = [
    'edit_url_base' => 'add_semester.php?id=',
    'delete_entity_name' => 'semester'
];
require_once __DIR__ . '/../core/components/datagrid_controller.php';


ob_start();
?>

<?php echo $filterBuilder->render(); ?>
<?php echo $gridBuilder->render($result); ?>

<script>
document.addEventListener('DOMContentLoaded', function()
{
    const confirmBtn = document.getElementById('custom-modal-confirm-btn');

    if (confirmBtn)
    {

        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);


        newConfirmBtn.addEventListener('click', function()
        {
            const form = document.getElementById('bulkActionForm');
            const actionInput = document.getElementById('bulk_action_type');

            if (form && actionInput)
            {
 actionInput.value = 'delete';
                this.innerHTML = 'Purging...';
                this.disabled = true;
                this.classList.add('opacity-70', 'cursor-not-allowed');
 form.submit();
            } else
            {
                console.error("DOM Matrix Error: Form or Action Input missing.");
            }
        });
    }
});
</script>

<?php
$page_content = ob_get_clean();


require_once __DIR__ . '/../core/layout.php';
?>
