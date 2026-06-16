<?php
// File: admin/assign_inspector_detail.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

// Load repository dependency (Zero-SQL Principle)
require_once __DIR__ . '/../core/repositories/BookingRepository.php';
require_once __DIR__ . '/../core/repositories/PersonnelRepository.php';
use Core\Repositories\BookingRepository;
use Core\Repositories\PersonnelRepository;

/*
|--------------------------------------------------------------------------
| I: Repository Initialization & Mode Detection
|--------------------------------------------------------------------------
*/
$bookingRepo = new BookingRepository($conn);
$personnelRepo = new PersonnelRepository($conn);

$bid = intval($_GET['bid'] ?? 0);

if ($bid === 0) {
    die("Execution Fault: Invalid Booking ID.");
}

/*
|--------------------------------------------------------------------------
| D: Data Extraction & Dictionary Mapping
|--------------------------------------------------------------------------
*/
// Get full details (including user and venue)
$booking = $bookingRepo->getDetailedBookingById($bid);

if (!$booking) {
    die("Execution Fault: Booking Node not found.");
}

// Get inspector list
$inspectorsData = $personnelRepo->getInspectors();

// Build dropdown options (including default and auto-assign options)
$inspectorOptions = [
    '' => '-- Choose Inspector --',
    'RA01' => 'RA01 - Random Assignment (System Auto)'
];
foreach ($inspectorsData as $ins) {
    $inspectorOptions[$ins['sid']] = $ins['staff_name'] . ' (ID: ' . $ins['sid'] . ')';
}

/*
|--------------------------------------------------------------------------
| C: Configuration Definitions
|--------------------------------------------------------------------------
*/
$page_title = "Assign Inspector Detail";
$page_description = "Allocate personnel to booking nodes and verify physical asset states.";
$topbar_content = '
<div class="flex items-center">
    <a href="assign_inspector.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Bookings / Assign Inspector / Detail</h2>
</div>';

// Load special Fiori form style
$extra_css = [];

// Load form builder
require_once __DIR__ . '/../core/components/FioriFormBuilder.php';
use Core\Components\FioriFormBuilder as FB;

/*
|--------------------------------------------------------------------------
| V: View Rendering (State Binding via Builder)
|--------------------------------------------------------------------------
*/
ob_start();
?>

<form action="../actions/process_assign_inspector.php" method="POST" id="assignInspectorForm" class="bg-white rounded-lg shadow-sm border border-slate-200 relative mb-20">
    <input type="hidden" name="bid" value="<?php echo htmlspecialchars($bid); ?>">

    <div class="p-0">
        <div class="fiori-form-container">
            <div class="fiori-section-header">
                <h2 class="text-base font-bold text-[#1d2d3e]">Booking Evaluation Context</h2>
            </div>

            <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <div class="lg:col-span-6 space-y-4">
                    <h3 class="text-sm font-bold text-[#1d2d3e] mb-4">Node & Asset Parameters (Read-Only)</h3>
                    
                    <?php 
                    $readOnlyProps = ['readonly' => true, 'extra_css' => 'bg-slate-50 text-slate-500 cursor-not-allowed'];

                    echo FB::input('text', 'disp_bid', 'Booking ID', $booking['bid'], $readOnlyProps);
                    echo FB::input('text', 'disp_vname', 'Target Venue', $booking['vname'] ?? 'N/A', $readOnlyProps);
                    
 // Show deposit with special color
                    echo FB::input('text', 'disp_deposit', 'Deposit Required (RM)', number_format($booking['deposit'] ?? 0, 2), [
                        'readonly' => true,
                        'extra_css' => 'bg-slate-50 text-emerald-600 font-mono font-bold cursor-not-allowed'
                    ]);

 // If the booking table has other time fields ( start_date, end_date), extend here
                    if (isset($booking['start_date'])) {
                        echo FB::input('text', 'disp_start', 'Start Date/Time', $booking['start_date'], $readOnlyProps);
                    }
                    ?>
                </div>

                <div class="lg:col-span-6 space-y-4">
                    <h3 class="text-sm font-bold text-[#1d2d3e] mb-4">Requester Identity (Read-Only)</h3>
                    
                    <?php 
                    echo FB::input('text', 'disp_username', 'Student Name', $booking['username'] ?? 'N/A', $readOnlyProps);
                    echo FB::input('email', 'disp_email', 'Contact Email', $booking['email'] ?? 'N/A', $readOnlyProps);
                    echo FB::input('text', 'disp_status', 'Current Status', strtoupper($booking['status'] ?? 'PENDING'), [
                        'readonly' => true,
                        'extra_css' => 'bg-slate-50 text-[#004aad] font-bold cursor-not-allowed'
                    ]);
                    ?>
                </div>

                <div class="lg:col-span-12 border-t border-slate-100 pt-6 mt-2">
                    <h3 class="text-sm font-bold text-[#1d2d3e] mb-4">Execution Strategy</h3>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                        <div class="space-y-4">
                            <?php 
                            echo FB::select('sid', 'Select Allocation Strategy', $inspectorOptions, null, [
                                'required' => true,
                                'extra_css' => 'border-indigo-300 focus:border-indigo-500 font-bold text-indigo-700'
                            ]);
                            ?>
                        </div>
                        
                        <div class="p-4 bg-indigo-50 rounded-lg border border-indigo-100 flex items-start mt-6">
                            <i data-lucide="shield-info" class="w-5 h-5 text-indigo-600 mr-3 shrink-0"></i>
                            <p class="text-xs text-indigo-700 leading-relaxed font-medium">
                                Confirmed assignment will immediately update the <strong>Process Flow</strong> for this booking. The selected inspector will be authorized to conduct the post-event assessment.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="fixed bottom-0 right-0 w-full lg:w-[calc(100%-16rem)] bg-slate-50 border-t border-slate-200 p-4 px-6 flex justify-end space-x-3 z-50 shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.1)]">
        <button type="button" onclick="window.location.href='assign_inspector.php'" class="px-5 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-md hover:bg-slate-50 transition-colors">
            Discard
        </button>
        <button type="submit" id="submitBtn" class="px-5 py-2 text-sm font-semibold text-white bg-[#004aad] rounded-md hover:bg-[#003882] shadow-sm transition-colors flex items-center border border-[#004aad]">
            <i data-lucide="user-check" class="w-4 h-4 mr-2"></i> Confirm Assignment
        </button>
    </div>
</form>

<script>
    document.getElementById('assignInspectorForm').addEventListener('submit', function() {
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