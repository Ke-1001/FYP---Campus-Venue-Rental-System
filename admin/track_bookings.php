<?php
// This section prepares the admin track bookings page.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/booking_functions.php';
require_once __DIR__ . '/../core/components/datagrid.php';


require_once __DIR__ . '/../core/components/FilterBuilder.php';
require_once __DIR__ . '/../core/repositories/BookingRepository.php';

use Core\Components\FilterBuilder;
use Core\Repositories\BookingRepository;

syncCompletedBookings($conn);


expireUnpaidBookings($conn);


$bookingRepo = new BookingRepository($conn);


$filterBuilder = new FilterBuilder('track_bookings.php', true);
$filterBuilder
    ->addField('text', 'f_bid', 'Booking ID', [], 'Search ID...', 'b.bid', 'LIKE')
    ->addField('text', 'f_student', 'Student', [], 'Name or ID...', 'CONCAT(u.uid, " ", u.username)', 'LIKE')
    ->addField('text', 'f_venue', 'Venue', [], 'Venue Name or Cat...', 'CONCAT(v.vname, " ", vc.category)', 'LIKE')
    ->addField('date', 'f_date', 'Date', [], '', 'b.date_booked', '=')
    ->addField('select', 'f_status', 'Status', [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled'
    ], 'All', 'b.status', '=');


$result = $bookingRepo->getAllWithFilters($filterBuilder);


$records = [];
if ($result && $result->num_rows > 0)
{
    while($row = $result->fetch_assoc())
    {
        $remarks = '-';

        if ($row['status'] === 'pending' && $row['payment_status'] === 'unpaid')
        {
            $timestamp = strtotime($row['payment_due_at']);
            $remarks = 'Due: ' . ($timestamp ? date('y/m/d H:i', $timestamp) : 'N/A');
        } elseif ($row['status'] === 'cancelled')
        {


            $raw_reason = $row['cancel_reason'];
            if ($raw_reason === 'SYS_TIMEOUT_ADMIN')
            {
                $friendly_reason = 'Auto-Timeout';
            } else
            {
                $friendly_reason = $raw_reason ?: 'Time Expired';
            }


            $cancel_time = strtotime($row['cancelled_at']);
            $time_str = $cancel_time ? date('m/d H:i', $cancel_time) : '';


            if (!empty($time_str))
            {
                $remarks = $time_str . ' (' . $friendly_reason . ')';
            } else
            {
                $remarks = '' . $friendly_reason;
            }
        }

        $row['remarks'] = $remarks;
        $records[] = $row;
    }
}


$page_title = "Booking History";
$page_description = "View paid, unpaid, completed, rejected and cancelled venue booking records.";
$topbar_content = '
<div class="flex items-center">
    <a href="manage_bookings.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Bookings / History</h2>
</div>';
$extra_css = [];


$datagrid_schema = [
    'enable_checkbox' => false,
    'primary_key' => 'bid',
    'row_action_url' => 'process_flow.php?bid=%s',
    'columns' => [
        ['key' => 'bid', 'label' => 'ID', 'type' => 'link', 'url_format' => 'process_flow.php?bid=%s', 'width' => 'w-24 text-center'],
        ['key' => 'student_id', 'label' => 'Student ID', 'type' => 'text_mono', 'width' => 'w-28'],
        ['key' => 'username', 'label' => 'Student Name', 'type' => 'text_bold', 'width' => 'w-36'],
        ['key' => 'vname', 'label' => 'Venue Name', 'type' => 'text_bold', 'width' => 'w-36'],
        ['key' => 'venue_category', 'label' => 'Category', 'type' => 'badge', 'width' => 'w-28'],
        ['key' => 'date_booked', 'label' => 'Date', 'type' => 'text_mono', 'width' => 'w-28 text-center'],
        ['type' => 'time_range', 'label' => 'Time', 'start_key' => 'time_start', 'end_key' => 'time_end', 'width' => 'w-32'],
        ['key' => 'status', 'label' => 'Status', 'type' => 'map_badge', 'width' => 'w-28 text-center', 'map' => [
            'pending' => ['label' => 'Pending', 'class' => 'bg-[#fffbeb] text-[#b45309] border-[#fde68a]'],
            'approved' => ['label' => 'Approved', 'class' => 'bg-[#eff6ff] text-[#1d4ed8] border-[#bfdbfe]'],
            'completed' => ['label' => 'Completed', 'class' => 'bg-[#ecfdf5] text-[#059669] border-[#a7f3d0]'],
            'rejected' => ['label' => 'Rejected', 'class' => 'bg-[#fef2f2] text-[#dc2626] border-[#fecaca]'],
            'cancelled' => ['label' => 'Cancelled', 'class' => 'bg-[#f1f5f9] text-slate-600 border-[#cbd5e1]']
        ]],
        ['key' => 'payment_status', 'label' => 'Payment', 'type' => 'map_badge', 'width' => 'w-28 text-center', 'map' => [
            'paid' => ['label' => 'Paid', 'class' => 'bg-[#ecfdf5] text-[#059669] border-[#a7f3d0]'],
            'refunded' => ['label' => 'Refunded', 'class' => 'bg-[#eff6ff] text-[#1d4ed8] border-[#bfdbfe]'],
            'unpaid' => ['label' => 'Unpaid', 'class' => 'bg-[#fffbeb] text-[#b45309] border-[#fde68a]']
        ]],
        ['key' => 'remarks', 'label' => 'Remarks', 'type' => 'text_muted_mono', 'width' => 'w-40']
    ]
];


ob_start();
?>

<?php echo $filterBuilder->render(); ?>

<div class="custom-table-container flex-1 overflow-hidden flex flex-col">
    <div class="px-4 py-3 border-b border-slate-200 bg-[#f8fafc] flex justify-between items-center shrink-0">
        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest">
            Records (<?php echo count($records); ?>)
        </h3>
    </div>

    <div class="flex-1 overflow-y-auto">
        <?php echo render_datagrid($datagrid_schema, $records); ?>
    </div>
</div>

<?php
$page_content = ob_get_clean();


require_once __DIR__ . '/../core/layout.php';
?>
