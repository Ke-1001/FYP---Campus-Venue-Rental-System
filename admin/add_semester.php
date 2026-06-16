<?php
// This section prepares the admin add semester page.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';


require_once __DIR__ . '/../core/repositories/SemesterRepository.php';
use Core\Repositories\SemesterRepository;


$semesterRepo = new SemesterRepository($conn);

$sem_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mode = ($sem_id > 0) ? 'Update' : 'Create';
$semester = null;


if ($mode === 'Update')
{
    $semester = $semesterRepo->getSemesterById($sem_id);
    if (!$semester)
    {
        die("Execution Fault: Semester Vector not found.");
    }
}


$page_title = "{$mode} Semester";
$page_description = "Configure global temporal boundaries for academic operations.";
$topbar_content = '
<div class="flex items-center">
    <a href="semester_management.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Academic / ' . $mode . ' Semester</h2>
</div>';

$extra_css = [];

require_once __DIR__ . '/../core/components/FioriFormBuilder.php';
use Core\Components\FioriFormBuilder as FB;


ob_start();
?>

<form action="../actions/process_semester.php" method="POST" id="semesterForm" class="bg-white rounded-lg shadow-sm border border-slate-200 relative mb-20">
    <input type="hidden" name="action" value="<?php echo strtolower($mode); ?>">

    <div class="p-0">
        <div class="fiori-form-container">
            <div class="fiori-section-header">
                <h2 class="text-base font-bold text-[#1d2d3e]">Temporal Configuration</h2>
            </div>

            <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">

                <div class="lg:col-span-12 space-y-4">
                    <?php

                    $semIdOptions = [
                        'required' => true,
                        'placeholder' => 'e.g., 2610'
                    ];

                    if ($mode === 'Update')
                    {
                        $semIdOptions['readonly'] = true;
                        $semIdOptions['extra_css'] = 'font-mono font-bold text-indigo-700 bg-slate-50 cursor-not-allowed';
                    } else
                    {
                        $semIdOptions['min'] = '1000';
                        $semIdOptions['max'] = '9999';
                        $semIdOptions['extra_css'] = 'font-mono font-bold';
                    }

                    echo FB::input('number', 'sem_id', 'Semester Code (4 Digits)', $sem_id > 0 ? $sem_id : '', $semIdOptions);

                    if ($mode === 'Update')
                    {
                        echo '<p class="text-[10px] text-slate-400 mt-1 uppercase tracking-widest pl-1">* Primary Key is locked to prevent cascade failure.</p>';
                    }
                    ?>
                </div>

                <div class="lg:col-span-12 border-t border-slate-100 pt-6 mt-2">
                    <?php
                    echo FB::input('text', 'sem_name', 'Semester Nomenclature', $semester['sem_name'] ?? '', [
                        'required' => true,
                        'placeholder' => 'e.g., 2026 Trimester 1'
                    ]);
                    ?>
                </div>

                <div class="lg:col-span-6 space-y-4">
                    <?php
                    echo FB::input('date', 'start_date', 'Start Boundary', $semester['start_date'] ?? '', [
                        'required' => true,
                        'extra_css' => 'font-mono text-emerald-700 font-bold'
                    ]);
                    ?>
                </div>

                <div class="lg:col-span-6 space-y-4">
                    <?php
                    echo FB::input('date', 'end_date', 'End Boundary', $semester['end_date'] ?? '', [
                        'required' => true,
                        'extra_css' => 'font-mono text-red-600 font-bold'
                    ]);
                    ?>
                </div>

                <div class="lg:col-span-12 border-t border-slate-100 pt-6 mt-2">
                    <label class="flex items-center space-x-3 cursor-pointer p-4 bg-slate-50 rounded-lg border border-slate-200 hover:bg-slate-100 transition-colors">
                        <input type="checkbox" name="is_active" value="1" <?php echo ($semester && $semester['is_active'] == 1) ? 'checked' : ''; ?> class="w-5 h-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                        <div>
                            <span class="block text-sm font-bold text-slate-800">Set as Active Semester</span>
                            <span class="block text-[10px] font-medium text-slate-500 uppercase tracking-widest mt-0.5">Warning: This will dynamically deactivate all other semesters.</span>
                        </div>
                    </label>
                </div>

            </div>
        </div>
    </div>

    <div class="fixed bottom-0 right-0 w-full lg:w-[calc(100%-16rem)] bg-slate-50 border-t border-slate-200 p-4 px-6 flex justify-end space-x-3 z-50 shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.1)]">
        <button type="button" onclick="window.location.href='semester_management.php'" class="px-5 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-md hover:bg-slate-50 transition-colors">
            Discard
        </button>
        <button type="submit" id="submitBtn" class="px-5 py-2 text-sm font-semibold text-white bg-[#004aad] rounded-md hover:bg-[#003882] shadow-sm transition-colors flex items-center border border-[#004aad]">
            <i data-lucide="save" class="w-4 h-4 mr-2 inline"></i> <?php echo ($mode === 'Update') ? 'Commit Update' : 'Deploy Semester'; ?>
        </button>
    </div>
</form>

<script>
    document.getElementById('semesterForm').addEventListener('submit', function()
    {
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2 inline"></i> Executing...';
        btn.classList.add('opacity-70', 'cursor-not-allowed');
        btn.style.pointerEvents = 'none';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php
$page_content = ob_get_clean();


require_once __DIR__ . '/../core/layout.php';
?>
