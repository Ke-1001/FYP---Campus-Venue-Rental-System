<?php
// Debug 注入：在頁面頂部執行此段代碼後，重新點擊刪除，查看跳轉後的頁面輸出
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<pre>";
    print_r($_POST); // 這將顯示前端送出了什麼變數名稱
    exit; 
}
?>
<?php
// File: admin/semester_management.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

require_once __DIR__ . '/../core/components/FilterBuilder.php';
require_once __DIR__ . '/../core/components/DataGridBuilder.php';
require_once __DIR__ . '/../core/repositories/SemesterRepository.php';

use Core\Components\FilterBuilder;
use Core\Components\DataGridBuilder;
use Core\Repositories\SemesterRepository;

/*
|--------------------------------------------------------------------------
| I: Repository & Builder Instantiation
|--------------------------------------------------------------------------
*/
// 1. 實例化倉儲
$semesterRepo = new SemesterRepository($conn);

// 2. 建構過濾器矩陣
$filterBuilder = new FilterBuilder('semester_management.php', true);
$filterBuilder
    ->addField('text', 'filter_search', 'Search', [], 'e.g., 2610', 'sem_name', 'LIKE')
    ->addField('select', 'filter_status', 'Status', [
        '1' => 'Active Only',
        '0' => 'Inactive Only'
    ], 'All Status', 'is_active', '=', true);

// ∴ 3. 關鍵修正：必須先實例化 $gridBuilder，後續才能對其調用方法
$gridBuilder = new DataGridBuilder('sem_id', '../actions/process_semester.php', 'semester');

// ∴ 4. 領域對接：嚴格吻合 process_semester.php 中的 $_POST['selected_ids']
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

/*
|--------------------------------------------------------------------------
| V: View Rendering (Output Buffer)
|--------------------------------------------------------------------------
*/
ob_start();
?>

<?php echo $filterBuilder->render(); ?>
<?php echo $gridBuilder->render($result); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmBtn = document.getElementById('custom-modal-confirm-btn');
    
    if (confirmBtn) {
        // 1. 複製節點並替換：此舉將物理性抹除 datagrid_controller.php 綁定的錯誤監聽器
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        // 2. 重新綁定絕對正確的提交流程
        newConfirmBtn.addEventListener('click', function() {
            const form = document.getElementById('bulkActionForm');
            const actionInput = document.getElementById('bulk_action_type');
            
            if (form && actionInput) {
                actionInput.value = 'delete'; // ∴ 強制設定 action 變數，解決 Protocol 錯誤
                this.innerHTML = 'Purging...';
                this.disabled = true;
                this.classList.add('opacity-70', 'cursor-not-allowed');
                form.submit(); // 送出精準負載 (Payload)
            } else {
                console.error("DOM Matrix Error: Form or Action Input missing.");
            }
        });
    }
});
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