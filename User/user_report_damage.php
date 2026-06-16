<?php
// This section prepares the user report damage page.
session_start();
require_once __DIR__ . '/../config/db.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['uid']))
{
    header("Location: login.php");
    exit;
}

$uid = $_SESSION['uid'];
$bid = intval($_GET['bid'] ?? 0);

if ($bid <= 0)
{
    die("Invalid booking ID.");
}

$stmt = $conn->prepare("
    SELECT b.bid, b.uid, b.vid, b.date_booked, b.time_start, b.time_end, b.status, v.vname, vc.category
    FROM booking b
    JOIN venue v ON b.vid = v.vid
    JOIN vcategory vc ON v.vcid = vc.vcid
    WHERE b.bid = ? AND b.uid = ?
    LIMIT 1
");
$stmt->bind_param("is", $bid, $uid);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0)
{
    die("Booking not found.");
}

$booking = $result->fetch_assoc();
$stmt->close();

if (strtolower($booking['status']) !== 'approved')
{
    $_SESSION['error'] = "Damage report is only available for approved bookings.";
    header("Location: booking_details.php?bid=" . urlencode($bid));
    exit;
}

$damage_window_start_ts = strtotime($booking['date_booked'] . ' ' . $booking['time_start']);
$damage_window_end_ts = strtotime($booking['date_booked'] . ' ' . $booking['time_end']);
$now_ts = time();

if ( $damage_window_start_ts === false || $damage_window_end_ts === false || $now_ts < $damage_window_start_ts || $now_ts > $damage_window_end_ts )
{
    $_SESSION['error'] = "Damage report can only be submitted during the booking time.";
    header("Location: booking_details.php?bid=" . urlencode($bid));
    exit;
}

$check = $conn->prepare("SELECT report_id FROM damage_report WHERE bid = ? AND uid = ? LIMIT 1");
$check->bind_param("is", $bid, $uid);
$check->execute();
$check_result = $check->get_result();

if ($check_result && $check_result->num_rows > 0)
{
    header("Location: booking_details.php?bid=" . urlencode($bid));
    exit;
}
$check->close();

$page_title = "Report Existing Damage";
include("../includes/user_header.php");
include("../includes/user_navbar.php");
?>

<div class="relative z-10 max-w-4xl mx-auto px-4 py-10">
    <div class="mb-8">
        <a href="booking_details.php?bid=<?php echo urlencode($booking['bid']); ?>"
           class="inline-flex items-center text-sm font-bold text-blue-700 hover:text-blue-900 transition">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
            Back to Booking Details
        </a>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-200">
            <h1 class="text-2xl font-black text-slate-800 flex items-center">
                <i data-lucide="triangle-alert" class="w-6 h-6 mr-3 text-amber-600"></i>
                Report Existing Damage
            </h1>
        </div>

        <form method="POST" action="user_report_damage_process.php" enctype="multipart/form-data" class="p-6 space-y-6">
            <input type="hidden" name="bid" value="<?php echo htmlspecialchars($booking['bid']); ?>">
            <input type="hidden" name="vid" value="<?php echo htmlspecialchars($booking['vid']); ?>">
            <input type="hidden" name="user_action" id="user_action" value="continue">

            <div>
                <label class="block text-sm font-black text-slate-700 mb-2">Damage Severity <span class="text-red-500">*</span></label>
                <div class="space-y-3">
                    <label class="flex items-start p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition" onclick="updateUI('Level 1')">
                        <input type="radio" name="severity" value="Level 1" class="mt-1 mr-3" required>
                        <div>
 <p class="text-sm font-bold text-slate-800">Level 1: Cosmetic Issue</p>
                            <p class="text-xs text-slate-500">Does not affect usage (e.g., wall stains, minor scratches).</p>
                        </div>
                    </label>
                    <label class="flex items-start p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition" onclick="updateUI('Level 2')">
                        <input type="radio" name="severity" value="Level 2" class="mt-1 mr-3" required>
                        <div>
 <p class="text-sm font-bold text-slate-800">Level 2: Partial Impairment</p>
                            <p class="text-xs text-slate-500">Some features broken but venue is still usable (e.g., 1 broken chair out of 30).</p>
                        </div>
                    </label>
                    <label class="flex items-start p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-red-50 transition" onclick="updateUI('Level 3')">
                        <input type="radio" name="severity" value="Level 3" class="mt-1 mr-3" required>
                        <div>
 <p class="text-sm font-bold text-red-600">Level 3: Critical/Blocking Damage</p>
                            <p class="text-xs text-slate-500">Venue is unusable or physically unsafe (e.g., door lock broken, power outage).</p>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-black text-slate-700 mb-2">Damage Description <span class="text-red-500">*</span></label>
                <textarea name="damage_description" rows="4" required placeholder="Describe the damage and its exact location..." class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500"></textarea>
            </div>

            <div>
                <label class="block text-sm font-black text-slate-700 mb-2">Damage Photo</label>
                <input type="file" name="damage_photo" accept="image/jpeg,image/png,image/jpg,image/webp" class="block w-full text-sm text-slate-600 border border-slate-200 rounded-xl cursor-pointer bg-white file:mr-4 file:py-3 file:px-4 file:border-0 file:bg-amber-50 file:text-amber-700">
            </div>

            <div id="level3_warning" class="hidden bg-red-50 border border-red-200 rounded-xl p-4">
                <p class="text-sm text-red-800 font-bold mb-1">Critical Damage Detected</p>
                <p class="text-sm text-red-700 leading-relaxed mb-3">Since the venue is unusable, you may request an immediate cancellation and refund. If you choose this, you must vacate the venue immediately.</p>
                <div class="flex gap-4">
                    <label class="flex items-center text-sm font-bold text-red-700 cursor-pointer">
                        <input type="radio" name="level3_choice" value="cancel" class="mr-2" onchange="document.getElementById('user_action').value = 'cancel'; updateButtons();"> Request Cancellation & Refund
                    </label>
                    <label class="flex items-center text-sm font-bold text-slate-700 cursor-pointer">
                        <input type="radio" name="level3_choice" value="continue" class="mr-2" onchange="document.getElementById('user_action').value = 'continue'; updateButtons();" checked> Proceed & Continue Using
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="booking_details.php?bid=<?php echo urlencode($booking['bid']); ?>" class="px-5 py-2.5 text-sm font-bold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition">Cancel</a>

                <button type="submit" id="btn_submit" class="px-6 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition flex items-center">
                    <i data-lucide="check-circle" class="w-4 h-4 mr-2"></i>
                    Acknowledge & Continue Use
                </button>
            </div>
        </form>
    </div>
</div>

<script>
lucide.createIcons();

function updateUI(severity)
{
    const warningBox = document.getElementById('level3_warning');
    const actionInput = document.getElementById('user_action');

    if (severity === 'Level 3')
    {
        warningBox.classList.remove('hidden');

        const choice = document.querySelector('input[name="level3_choice"]:checked');
        actionInput.value = choice ? choice.value : 'continue';
    } else
    {
        warningBox.classList.add('hidden');
        actionInput.value = 'continue';
    }
    updateButtons();
}

function updateButtons()
{
    const action = document.getElementById('user_action').value;
    const btn = document.getElementById('btn_submit');

    if (action === 'cancel')
    {
        btn.innerHTML = '<i data-lucide="x-circle" class="w-4 h-4 mr-2"></i> Cancel Order & Request Refund';
        btn.className = 'px-6 py-2.5 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm transition flex items-center';
    } else
    {
        btn.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4 mr-2"></i> Acknowledge & Continue Use';
        btn.className = 'px-6 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition flex items-center';
    }
    lucide.createIcons();
}
</script>

<?php include("../includes/user_footer.php"); ?>
