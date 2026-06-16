<?php
// This section prepares the admin execute inspection page.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../core/repositories/InspectionRepository.php';
use Core\Repositories\InspectionRepository;
$inspectionRepo = new InspectionRepository($conn);
$bid = intval($_GET['bid'] ?? 0);
if ($bid === 0)
{
    die("Execution Fault: Invalid Booking Identifier.");
}
$data = $inspectionRepo->getPendingInspectionDetailById($bid);
$data = $inspectionRepo->getPendingInspectionDetailById($bid);
if (!$data)
{
    die("Execution Fault: No pending inspection record found for this Reference ID.");
}
$stmt_waiver = $conn->prepare("
    SELECT damage_description, admin_remark, damage_photo
    FROM damage_report
    WHERE bid = ? AND report_status = 'reviewed'
    LIMIT 1
");
$stmt_waiver->bind_param("i", $bid);
$stmt_waiver->execute();
$res_waiver = $stmt_waiver->get_result();
$waiver_data = $res_waiver->num_rows > 0 ? $res_waiver->fetch_assoc() : null;
$stmt_waiver->close();
if (!$data)
{
    die("Execution Fault: No pending inspection record found for this Reference ID.");
}
$tz = new DateTimeZone('Asia/Kuala_Lumpur');
$now = new DateTime('now', $tz);
$start_dt = new DateTime($data['date_booked'] . ' ' . $data['time_start'], $tz);
$end_dt = new DateTime($data['date_booked'] . ' ' . $data['time_end'], $tz);
$execution_state = 'awaiting';
if ($now >= $end_dt || $data['status'] === 'completed')
{
    $execution_state = 'ready';
}
elseif ($now >= $start_dt && $now < $end_dt)
{
    $execution_state = 'in_use';
}
if ($execution_state !== 'ready')
{
    header("Location: pending_inspections.php?err=temporal&state=" . urlencode($execution_state));
    exit;
}
$page_title = "Execute Assessment";
$page_description = "Conduct post-usage verification and deposit settlement.";
$topbar_content = '
<div class="flex items-center">
    <a href="pending_inspections.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Operations / Execute Inspection / BID: ' . $bid . '</h2>
</div>';
$extra_css = [];
require_once __DIR__ . '/../core/components/FioriFormBuilder.php';
use Core\Components\FioriFormBuilder as FB;
ob_start();
?>
<form action="../actions/process_inspection.php" method="POST" id="inspectionForm" class="bg-white rounded-lg shadow-sm border border-slate-200 relative mb-20">
    <input type="hidden" name="bid" value="<?php echo htmlspecialchars($bid); ?>">
    <div class="p-0">
        <div class="fiori-form-container">
            <div class="fiori-section-header">
                <h2 class="text-base font-bold text-[#1d2d3e]">Post-Usage Assessment Protocol</h2>
            </div>
            <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">
                <div class="lg:col-span-6 space-y-4">
                    <h3 class="text-sm font-bold text-[#1d2d3e] mb-4">Reference Context (Read-Only)</h3>
                    <?php
                    $readOnlyProps = ['readonly' => true, 'extra_css' => 'bg-slate-50 text-slate-500 cursor-not-allowed'];
                    echo FB::input('text', 'disp_vname', 'Venue Details', $data['vname'] . ' [' . $data['venue_category'] . ']', $readOnlyProps);
                    echo FB::input('text', 'disp_user', 'Allocated User', $data['username'], $readOnlyProps);
                    echo FB::input('text', 'disp_deposit', 'Held Deposit', number_format($data['deposit'], 2), [
                        'readonly' => true,
                        'prefix' => 'RM',
                        'extra_css' => 'bg-slate-50 text-emerald-700 font-bold font-mono cursor-not-allowed'
                    ]);
                    ?>
                    <?php if ($waiver_data): ?>
                    <div class="mt-6 border-2 border-amber-400 bg-amber-50 rounded-lg p-4 shadow-sm animate-pulse-once relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-amber-500"></div>
                        <h4 class="text-sm font-black text-amber-800 flex items-center mb-3 uppercase tracking-wider">
                            <i data-lucide="triangle-alert" class="w-4 h-4 mr-2"></i>
                            Pre-existing Damage Waiver
                        </h4>
                        <div class="space-y-3">
                            <div>
                                <span class="block text-[10px] font-bold text-amber-600 uppercase">User Report</span>
                                <p class="text-xs font-semibold text-slate-700 mt-1 leading-relaxed border-l-2 border-amber-300 pl-2">
                                    <?php echo htmlspecialchars($waiver_data['damage_description']); ?>
                                </p>
                            </div>
                            <?php if (!empty($waiver_data['admin_remark'])): ?>
                            <div>
                                <span class="block text-[10px] font-bold text-amber-600 uppercase">Admin Remark</span>
                                <p class="text-xs font-bold text-slate-800 mt-1 bg-amber-100/50 p-2 rounded border border-amber-200">
                                    <?php echo htmlspecialchars($waiver_data['admin_remark']); ?>
                                </p>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($waiver_data['damage_photo'])): ?>
                            <div class="pt-2 border-t border-amber-200/50">
                                <a href="../uploads/damage_reports/<?php echo rawurlencode($waiver_data['damage_photo']); ?>" target="_blank" class="inline-flex items-center text-xs font-bold text-amber-700 hover:text-amber-900 transition-colors">
                                    <i data-lucide="image" class="w-4 h-4 mr-1"></i> View Photographic Evidence
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="lg:col-span-6 space-y-4">
                    <h3 class="text-sm font-bold text-[#1d2d3e] mb-4">Assessment Submission</h3>
                    <?php
                    echo FB::select('ins_status', 'Final Condition', [
                        'passed' => 'Passed (Perfect Condition - Full Refund)',
                        'failed' => 'Failed (Damages/Dirty - Apply Penalties)'
                    ], 'passed', [
                        'required' => true,
                        'extra_css' => 'font-bold text-emerald-600 border-emerald-300 focus:border-emerald-500'
                    ]);
                    ?>
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Observations</label>
                        <textarea name="damage_desc" id="damage_desc" rows="3" class="fiori-input w-full resize-none p-3 transition-colors focus:ring-2 focus:ring-[#004aad]/20 focus:border-[#004aad] outline-none rounded-md border border-slate-300"></textarea>
                    </div>
                    <?php
                    echo FB::input('number', 'penalty', 'Assessed Penalty (RM)', '0.00', [
                        'step' => '0.01',
                        'min' => '0',
                        'prefix' => 'RM',
                        'extra_css' => 'font-mono transition-colors'
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="fixed bottom-0 right-0 w-full lg:w-[calc(100%-16rem)] bg-slate-50 border-t border-slate-200 p-4 px-6 flex justify-end space-x-3 z-50 shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.1)]">
        <button type="button" onclick="window.location.href='pending_inspections.php'" class="px-5 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-md hover:bg-slate-50 transition-colors">
            Cancel
        </button>
        <button type="submit" id="submit-btn" class="px-5 py-2 text-sm font-semibold text-white bg-[#004aad] rounded-md shadow-sm transition-colors flex items-center border border-[#004aad]">
            <i data-lucide="check-circle" class="w-4 h-4 mr-2"></i> Finalize Settle
        </button>
    </div>
</form>
<script>
    function evaluateFormState()
    {
        const selectBox = document.querySelector('select[name="ins_status"]');
        const desc = document.getElementById('damage_desc');
        const penalty = document.querySelector('input[name="penalty"]');
        const btn = document.getElementById('submit-btn');
        if (!selectBox || !desc || !penalty || !btn) return;  const status = selectBox.value;  if (status === 'passed')
        {
            selectBox.classList.remove('text-red-600', 'border-red-300');
            selectBox.classList.add('text-emerald-600', 'border-emerald-300');
            desc.readOnly = true;
            desc.value = '';
            desc.placeholder = "Venue is in standard condition.";
            desc.classList.add('bg-slate-50', 'text-slate-500', 'cursor-not-allowed');
            penalty.readOnly = true;
            penalty.value = '0.00';
            penalty.classList.remove('text-red-600', 'font-bold');
            penalty.classList.add('bg-slate-50', 'text-slate-400', 'cursor-not-allowed');
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-slate-400', 'border-slate-400');
            btn.classList.add('bg-[#004aad]', 'hover:bg-[#003882]', 'border-[#004aad]');
        }
        else
        {
            selectBox.classList.remove('text-emerald-600', 'border-emerald-300');
            selectBox.classList.add('text-red-600', 'border-red-300');
            desc.readOnly = false;
            desc.placeholder = "REQUIRED: Detail the identified issues...";
            desc.classList.remove('bg-slate-50', 'text-slate-500', 'cursor-not-allowed');
            penalty.readOnly = false;
            penalty.classList.remove('bg-slate-50', 'text-slate-400', 'cursor-not-allowed');
            penalty.classList.add('text-red-600', 'font-bold');
            const pVal = parseFloat(penalty.value);
            const isValid = (desc.value.trim() !== '') && (!isNaN(pVal) && pVal > 0);
            btn.disabled = !isValid;
            if (isValid)
            {
                btn.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-slate-400', 'border-slate-400');
                btn.classList.add('bg-[#004aad]', 'hover:bg-[#003882]', 'border-[#004aad]');
            }
            else
            {
                btn.classList.add('opacity-50', 'cursor-not-allowed', 'bg-slate-400', 'border-slate-400');
                btn.classList.remove('bg-[#004aad]', 'hover:bg-[#003882]', 'border-[#004aad]');
            }
        }
    }
    window.addEventListener('DOMContentLoaded', () =>
    {
        const selectBox = document.querySelector('select[name="ins_status"]');
        const desc = document.getElementById('damage_desc');
        const penalty = document.querySelector('input[name="penalty"]');
        if (selectBox) selectBox.addEventListener('change', evaluateFormState);
        if (desc) desc.addEventListener('input', evaluateFormState);
        if (penalty) penalty.addEventListener('input', evaluateFormState);
        evaluateFormState();
    }
);
    document.getElementById('inspectionForm').addEventListener('submit', function(e)
    {
        const btn = document.getElementById('submit-btn');
        if (btn.disabled)
        {
            e.preventDefault();
            return;
        }
        btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2 inline"></i> Processing...';
        btn.classList.add('opacity-70', 'cursor-not-allowed', 'pointer-events-none');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
);
</script>
<?php
$page_content = ob_get_clean();
require_once __DIR__ . '/../core/layout.php';
?>
