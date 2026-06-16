<?php
// File: admin/register_venue.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

// Load repository dependency
require_once __DIR__ . '/../core/repositories/VenueRepository.php';
use Core\Repositories\VenueRepository;

/*
|--------------------------------------------------------------------------
| I: Repository Initialization & Mode Detection
|--------------------------------------------------------------------------
*/
$venueRepo = new VenueRepository($conn);

// Check page mode (State Machine Detection)
$vid_param = trim($_GET['vid'] ?? '');
$mode = !empty($vid_param) ? 'Update' : 'Create';
$venue = null;

/*
|--------------------------------------------------------------------------
| D: Data Extraction & Dictionary Mapping
|--------------------------------------------------------------------------
*/
// Getentityand, Zero-SQL then
if ($mode === 'Update') {
    $venue = $venueRepo->getVenueById($vid_param);
    if (!$venue) {
        die("Execution Fault: Venue Node not found.");
    }
}

// Use form options to get vcid instead of filter text
$categories = $venueRepo->getCategoryDictionary();

/*
|--------------------------------------------------------------------------
| C: Configuration Definitions
|--------------------------------------------------------------------------
*/
$page_title = "{$mode} Venue";
$page_description = "Configure physical asset parameters and visual properties.";
$topbar_content = '
<div class="flex items-center">
    <a href="venue_directory.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Venues / ' . $mode . ' Venue</h2>
</div>';

// Load special Fiori form style
$extra_css = [];

// Load new form builder
require_once __DIR__ . '/../core/components/FioriFormBuilder.php';
use Core\Components\FioriFormBuilder as FB;

/*
|--------------------------------------------------------------------------
| V: View Rendering (State Binding via Builder)
|--------------------------------------------------------------------------
*/
ob_start();
?>

<form action="../actions/process_venue.php" method="POST" enctype="multipart/form-data" id="registerVenueForm" class="bg-white rounded-lg shadow-sm border border-slate-200 relative">
    <input type="hidden" name="action" value="<?php echo strtolower($mode); ?>">
    <?php if ($mode === 'Update'): ?>
        <input type="hidden" name="original_vid" value="<?php echo htmlspecialchars($venue['vid']); ?>">
    <?php endif; ?>

    <div class="p-0">
        <div class="fiori-form-container">
            <div class="fiori-section-header">
                <h2 class="text-base font-bold text-[#1d2d3e]">Basic Data Parameters</h2>
            </div>

            <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <div class="lg:col-span-6 space-y-4">
                    <h3 class="text-sm font-bold text-[#1d2d3e] mb-4">Name and Identity</h3>
                    
                    <?php 
                    echo FB::input('text', 'vid', 'Venue ID', $venue['vid'] ?? '', [
                        'maxlength' => 10, 
                        'required' => true,
                        'readonly' => ($mode === 'Update'),
                        'placeholder' => 'e.g. MNBR2002',
                        'extra_css' => ($mode === 'Update') ? '' : 'font-mono uppercase'
                    ]); 

                    echo FB::input('text', 'vname', 'Venue Name', $venue['vname'] ?? '', [
                        'required' => true, 
                        'placeholder' => 'Full Name'
                    ]);

 // Convert data list to Builder key-value pairs
                    $catOptions = array_column($categories, 'category', 'vcid');
                    echo FB::select('vcid', 'Category', $catOptions, $venue['vcid'] ?? null, [
                        'required' => true,
                        'placeholder' => 'Select Category'
                    ]);
                    ?>
                </div>

                <div class="lg:col-span-6 space-y-4">
                    <h3 class="text-sm font-bold text-[#1d2d3e] mb-4">Capacity and Configuration</h3>
                    
                    <?php 
                    echo FB::input('number', 'max_cap', 'Max Capacity', $venue['max_cap'] ?? '', [
                        'min' => 1, 'required' => true, 'suffix' => 'PAX'
                    ]);

                    echo FB::input('number', 'deposit', 'Deposit', $venue['deposit'] ?? '', [
                        'step' => '0.01', 'min' => 0, 'required' => true, 'prefix' => 'RM',
                        'extra_css' => 'text-emerald-700 font-bold'
                    ]);

 // Status selector: use extra_css for color and attach onchange event
                    $statusColor = 'text-emerald-600';
                    if ($venue) {
                        $statusColor = ($venue['status'] === 'maintenance') ? 'text-red-600' : (($venue['status'] === 'closed') ? 'text-slate-600' : 'text-emerald-600');
                    }
                    echo "<div class=\"border-t border-slate-100 pt-4 mt-2\">" . FB::select('status', 'Operational State', [ 'available' => 'Available', 'maintenance' => 'Maintenance', 'closed' => 'Closed' ], $venue['status'] ?? 'available', [ 'extra_css' => "font-bold {$statusColor}", 'onchange' => "this.className = this.value === 'maintenance' ? 'fiori-input focus:border-[#004aad] appearance-none pr-8 bg-white cursor-pointer transition-colors font-bold text-red-600' : (this.value === 'closed' ? 'fiori-input focus:border-[#004aad] appearance-none pr-8 bg-white cursor-pointer transition-colors font-bold text-slate-600' : 'fiori-input focus:border-[#004aad] appearance-none pr-8 bg-white cursor-pointer transition-colors font-bold text-emerald-600')" ]) . "</div>";
                    ?>
                </div>
            </div>

    <div class="fixed bottom-0 right-0 w-full lg:w-[calc(100%-16rem)] bg-slate-50 border-t border-slate-200 p-4 px-6 flex justify-end space-x-3 z-50 shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.1)]">
        <button type="button" onclick="window.location.href='venue_directory.php'" class="px-5 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-md hover:bg-slate-50 transition-colors">
            Discard
        </button>
        <button type="submit" id="submitBtn" class="px-5 py-2 text-sm font-semibold text-white bg-[#004aad] rounded-md hover:bg-[#003882] shadow-sm transition-colors flex items-center border border-[#004aad]">
            <i data-lucide="save" class="w-4 h-4 mr-2"></i> <?php echo ($mode === 'Update') ? 'Apply Configuration' : 'Save Venue Record'; ?>
        </button>
    </div>
</form>

<script>
    const venuePics = document.getElementById('venue_pics');
    const fileList = document.getElementById('file-list');

    if (venuePics && fileList) {
        venuePics.addEventListener('change', function(e) {
            if (this.files.length > 0) {
                fileList.innerHTML = `${this.files.length} file(s) selected for upload.`;
            } else {
                fileList.innerHTML = '';
            }
        });
    }

    document.getElementById('registerVenueForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2 inline"></i> Processing...';
        btn.classList.add('opacity-70', 'cursor-not-allowed');
        btn.style.pointerEvents = 'none';
        lucide.createIcons();
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