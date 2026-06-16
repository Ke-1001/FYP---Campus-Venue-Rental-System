<?php
// File: admin/manage_vcategory.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

// Load core OOP components and repository
require_once __DIR__ . '/../core/components/FilterBuilder.php';
require_once __DIR__ . '/../core/components/DataGridBuilder.php';
require_once __DIR__ . '/../core/components/FioriFormBuilder.php';
require_once __DIR__ . '/../core/repositories/CategoryRepository.php';

use Core\Components\FilterBuilder;
use Core\Components\DataGridBuilder;
use Core\Components\FioriFormBuilder as FB;
use Core\Repositories\CategoryRepository;

/*
|--------------------------------------------------------------------------
| I: Repository Initialization & Mode Detection
|--------------------------------------------------------------------------
*/
$categoryRepo = new CategoryRepository($conn);

// Check page mode
$vcid_param = isset($_GET['vcid']) ? (int)$_GET['vcid'] : 0;
$mode = ($vcid_param > 0) ? 'Update' : 'Create';
$category_entity = null;

if ($mode === 'Update') {
    $category_entity = $categoryRepo->getCategoryById($vcid_param);
    if (!$category_entity) die("Execution Fault: Category Node not found.");
}

/*
|--------------------------------------------------------------------------
| D: DataGrid Extraction & Filter Topology
|--------------------------------------------------------------------------
*/
$filterBuilder = new FilterBuilder('manage_vcategory.php', true);
$filterBuilder->addField('text', 'f_query', 'Search', [], 'Category Name...', 'category', 'LIKE');

$result = $categoryRepo->getAllCategories($filterBuilder);

/*
|--------------------------------------------------------------------------
| E: Preemptive Error Interception (Constraint Guard)
|--------------------------------------------------------------------------
*/
$constraint_error = '';
if (isset($_SESSION['error']) && strpos($_SESSION['error'], 'Failed to Delete') !== false) {
 // Get error data and clear session to stop layout.php toast
    $constraint_error = $_SESSION['error'];
    unset($_SESSION['error']); 
}

/*
|--------------------------------------------------------------------------
| C: Configuration Definitions
|--------------------------------------------------------------------------
*/
$page_title = "Manage Venue Categories";
$page_description = "Define systemic categories for venue classification. Execute modifications or batch removals.";
$topbar_content = '
<div class="flex items-center">
    <a href="venue_directory.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Venues / Categories</h2>
</div>';

$extra_css = [];

// DataGrid config (lower visual grid)
$gridBuilder = new DataGridBuilder('vcid', '../actions/process_vcategory.php', 'category entity');
$gridBuilder->setCreateAction('manage_vcategory.php', 'Reset Form Mode') // point to itself to clear update mode
    ->setRowActionUrl('manage_vcategory.php?vcid=%s')
 ->disableAction('create') // Disable create button to force form submit
    ->addColumn('vcid', 'ID', 'text_mono', ['width' => 'w-24 text-center'])
    ->addColumn('category', 'Category Name', 'text_bold', ['width' => 'w-64'])
    ->addColumn('description', 'Parameters / Description', 'text_muted_mono', []);

$controller_config = [
    'edit_url_base' => 'manage_vcategory.php?vcid=',
    'delete_entity_name' => 'category entity'
];
require_once __DIR__ . '/../core/components/datagrid_controller.php';

/*
|--------------------------------------------------------------------------
| V: Hybrid View Rendering
|--------------------------------------------------------------------------
*/
ob_start();
?>

<form action="../actions/process_vcategory.php" method="POST" id="categoryForm" class="bg-white rounded-lg shadow-sm border border-slate-200 relative mb-8">
    <input type="hidden" name="action" value="<?php echo strtolower($mode); ?>">
    
    <div class="p-0">
        <div class="fiori-form-container border-none shadow-none">
            <div class="fiori-section-header bg-slate-50 border-b border-slate-200">
                <h2 class="text-base font-bold text-[#1d2d3e]"><i data-lucide="<?php echo $mode === 'Update' ? 'edit' : 'plus-circle'; ?>" class="w-4 h-4 inline mr-1 pb-0.5 text-[#004aad]"></i> <?php echo $mode; ?> Matrix Node</h2>
            </div>

            <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">
                <div class="lg:col-span-8 space-y-4">
                    <?php 
 // In update mode, show read-only ID (as visual anchor)
                    if ($mode === 'Update') {
                        echo '<input type="hidden" name="vcid" value="' . $category_entity['vcid'] . '">';
                        echo FB::input('text', 'dummy_id', 'Category ID', $category_entity['vcid'], [
                            'readonly' => true,
                            'extra_css' => 'font-mono text-slate-400 bg-slate-50 cursor-not-allowed'
                        ]);
                    }

                    echo FB::input('text', 'category', 'Category Name', $category_entity['category'] ?? '', [
                        'required' => true, 
                        'placeholder' => 'e.g. Computer Laboratory'
                    ]);

                    echo FB::textarea('description', 'Description', $category_entity['description'] ?? '', [
                        'required' => true, 
                        'placeholder' => 'Define the attributes of this category...'
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-slate-50 border-t border-slate-200 p-4 flex justify-end space-x-3 rounded-b-lg">
        <?php if ($mode === 'Update'): ?>
            <button type="button" onclick="window.location.href='manage_vcategory.php'" class="px-5 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-md hover:bg-slate-50 transition-colors">
                Cancel Edit
            </button>
        <?php endif; ?>
        <button type="submit" id="submitBtn" class="px-5 py-2 text-sm font-semibold text-white bg-[#004aad] rounded-md hover:bg-[#003882] shadow-sm transition-colors flex items-center border border-[#004aad]">
            <i data-lucide="save" class="w-4 h-4 mr-2"></i> <?php echo ($mode === 'Update') ? 'Apply Synchronization' : 'Commit New Category'; ?>
        </button>
    </div>
</form>

<script>
    document.getElementById('categoryForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2 inline"></i> Executing...';
        btn.classList.add('opacity-70', 'cursor-not-allowed');
    });
</script>


<?php echo $gridBuilder->render($result); ?>

<div id="constraint-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] hidden flex items-center justify-center transition-opacity opacity-0">
    <div class="bg-white rounded-md shadow-2xl border border-slate-200 w-full max-w-md p-6 transform scale-95 transition-transform" id="constraint-panel">
        
        <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-50 rounded-full border border-red-100">
            <i data-lucide="shield-alert" class="w-6 h-6 text-red-600"></i>
        </div>
        
        <h3 class="text-lg font-black text-slate-800 mb-2 text-center tracking-tight">Failed to Delete Category</h3>
        
        <p class="text-sm text-slate-600 font-medium leading-relaxed mb-6 text-center bg-slate-50 p-3 rounded border border-slate-100" id="constraint-msg">
            </p>
        
        <div class="flex justify-center">
            <button type="button" onclick="closeConstraintModal()" class="px-6 py-2.5 text-sm font-semibold text-white rounded-md shadow-sm transition-colors bg-slate-800 hover:bg-slate-700 focus:ring-2 focus:ring-slate-400 focus:ring-offset-1">
                Acknowledge & Close
            </button>
        </div>
    </div>
</div>

<script>
 // Close modal function
    const closeConstraintModal = () => {
        const modal = document.getElementById('constraint-modal');
        const panel = document.getElementById('constraint-panel');
        modal.classList.add('opacity-0');
        panel.classList.add('scale-95');
 setTimeout(() => { modal.classList.add('hidden'); }, 200); // match Tailwind transition
    };

 // Conditional trigger (Conditional Trigger Engine)
    <?php if (!empty($constraint_error)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('constraint-modal');
        const panel = document.getElementById('constraint-panel');
        const msgNode = document.getElementById('constraint-msg');
        
 // Map PHP variables to DOM
        msgNode.innerText = <?php echo json_encode(str_replace('System Fault: ', '', $constraint_error)); ?>;
        
 // Remove hidden state
        modal.classList.remove('hidden');
        
 // Force browser reflow for animation (Critical rendering path)
        void modal.offsetWidth; 
        
 // Start visual transition
        modal.classList.remove('opacity-0');
        panel.classList.remove('scale-95');
        
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
    <?php endif; ?>
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