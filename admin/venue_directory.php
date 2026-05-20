<?php
// File: admin/venue_directory.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../core/components/datagrid.php';
require_once __DIR__ . '/../core/components/filter_engine.php';

/*
|--------------------------------------------------------------------------
| D: Data Retrieval & Filter Logic
|--------------------------------------------------------------------------
*/
$filter_name = trim($_GET['f_name'] ?? '');
$filter_cat = trim($_GET['f_cat'] ?? '');
$filter_status = trim($_GET['f_status'] ?? '');


$cat_dict_sql = "SELECT DISTINCT UPPER(TRIM(category)) AS category_name FROM vcategory ORDER BY category_name ASC";
$cat_dict_result = $conn->query($cat_dict_sql);


$sql = "SELECT v.*, vc.category AS venue_category 
        FROM venue v 
        JOIN vcategory vc ON v.vcid = vc.vcid 
        WHERE 1=1";

if (!empty($filter_name)) {
    $sql .= " AND v.vname LIKE '%" . $conn->real_escape_string($filter_name) . "%'";
}
if (!empty($filter_cat)) {
    $sanitized_cat = strtoupper($filter_cat);
    $sql .= " AND UPPER(vc.category) LIKE '%" . $conn->real_escape_string($sanitized_cat) . "%'";
}
if (!empty($filter_status)) {
    $sql .= " AND v.status = '" . $conn->real_escape_string($filter_status) . "'";
}
$sql .= " ORDER BY v.vname ASC";
$result = $conn->query($sql);



/*
|--------------------------------------------------------------------------
| C: Configuration & DataGrid Schema
|--------------------------------------------------------------------------
*/
$page_title = "Venue Directory";
$page_description = "Search, filter, and manage existing physical assets.";
$topbar_content = '
<div class="flex items-center">
    <a href="manage_venues.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Asset Management / Directory</h2>
</div>';

$extra_css = ["../assets/css/fiori_forms.css", "../assets/css/table.css"];
$extra_js = [];

// ∴ 数据网格拓扑定义: 多元状态映射与财务单位绑定
$datagrid_schema = [
    'enable_checkbox' => true,
    'primary_key' => 'vid',
    'checkbox_name' => 'selected_vids',
    'row_action_url' => 'register_venue.php?vid=%s',
    'columns' => [
        ['key' => 'vid', 'label' => 'Identifier', 'type' => 'text_muted_mono', 'width' => 'w-32'],
        ['key' => 'vname', 'label' => 'Venue Name', 'type' => 'link', 'url_format' => 'register_venue.php?vid=%s', 'width' => 'w-48'],
        ['key' => 'venue_category', 'label' => 'Category', 'type' => 'badge', 'width' => 'w-40'],
        ['key' => 'max_cap', 'label' => 'Capacity', 'type' => 'suffix_text', 'suffix' => 'Pax', 'width' => ''],
        ['key' => 'deposit', 'label' => 'Deposit', 'type' => 'currency', 'prefix' => 'RM ', 'width' => ''],
        ['key' => 'status', 'label' => 'Current State', 'type' => 'map_badge', 'width' => 'w-40',
            'map' => [
                'available' => ['label' => 'Available', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                'maintenance' => ['label' => 'Maintenance', 'class' => 'bg-red-50 text-red-700 border-red-200'],
                'booked' => ['label' => 'Booked', 'class' => 'bg-blue-50 text-blue-700 border-blue-200']
            ],
            'default_map' => ['label' => 'Unknown', 'class' => 'bg-slate-50 text-slate-600 border-slate-200']
        ]
    ]
];

$controller_config = [
    'edit_url_base' => 'register_venue.php?vid=',
    'delete_entity_name' => 'venue'
];
require_once __DIR__ . '/../core/components/datagrid_controller.php';

// 提取 Datalist 选项数组
$cat_options = [];
if ($cat_dict_result && $cat_dict_result->num_rows > 0) {
    while ($dict = $cat_dict_result->fetch_assoc()) {
        $cat_options[] = $dict['category_name'];
    }
}

// ∴ 定义 Filter Schema
$filter_schema = [
    'action' => 'venue_directory.php',
    'show_submit_btn' => true,
    'fields' => [
        [
            'type' => 'text',
            'name' => 'f_name',
            'label' => 'Venue',
            'value' => $filter_name,
            'placeholder' => 'Search name...'
        ],
        [
            'type' => 'datalist',
            'name' => 'f_cat',
            'label' => 'Category',
            'value' => $filter_cat,
            'placeholder' => 'Keyword...',
            'options' => $cat_options
        ],
        [
            'type' => 'select',
            'name' => 'f_status',
            'label' => 'State',
            'value' => $filter_status,
            'placeholder' => 'All States',
            'auto_submit' => false,
            'options' => [
                'available' => 'Available',
                'maintenance' => 'Maintenance',
                'booked' => 'Booked'
            ]
        ]
    ]
];

/*
|--------------------------------------------------------------------------
| V: View Rendering (Output Buffer)
|--------------------------------------------------------------------------
*/
ob_start();
?>

<?php echo render_filter_engine($filter_schema); ?>

<div class="mb-4 bg-white p-3 rounded-md border border-slate-200 shadow-[0_1px_2px_0_rgba(0,0,0,0.05)] shrink-0 flex justify-between items-center">
    <div class="text-xs font-bold text-slate-500 pl-2">
        <span id="cb-counter">0</span> selected
    </div>
    <div class="flex space-x-2">
        <button onclick="window.location.href='register_venue.php'" class="px-4 py-2 text-xs font-semibold text-white bg-[#004aad] hover:bg-[#003882] rounded-md shadow-sm transition border border-[#004aad]">
            <i data-lucide="plus" class="w-3.5 h-3.5 inline mr-1"></i> Create Venue
        </button>
        <button id="btn-edit" disabled onclick="executeAction('edit')" class="px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200">
            <i data-lucide="edit-3" class="w-3.5 h-3.5 inline mr-1"></i> Edit
        </button>
        <button id="btn-delete" disabled onclick="executeAction('delete')" class="px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200">
            <i data-lucide="trash-2" class="w-3.5 h-3.5 inline mr-1"></i> Delete
        </button>
    </div>
</div>

<form id="bulkActionForm" action="../actions/process_venue.php" method="POST" class="flex-1 overflow-hidden flex flex-col custom-table-container">
    <input type="hidden" name="action" id="bulk_action_type" value="">
    <div class="flex-1 overflow-y-auto">
        <?php echo render_datagrid($datagrid_schema, $result); ?>
    </div>
</form>

<div id="custom-delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm opacity-0 transition-opacity duration-200">
    <div id="custom-modal-panel" class="bg-white rounded-md shadow-lg w-full max-w-sm p-6 transform scale-95 transition-transform duration-200 border border-slate-200">
        <h3 class="text-base font-bold text-slate-900 mb-2">Delete Asset</h3>
        <p class="text-sm text-slate-600 mb-6" id="custom-modal-msg">Are you sure you want to delete the selected venue(s)?</p>
        <div class="flex justify-end space-x-3">
            <button type="button" onclick="closeCustomModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-transparent hover:bg-slate-100 rounded-md transition-colors">Cancel</button>
            <button type="button" id="custom-modal-confirm-btn" class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-md shadow-sm transition-colors border border-red-600">Delete</button>
        </div>
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
