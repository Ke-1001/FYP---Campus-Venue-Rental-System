<?php
// This section prepares the admin add exclusion page.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';


require_once __DIR__ . '/../core/repositories/ScheduleRepository.php';
use Core\Repositories\ScheduleRepository;


$scheduleRepo = new ScheduleRepository($conn);

$sch_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mode = ($sch_id > 0) ? 'Update' : 'Add';
$exclusion = null;


if ($mode === 'Update')
{
    $exclusion = $scheduleRepo->getScheduleById($sch_id);
    if (!$exclusion)
    {
        die("Execution Fault: Schedule Node not found.");
    }
}


$venuesData = $scheduleRepo->getVenuesForDropdown();
$venueOptions = ['' => 'Select Venue'];
foreach ($venuesData as $v)
{
    $venueOptions[$v['vid']] = "[{$v['vid']}] " . $v['vname'];
}

$semestersData = $scheduleRepo->getSemestersForDropdown();
$semOptions = ['' => 'Select Semester'];
$activeSemId = null;
foreach ($semestersData as $sem)
{
    $semOptions[$sem['sem_id']] = $sem['sem_name'] . ($sem['is_active'] ? ' (Active)' : '');
    if ($sem['is_active'])
    {
        $activeSemId = $sem['sem_id'];
    }
}

$dayOptions = [
    'Monday' => 'Monday', 'Tuesday' => 'Tuesday', 'Wednesday' => 'Wednesday',
    'Thursday' => 'Thursday', 'Friday' => 'Friday', 'Saturday' => 'Saturday', 'Sunday' => 'Sunday'
];


$page_title = "{$mode} Schedule";
$page_description = "Configure academic class schedule exclusions.";
$topbar_content = '
<div class="flex items-center">
    <a href="academic_schedule.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Academic / ' . $mode . ' Schedule</h2>
</div>';

$extra_css = [];

require_once __DIR__ . '/../core/components/FioriFormBuilder.php';
use Core\Components\FioriFormBuilder as FB;


ob_start();
?>

<div class="mb-24 space-y-8">

    <form action="../actions/process_schedule.php" method="POST" id="exclusionForm" class="bg-white rounded-lg shadow-sm border border-slate-200 relative">
        <input type="hidden" name="action" value="<?php echo ($mode === 'Add') ? 'create' : 'update'; ?>">
        <?php if ($mode === 'Update'): ?>
            <input type="hidden" name="sch_id" value="<?php echo htmlspecialchars($sch_id); ?>">
        <?php endif; ?>

        <div class="fiori-form-container">
            <div class="fiori-section-header">
                <h2 class="text-base font-bold text-[#1d2d3e]">Basic Info (Single Entry)</h2>
            </div>

            <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">
                <div class="lg:col-span-6 space-y-4">
                    <?php
                    echo FB::input('text', 'subject', 'Subject Name', $exclusion['subject_name'] ?? '', [
                        'required' => true,
                        'placeholder' => 'e.g., Data Structures'
                    ]);


                    $defaultSem = $exclusion ? $exclusion['sem_id'] : $activeSemId;
                    echo FB::select('sem_id', 'Semester', $semOptions, $defaultSem, [
                        'required' => true,
                        'extra_css' => 'font-bold text-[#004aad]'
                    ]);
                    ?>
                </div>

                <div class="lg:col-span-6 space-y-4">
                    <?php
                    echo FB::select('vid', 'Venue', $venueOptions, $exclusion['vid'] ?? null, [
                        'required' => true,
                        'extra_css' => 'font-bold text-slate-700'
                    ]);

                    echo FB::select('day_of_week', 'Day', $dayOptions, $exclusion['day_of_week'] ?? null, [
                        'required' => true,
                        'extra_css' => 'font-bold text-slate-700'
                    ]);
                    ?>

                    <div class="grid grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                        <?php
                        echo FB::input('time', 'start_time', 'Start Time', $exclusion['start_time'] ?? '08:00', [
                            'required' => true,
                            'extra_css' => 'font-mono font-bold text-emerald-600'
                        ]);

                        echo FB::input('time', 'end_time', 'End Time', $exclusion['end_time'] ?? '10:00', [
                            'required' => true,
                            'extra_css' => 'font-mono font-bold text-red-600'
                        ]);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <?php if ($mode === 'Add'): ?>
    <form action="../actions/process_schedule.php" method="POST" enctype="multipart/form-data" id="csvSubmitForm" class="bg-white rounded-lg shadow-sm border border-slate-200 relative">
        <input type="hidden" name="action" value="batch_import">

        <div class="fiori-form-container">
            <div class="fiori-section-header flex justify-between items-center bg-slate-50">
                <h2 class="text-base font-bold text-[#1d2d3e]">Batch Import</h2>
                <span class="text-[10px] font-black bg-emerald-100 text-emerald-700 px-2 py-1 rounded uppercase tracking-widest border border-emerald-200">Mass Upload</span>
            </div>

            <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">
                <div class="lg:col-span-6 space-y-4">
                    <?php
                    echo FB::select('sem_id', 'Target Semester', $semOptions, $activeSemId, [
                        'required' => true,
                        'extra_css' => 'font-bold text-slate-700'
                    ]);
                    ?>
                </div>

                <div class="lg:col-span-6 space-y-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">CSV Data File</label>
                    <div class="flex items-center space-x-4 w-full">
                        <div class="flex-1 relative">
                            <input type="file" name="csv_file" accept=".csv" required class="absolute inset-0 opacity-0 cursor-pointer z-10 w-full h-full" onchange="document.getElementById('csv-name-display').innerText = this.files[0].name">
                            <div class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-sm text-slate-500 font-mono flex justify-between items-center hover:border-emerald-500 transition-colors">
                                <span id="csv-name-display" class="truncate pr-2">Choose CSV file...</span>
                                <i data-lucide="upload" class="w-4 h-4 text-slate-400 shrink-0"></i>
                            </div>
                        </div>
                        <button type="submit" id="btnCsvSubmit" class="px-6 py-3 bg-emerald-600 text-white text-sm font-bold rounded-md shadow-sm hover:bg-emerald-700 transition-colors flex items-center justify-center min-w-[140px]">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4 mr-2"></i> Import Data
                        </button>
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 mt-2 uppercase tracking-widest">* Format strictly requires: Venue ID, Day, Start Time, End Time, Subject Name.</p>
                </div>
            </div>
        </div>
    </form>
    <?php endif; ?>

</div>

<div class="fixed bottom-0 right-0 w-full lg:w-[calc(100%-16rem)] bg-slate-50 border-t border-slate-200 p-4 px-6 flex justify-end space-x-3 z-50 shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.1)]">
    <button type="button" onclick="window.location.href='academic_schedule.php'" class="px-5 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-md hover:bg-slate-50 transition-colors">
        Cancel
    </button>
    <button type="submit" form="exclusionForm" id="submitBtn" class="px-5 py-2 text-sm font-semibold text-white bg-[#004aad] rounded-md hover:bg-[#003882] shadow-sm transition-colors flex items-center border border-[#004aad]">
        <i data-lucide="save" class="w-4 h-4 mr-2 inline"></i> <?php echo ($mode === 'Update') ? 'Save Changes' : 'Add Single Entry'; ?>
    </button>
</div>

<script>
    document.getElementById('exclusionForm').addEventListener('submit', function()
    {
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2 inline"></i> Saving...';
        btn.classList.add('opacity-70', 'cursor-not-allowed');
        btn.style.pointerEvents = 'none';
        lucide.createIcons();
    });

    const csvForm = document.getElementById('csvSubmitForm');
    if (csvForm)
    {
        csvForm.addEventListener('submit', function()
        {
            const btn = document.getElementById('btnCsvSubmit');
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2 inline"></i> Importing...';
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            btn.style.pointerEvents = 'none';
            lucide.createIcons();
        });
    }
</script>

<?php
$page_content = ob_get_clean();


require_once __DIR__ . '/../core/layout.php';
?>
