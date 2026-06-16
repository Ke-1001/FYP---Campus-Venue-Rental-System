<?php
// This section prepares the admin pending requests page.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../core/components/datagrid.php';


require_once __DIR__ . '/../core/components/FilterBuilder.php';
require_once __DIR__ . '/../core/repositories/BookingRepository.php';

use Core\Components\FilterBuilder;
use Core\Repositories\BookingRepository;


$bookingRepo = new BookingRepository($conn);

$filterBuilder = new FilterBuilder('pending_requests.php', true);
$filterBuilder
    ->addField('text', 'f_bid', 'Ref ID', [], 'Search BID...', 'b.bid', 'LIKE')
    ->addField('text', 'f_student', 'Student Entity', [], 'Name or ID...', 'CONCAT(u.uid, " ", u.username)', 'LIKE')
    ->addField('text', 'f_venue', 'Asset', [], 'Name or Category...', 'CONCAT(v.vname, " ", vc.category)', 'LIKE')
    ->addField('date', 'f_date', 'Date', [], '', 'b.date_booked', '=');


$result = $bookingRepo->getPendingRequests($filterBuilder);


$page_title = "Pending Requests";
$page_description = "Review full details below and execute booking decisions efficiently via batch processing.";
$topbar_content = '
<div class="flex items-center">
    <a href="manage_bookings.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Bookings / Pending Requests</h2>
</div>';
$extra_css = [];


$datagrid_schema = [
    'enable_checkbox' => true,
    'primary_key' => 'bid',
    'checkbox_name' => 'bids',
    'row_action_url' => 'process_flow.php?bid=%s',
    'columns' => [
        ['key' => 'bid', 'label' => 'Reference', 'type' => 'link', 'url_format' => 'process_flow.php?bid=%s', 'width' => 'w-28'],
        ['key' => 'student_name', 'label' => 'Student', 'type' => 'text_bold', 'width' => 'w-36'],
        ['key' => 'venue_name', 'label' => 'Asset', 'type' => 'text_bold', 'width' => 'w-40'],
        ['key' => 'date_booked', 'label' => 'Reserved Date', 'type' => 'text_mono', 'width' => 'w-32'],
        ['type' => 'time_range', 'label' => 'Time', 'start_key' => 'time_start', 'end_key' => 'time_end', 'width' => 'w-32'],
        ['key' => 'deposit', 'label' => 'Deposit', 'type' => 'currency', 'prefix' => 'RM ', 'width' => 'w-28'],
        ['key' => 'purpose', 'label' => 'Declared Purpose', 'type' => 'text_muted_mono', 'width' => 'w-48']
    ]
];


ob_start();
?>

<?php echo $filterBuilder->render(); ?>

<div class="flex items-center justify-between mb-4 bg-white p-3 rounded-md border border-slate-200 shadow-[0_1px_2px_0_rgba(0,0,0,0.05)] shrink-0">
    <div class="text-xs font-bold text-slate-500 pl-2">
        <span id="cb-counter">0</span> selected
    </div>
    <div class="flex space-x-2">
        <button id="btn-approve" disabled onclick="triggerBulkDecision('approve')" class="px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200">
            <i data-lucide="check-circle" class="w-3.5 h-3.5 inline mr-1"></i> Approve Selected
        </button>
        <button id="btn-reject" disabled onclick="triggerBulkDecision('reject')" class="px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200">
            <i data-lucide="x-circle" class="w-3.5 h-3.5 inline mr-1"></i> Reject Selected
        </button>
    </div>
</div>

<div class="custom-table-container flex-1 overflow-hidden flex flex-col relative">
    <form id="bulkActionForm" action="../actions/process_booking_action.php" method="POST" class="flex-1 overflow-hidden flex flex-col">
        <input type="hidden" name="action_type" id="bulk_action_type" value="">
        <div class="flex-1 overflow-y-auto">
            <?php echo render_datagrid($datagrid_schema, $result); ?>
        </div>
    </form>
</div>

<div id="decision-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center transition-opacity opacity-0">
    <div class="bg-white rounded-md shadow-lg border border-slate-200 w-full max-w-sm p-6 transform scale-95 transition-transform" id="decision-panel">
        <h3 class="text-lg font-bold text-slate-800 mb-2" id="modal-title">Confirm Action</h3>
        <p class="text-sm text-slate-600 font-medium leading-relaxed mb-6" id="modal-msg"></p>
        <div class="flex justify-end space-x-3">
            <button type="button" onclick="closeDecisionModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-transparent hover:bg-slate-100 rounded-md transition-colors">Cancel</button>
            <button type="button" id="modal-confirm-btn" class="px-4 py-2 text-xs font-semibold text-white rounded-md shadow-sm transition-colors border">Confirm</button>
        </div>
    </div>
</div>

<script>

    const toggleAll = (source) =>
    {
        document.querySelectorAll('.row-cb').forEach(cb =>
        { cb.checked = source.checked; });
        updateButtonStates();
    };

    const updateButtonStates = () =>
    {
        const selectedCount = document.querySelectorAll('.row-cb:checked').length;
        const btnApprove = document.getElementById('btn-approve');
        const btnReject = document.getElementById('btn-reject');
        const cbCounter = document.getElementById('cb-counter');

        if (cbCounter) cbCounter.innerText = selectedCount;

        const hasSelection = selectedCount > 0;


        if (btnApprove)
        {
            btnApprove.disabled = !hasSelection;
            btnApprove.className = hasSelection
                ? "px-4 py-2 text-xs font-semibold text-white bg-[#059669] hover:bg-[#047857] rounded-md shadow-sm transition cursor-pointer border border-[#059669]"
                : "px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200";
        }

        if (btnReject)
        {
            btnReject.disabled = !hasSelection;
            btnReject.className = hasSelection
                ? "px-4 py-2 text-xs font-semibold text-[#dc2626] bg-white hover:bg-red-50 border border-red-200 rounded-md shadow-sm transition cursor-pointer"
                : "px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200";
        }
    };


    document.addEventListener('change', function(e)
    {
        if(e.target && e.target.classList.contains('row-cb'))
        {
            updateButtonStates();
        }
    });

    const triggerBulkDecision = (actionType) =>
    {
        const selected = document.querySelectorAll('.row-cb:checked');
        if (selected.length === 0) return;  const titleEl = document.getElementById('modal-title'); const msgEl = document.getElementById('modal-msg'); const btnEl = document.getElementById('modal-confirm-btn');  document.getElementById('bulk_action_type').value = actionType;  if (actionType === 'approve')
        {
            titleEl.innerText = 'Batch Approve Bookings';
            msgEl.innerText = `Are you sure you want to approve ${selected.length} request(s)? The venue slots will be locked.`;
            btnEl.className = 'px-4 py-2 text-xs font-semibold text-white rounded-md shadow-sm transition-colors bg-[#059669] hover:bg-[#047857] border border-[#059669]';
        } else if (actionType === 'reject')
        {
            titleEl.innerText = 'Batch Reject Bookings';
            msgEl.innerText = `Are you sure you want to reject ${selected.length} request(s)? The deposits will be flagged for refund.`;
            btnEl.className = 'px-4 py-2 text-xs font-semibold text-white rounded-md shadow-sm transition-colors bg-[#dc2626] hover:bg-[#b91c1c] border border-[#dc2626]';
        }

        const modal = document.getElementById('decision-modal');
        const panel = document.getElementById('decision-panel');
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.classList.remove('opacity-0');
        panel.classList.remove('scale-95');
    };

    const closeDecisionModal = () =>
    {
        const modal = document.getElementById('decision-modal');
        const panel = document.getElementById('decision-panel');
        modal.classList.add('opacity-0');
        panel.classList.add('scale-95');
        setTimeout(() =>
        { modal.classList.add('hidden'); }, 200);
    };


    document.getElementById('modal-confirm-btn').addEventListener('click', function()
    {
        this.innerHTML = 'Processing...';
        this.classList.add('opacity-70', 'cursor-not-allowed');
        document.getElementById('bulkActionForm').submit();
    });
</script>

<?php
$page_content = ob_get_clean();


require_once __DIR__ . '/../core/layout.php';
?>
