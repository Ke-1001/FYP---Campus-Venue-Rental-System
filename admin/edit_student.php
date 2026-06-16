<?php

// This section prepares the admin edit student page.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

require_once __DIR__ . '/../core/components/FilterBuilder.php';
require_once __DIR__ . '/../core/components/DataGridBuilder.php';
require_once __DIR__ . '/../core/components/FioriFormBuilder.php';
require_once __DIR__ . '/../core/repositories/StudentRepository.php';

use Core\Components\FilterBuilder;
use Core\Components\DataGridBuilder;
use Core\Components\FioriFormBuilder as FB;
use Core\Repositories\StudentRepository;

$studentRepo = new StudentRepository($conn);

$uid_param = isset($_GET['uid']) ? trim($_GET['uid']) : '';

if (empty($uid_param))
{
    die("Execution Fault: Missing Identity Parameter (UID).");
}

$student_entity = $studentRepo->getStudentById($uid_param);
if (!$student_entity)
{
    die("Execution Fault: Student Identity Node not found in database.");
}

$records = [];
$sql_booking = "
    SELECT
        b.bid AS booking_id,
        v.vname AS venue_name,
        b.date_booked AS booking_date,
        b.status
    FROM booking b
    LEFT JOIN venue v ON b.vid = v.vid
    WHERE b.uid = ?
    ORDER BY b.date_booked DESC, b.time_start DESC
";

$booking_stmt = $conn->prepare($sql_booking);
if ($booking_stmt)
{
    $booking_stmt->bind_param("s", $uid_param);
    $booking_stmt->execute();
    $booking_result = $booking_stmt->get_result();
    while ($row = $booking_result->fetch_assoc())
    {
        $records[] = $row;
    }
    $booking_stmt->close();
}
else
{
    die("Relational Matrix Extraction Fault: " . $conn->error);
}

$page_title = "Edit Student Profile";
$page_description = "Modify systemic status for student entities and analyze their historical resource allocation paths.";
$topbar_content = '
<div class="flex items-center">
    <a href="manage_students.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Identity Management / Profile Editor</h2>
</div>';

$extra_css = [];

$bookingGrid = new DataGridBuilder('booking_id', '#', 'booking record');
$bookingGrid->disableAction('create')->disableAction('edit')->disableAction('delete')
    ->addColumn('booking_id', 'Booking Reference', 'text_mono', ['width' => 'w-32 text-center'])
    ->addColumn('venue_name', 'Allocated Resource / Venue', 'text_bold', ['width' => 'w-64'])
    ->addColumn('booking_date', 'Timestamp Matrix', 'text_muted_mono', ['width' => 'w-48'])
    ->addColumn('status', 'Allocation State', 'map_badge', ['width' => 'w-36 text-center', 'map' => [
        'completed' => ['label' => 'Completed', 'class' => 'bg-emerald-50 text-emerald-600 border-emerald-100'],
        'pending'   => ['label' => 'Pending', 'class' => 'bg-amber-50 text-amber-600 border-amber-100'],
        'cancelled' => ['label' => 'Cancelled', 'class' => 'bg-rose-50 text-rose-600 border-rose-100']
    ]]);

ob_start();
?>

<form action="../actions/process_student_action.php" method="POST" id="studentStatusForm" class="bg-white rounded-lg shadow-sm border border-slate-200 relative mb-8">
    <input type="hidden" name="action" value="update_status">
    <input type="hidden" name="uid" value="<?php echo htmlspecialchars($student_entity['uid']); ?>">

    <div class="p-0">
        <div class="fiori-form-container border-none shadow-none">
            <div class="fiori-section-header bg-slate-50 border-b border-slate-200">
                <h2 class="text-base font-bold text-[#1d2d3e]">
                    <i data-lucide="user-cog" class="w-4 h-4 inline mr-1 pb-0.5 text-[#004aad]"></i>
                    Identity Modification Node (UID: <?php echo htmlspecialchars($student_entity['uid']); ?>)
                </h2>
            </div>

            <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">
                <div class="lg:col-span-8 space-y-4">
                    <?php
                    echo FB::input('text', 'display_uid', 'Student Identity (UID)', $student_entity['uid'], [
                        'readonly' => true,
                        'extra_css' => 'font-mono text-slate-400 bg-slate-50 cursor-not-allowed'
                    ]);

                    echo FB::input('text', 'display_name', 'Username Profile', $student_entity['username'] ?? '', [
                        'readonly' => true,
                        'extra_css' => 'text-slate-400 bg-slate-50 cursor-not-allowed'
                    ]);

                    echo FB::input('text', 'display_email', 'Email Address Vector', $student_entity['email'] ?? '', [
                        'readonly' => true,
                        'extra_css' => 'font-mono text-slate-400 bg-slate-50 cursor-not-allowed'
                    ]);

                    echo FB::select('status', 'Account State Constraint', [
                        'active' => 'Active (Granted Access)',
                        'restricted' => 'Restricted (Suspended Access)'
                    ], $student_entity['status'] ?? 'active', [
                        'required' => true
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-slate-50 border-t border-slate-200 p-4 flex justify-end space-x-3 rounded-b-lg">
        <a href="manage_students.php" class="px-5 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-md hover:bg-slate-50 transition-colors">
            Cancel Modification
        </a>
        <button type="submit" id="submitBtn" class="px-5 py-2 text-sm font-semibold text-white bg-[#004aad] rounded-md hover:bg-[#003882] shadow-sm transition-colors flex items-center border border-[#004aad]">
            <i data-lucide="save" class="w-4 h-4 mr-2"></i> Apply Synchronization
        </button>
    </div>
</form>

<script>
    document.getElementById('studentStatusForm').addEventListener('submit', function()
    {
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2 inline"></i> Executing Data Sync...';
        btn.classList.add('opacity-70', 'cursor-not-allowed');
    });
</script>

<h3 class="text-xl font-extrabold text-slate-800 tracking-tight mb-4">Past Allocation Matrix Topology</h3>
<?php echo $bookingGrid->render($records); ?>

<?php
$page_content = ob_get_clean();

require_once __DIR__ . '/../core/layout.php';
?>
