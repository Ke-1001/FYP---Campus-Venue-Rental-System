<?php
// File: admin/staff_directory.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../core/components/datagrid.php'; 

// ∴ 引入核心組件與全新倉儲
require_once __DIR__ . '/../core/components/FilterBuilder.php';
require_once __DIR__ . '/../core/repositories/PersonnelRepository.php';

use Core\Components\FilterBuilder;
use Core\Repositories\PersonnelRepository;

/*
|--------------------------------------------------------------------------
| I: Repository Initialization & System Protocol
|--------------------------------------------------------------------------
*/
// ∴ 1. 實例化身分管理倉儲
$personnelRepo = new PersonnelRepository($conn);

// ∴ 2. 建構過濾器矩陣 (Filter Topology)
$filterBuilder = new FilterBuilder('staff_directory.php', true);
$filterBuilder
    ->addField('text', 'f_query', 'Identity', [], 'Search ID, Name, Email...', 'CONCAT(entity_id, " ", name, " ", email)', 'LIKE')
    ->addField('select', 'f_role', 'Access Privilege', [
        'super_admin' => 'Super Admin',
        'admin'       => 'Standard Admin',
        'inspector'   => 'Inspector'
    ], 'All Roles', 'access_level', '=');

/*
|--------------------------------------------------------------------------
| D: Abstracted Data Execution & View Reshaping
|--------------------------------------------------------------------------
*/
// ∴ 3. 委託倉儲獲取結果集
$result = $personnelRepo->getUnifiedDirectory($filterBuilder);

// ∴ 4. 提取資料列並執行 Data Flattening (資料展平與組合鍵生成)
$records = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // 生成多型態路由的組合鍵 (e.g., "Admin|A001")
        $row['compound_id'] = $row['entity_type'] . '|' . $row['entity_id'];
        $row['joined_fmt'] = 'Joined: ' . date('M Y', strtotime($row['created_at']));
        $records[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| C: Configuration & Schema Definitions
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
$extra_css = ["../assets/css/fiori_forms.css", "../assets/css/table.css"];

// ∴ 核心拓撲：DataGrid Schema 配置字典
$datagrid_schema = [
    'enable_checkbox' => true,
    'primary_key' => 'compound_id',
    'checkbox_name' => 'personnel_refs',
    'columns' => [
        ['key' => 'entity_id', 'label' => 'Reference ID', 'type' => 'text_mono', 'width' => 'w-28 text-center'],
        ['key' => 'name', 'label' => 'Personnel Name', 'type' => 'text_bold', 'width' => 'w-48'],
        ['key' => 'entity_type', 'label' => 'Entity', 'type' => 'badge', 'width' => 'w-24 text-center'],
        ['key' => 'email', 'label' => 'Contact Vector', 'type' => 'text_mono', 'width' => 'w-56'],
        ['key' => 'access_level', 'label' => 'Privilege', 'type' => 'map_badge', 'width' => 'w-32 text-center', 'map' => [
            'super_admin' => ['label' => 'Super Admin', 'class' => 'bg-purple-50 text-purple-700 border-purple-200'],
            'admin'       => ['label' => 'Admin', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
            'inspector'   => ['label' => 'Inspector', 'class' => 'bg-amber-50 text-amber-700 border-amber-200']
        ]],
        ['key' => 'status', 'label' => 'Status', 'type' => 'map_badge', 'width' => 'w-24 text-center', 'map' => [
            'active' => ['label' => 'Active', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'inactive' => ['label' => 'Inactive', 'class' => 'bg-slate-100 text-slate-500 border-slate-300']
        ]],
        ['key' => 'joined_fmt', 'label' => 'Record', 'type' => 'text_muted_mono', 'width' => 'w-32']
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

<div class="mb-4 bg-white p-3 rounded-md border border-slate-200 shadow-[0_1px_2px_0_rgba(0,0,0,0.05)] shrink-0 flex justify-between items-center">
    <div class="text-xs font-bold text-slate-500 pl-2">
        <span id="cb-counter">0</span> selected
    </div>
    <div class="flex space-x-2">
        <a href="add_staff.php" class="px-4 py-2 text-xs font-semibold text-white bg-[#004aad] hover:bg-[#003882] rounded-md shadow-sm transition border border-[#004aad]">
            <i data-lucide="user-plus" class="w-3.5 h-3.5 inline mr-1"></i> Register New Staff
        </a>
        <button id="btn-edit" disabled onclick="executeAction('edit')" class="px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200">
            <i data-lucide="settings-2" class="w-3.5 h-3.5 inline mr-1"></i> Modify Access
        </button>
        <button id="btn-delete" disabled onclick="executeAction('delete')" class="px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200">
            <i data-lucide="trash-2" class="w-3.5 h-3.5 inline mr-1"></i> Deactivate / Purge
        </button>
    </div>
</div>

<div class="custom-table-container flex-1 overflow-hidden flex flex-col relative">
    <form id="bulkActionForm" action="../actions/process_personnel_action.php" method="POST" class="flex-1 overflow-hidden flex flex-col">
        <input type="hidden" name="action_type" id="bulk_action_type" value="">
        <div class="flex-1 overflow-y-auto">
            <?php echo render_datagrid($datagrid_schema, $records); ?>
        </div>
    </form>
</div>

<div id="decision-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center transition-opacity opacity-0">
    <div class="bg-white rounded-md shadow-lg border border-slate-200 w-full max-w-sm p-6 transform scale-95 transition-transform" id="decision-panel">
        <h3 class="text-lg font-bold text-slate-800 mb-2">System Confirmation</h3>
        <p class="text-sm text-slate-600 font-medium leading-relaxed mb-6" id="modal-msg"></p>
        <div class="flex justify-end space-x-3">
            <button type="button" onclick="closeDecisionModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-transparent hover:bg-slate-100 rounded-md transition-colors">Cancel</button>
            <button type="button" id="modal-confirm-btn" class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-md shadow-sm transition-colors border border-red-600">Proceed</button>
        </div>
    </div>
</div>

<script>
    // ∴ 嚴格對接 checkbox 狀態與智慧型按鈕控制器
    const toggleAll = (source) => {
        document.querySelectorAll('.row-cb').forEach(cb => { cb.checked = source.checked; });
        updateButtonStates();
    };

    const updateButtonStates = () => {
        const selectedCount = document.querySelectorAll('.row-cb:checked').length;
        const btnEdit = document.getElementById('btn-edit');
        const btnDelete = document.getElementById('btn-delete');
        const cbCounter = document.getElementById('cb-counter');
        
        if (cbCounter) cbCounter.innerText = selectedCount;

        const isSingle = selectedCount === 1;
        const isMultiple = selectedCount > 0;

        if (btnEdit) {
            btnEdit.disabled = !isSingle;
            btnEdit.className = isSingle 
                ? "px-4 py-2 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-md shadow-sm transition cursor-pointer"
                : "px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200";
        }

        if (btnDelete) {
            btnDelete.disabled = !isMultiple;
            btnDelete.className = isMultiple 
                ? "px-4 py-2 text-xs font-semibold text-red-600 bg-white hover:bg-red-50 border border-red-200 rounded-md shadow-sm transition cursor-pointer"
                : "px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200";
        }
    };

    document.addEventListener('change', function(e) {
        if(e.target && e.target.classList.contains('row-cb')) updateButtonStates();
    });

    const executeAction = (type) => {
        const selected = document.querySelectorAll('.row-cb:checked');
        if (selected.length === 0) return;

        if (type === 'edit' && selected.length === 1) {
            // ∴ 多型態路由推演 (Polymorphic Routing)
            // 將 Compound ID 分拆為 EntityType 與 EntityID
            const [etype, eid] = selected[0].value.split('|');
            const url = etype === 'Admin' ? 'edit_admin.php?aid=' : 'edit_staff.php?sid=';
            window.location.href = url + encodeURIComponent(eid);
        } else if (type === 'delete') {
            document.getElementById('bulk_action_type').value = 'delete';
            document.getElementById('modal-msg').innerText = `CRITICAL WARNING: Executing conditional deletion on ${selected.length} entity(ies). If relational traces exist, the entity will be deactivated instead of purged. Proceed?`;
            
            const modal = document.getElementById('decision-modal');
            const panel = document.getElementById('decision-panel');
            modal.classList.remove('hidden');
            void modal.offsetWidth;
            modal.classList.remove('opacity-0');
            panel.classList.remove('scale-95');
        }
    };

    const closeDecisionModal = () => {
        const modal = document.getElementById('decision-modal');
        const panel = document.getElementById('decision-panel');
        modal.classList.add('opacity-0');
        panel.classList.add('scale-95');
        setTimeout(() => { modal.classList.add('hidden'); }, 200);
    };

    document.getElementById('modal-confirm-btn').addEventListener('click', function() {
        this.innerHTML = 'Executing...';
        this.classList.add('opacity-70', 'cursor-not-allowed');
        document.getElementById('bulkActionForm').submit();
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