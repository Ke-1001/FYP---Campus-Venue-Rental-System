<?php
// File: admin/semester_management.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../core/components/datagrid.php';

/*
|--------------------------------------------------------------------------
| D: Data Retrieval & Filter Logic
|--------------------------------------------------------------------------
*/
$f_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : 'all';
$f_search = isset($_GET['filter_search']) ? trim($_GET['filter_search']) : '';

$sql = "SELECT * FROM semester_config WHERE 1=1";
if ($f_status === 'active') {
    $sql .= " AND is_active = 1";
} elseif ($f_status === 'inactive') {
    $sql .= " AND is_active = 0";
}
if (!empty($f_search)) {
    $sql .= " AND sem_name LIKE '%" . $conn->real_escape_string($f_search) . "%'";
}
$sql .= " ORDER BY start_date DESC";
$result = $conn->query($sql);

/*
|--------------------------------------------------------------------------
| C: Configuration & DataGrid Schema
|--------------------------------------------------------------------------
*/
$page_title = "Semester Management";
$page_description = "Manage academic terms, dates, and status settings.";
$topbar_content = '
<div class="flex items-center">
    <a href="academic.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Academic / Semester Management</h2>
</div>';
$extra_css = ["../assets/css/fiori_forms.css", "../assets/css/table.css"];
$extra_js = [];

// ∴ 数据网格拓扑定义 (DataGrid Schema Definition)
$datagrid_schema = [
    'enable_checkbox' => true,
    'primary_key' => 'sem_id',
    'checkbox_name' => 'selected_ids',
    'row_action_url' => 'add_semester.php?id=%s', // 注入行级双击路由
    'columns' => [
        ['key' => 'sem_id', 'label' => 'Semester ID', 'type' => 'text_muted_mono', 'prefix' => '#', 'width' => 'w-32'],
        ['key' => 'sem_name', 'label' => 'Semester Name', 'type' => 'link', 'url_format' => 'add_semester.php?id=%s', 'width' => ''],
        ['key' => 'start_date', 'label' => 'Start Date', 'type' => 'text_mono', 'width' => 'w-48'],
        ['key' => 'end_date', 'label' => 'End Date', 'type' => 'text_mono', 'width' => 'w-48'],
        ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean_badge', 'width' => 'w-32 text-center',
         'true_label' => 'Active', 'true_class' => 'text-emerald-700',
         'false_label' => 'Inactive', 'false_class' => 'text-slate-600']
    ]
];

/*
|--------------------------------------------------------------------------
| V: View Rendering (Output Buffer)
|--------------------------------------------------------------------------
*/
ob_start();
?>

<div class="mb-4 bg-white border border-slate-200 rounded-md p-3 shadow-[0_1px_2px_0_rgba(0,0,0,0.05)] shrink-0 flex flex-wrap items-center gap-4">
    <form method="GET" action="semester_management.php" id="filterForm" class="flex flex-1 items-center gap-4">
        <div class="flex items-center space-x-2">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Search:</label>
            <input type="text" name="filter_search" value="<?php echo htmlspecialchars($f_search); ?>" placeholder="e.g., 2610" class="border border-slate-200 rounded px-3 py-1.5 text-xs font-semibold focus:outline-none focus:border-[#004aad] w-48 bg-white">
        </div>
        <div class="flex items-center space-x-2">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Status:</label>
            <select name="filter_status" onchange="this.form.submit()" class="border border-slate-200 rounded px-3 py-1.5 text-xs font-bold text-slate-700 focus:outline-none focus:border-[#004aad] cursor-pointer bg-white">
                <option value="all" <?php echo $f_status === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="active" <?php echo $f_status === 'active' ? 'selected' : ''; ?>>Active Only</option>
                <option value="inactive" <?php echo $f_status === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
            </select>
        </div>
        <a href="semester_management.php" class="px-4 py-2 bg-transparent text-slate-600 text-sm font-semibold rounded-md hover:bg-slate-100 border border-transparent transition flex items-center justify-center ml-auto">
            Reset
        </a>
    </form>
</div>

<div class="mb-4 bg-white p-3 rounded-md border border-slate-200 shadow-[0_1px_2px_0_rgba(0,0,0,0.05)] shrink-0 flex justify-between items-center">
    <div class="text-xs font-bold text-slate-500 pl-2">
        <span id="cb-counter">0</span> selected
    </div>
    <div class="flex space-x-2">
        <button onclick="window.location.href='add_semester.php'" class="px-4 py-2 text-xs font-semibold text-white bg-[#004aad] hover:bg-[#003882] rounded-md shadow-sm transition border border-[#004aad]">
            <i data-lucide="plus" class="w-3.5 h-3.5 inline mr-1"></i> Create Semester
        </button>
        <button id="btn-edit" disabled onclick="executeAction('edit')" class="px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200">
            <i data-lucide="edit-3" class="w-3.5 h-3.5 inline mr-1"></i> Edit
        </button>
        <button id="btn-delete" disabled onclick="executeAction('delete')" class="px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200">
            <i data-lucide="trash-2" class="w-3.5 h-3.5 inline mr-1"></i> Delete
        </button>
    </div>
</div>

<form id="bulkActionForm" action="../actions/process_semester.php" method="POST" class="flex-1 overflow-hidden flex flex-col custom-table-container">
    <input type="hidden" name="action" id="bulk_action_type" value="">
    <div class="flex-1 overflow-y-auto">
        <?php echo render_datagrid($datagrid_schema, $result); ?>
    </div>
</form>

<div id="custom-delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm opacity-0 transition-opacity duration-200">
    <div id="custom-modal-panel" class="bg-white rounded-md shadow-lg w-full max-w-sm p-6 transform scale-95 transition-transform duration-200 border border-slate-200">
        <h3 class="text-base font-bold text-slate-900 mb-2">Delete Semester</h3>
        <p class="text-sm text-slate-600 mb-6" id="custom-modal-msg">Are you sure you want to delete the selected semester(s)?</p>
        <div class="flex justify-end space-x-3">
            <button type="button" onclick="closeCustomModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-transparent hover:bg-slate-100 rounded-md transition-colors">Cancel</button>
            <button type="button" id="custom-modal-confirm-btn" class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-md shadow-sm transition-colors border border-red-600">Delete</button>
        </div>
    </div>
</div>

<script>
    // 状态广播与联动计数计算
    const toggleAll = (source) => {
        document.querySelectorAll('.row-cb').forEach(cb => { cb.checked = source.checked; });
        updateButtonStates();
    };

    const updateButtonStates = () => {
        const selectedCount = document.querySelectorAll('.row-cb:checked').length;
        const btnEdit = document.getElementById('btn-edit');
        const btnDelete = document.getElementById('btn-delete');
        
        document.getElementById('cb-counter').innerText = selectedCount; // 同步外部计数器

        const isSingle = selectedCount === 1;
        const isMultiple = selectedCount > 0;

        btnEdit.disabled = !isSingle;
        btnEdit.className = isSingle 
            ? "px-4 py-2 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-md shadow-sm transition cursor-pointer"
            : "px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200";

        btnDelete.disabled = !isMultiple;
        btnDelete.className = isMultiple 
            ? "px-4 py-2 text-xs font-semibold text-[#dc2626] bg-white hover:bg-red-50 border border-red-200 rounded-md shadow-sm transition cursor-pointer"
            : "px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200";
    };

    // 动作路由分配器
    const executeAction = (type) => {
        const selected = document.querySelectorAll('.row-cb:checked');
        if (selected.length === 0) return;

        if (type === 'edit' && selected.length === 1) {
            window.location.href = `add_semester.php?id=${selected[0].value}`;
        } else if (type === 'delete') {
            document.getElementById('custom-modal-msg').innerText = `Are you sure you want to permanently delete the ${selected.length} selected semester(s)?`;
            openCustomModal();
        }
    };

    // 自定义模态框流体控制
    const openCustomModal = () => {
        const modal = document.getElementById('custom-delete-modal');
        const panel = document.getElementById('custom-modal-panel');
        modal.classList.remove('hidden');
        void modal.offsetWidth; // 触发强制重绘以保证动画帧序列
        modal.classList.remove('opacity-0');
        panel.classList.remove('scale-95');
    };

    const closeCustomModal = () => {
        const modal = document.getElementById('custom-delete-modal');
        const panel = document.getElementById('custom-modal-panel');
        modal.classList.add('opacity-0');
        panel.classList.add('scale-95');
        setTimeout(() => { modal.classList.add('hidden'); }, 200);
    };

    document.getElementById('custom-modal-confirm-btn').addEventListener('click', function() {
        const form = document.getElementById('bulkActionForm');
        document.getElementById('bulk_action_type').value = 'delete';
        this.innerHTML = 'Deleting...';
        this.disabled = true;
        form.submit();
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