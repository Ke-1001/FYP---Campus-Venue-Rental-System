<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit;
}

$uid = $_SESSION['uid'];
$bid = intval($_GET['bid'] ?? 0);

if ($bid <= 0) {
    die("Invalid booking ID.");
}

// Get booking details and make sure it belongs to this user
$stmt = $conn->prepare("
    SELECT 
        b.bid,
        b.uid,
        b.vid,
        b.date_booked,
        b.time_start,
        b.time_end,
        b.status,
        v.vname,
        vc.category
    FROM booking b
    JOIN venue v ON b.vid = v.vid
    JOIN vcategory vc ON v.vcid = vc.vcid
    WHERE b.bid = ? AND b.uid = ?
    LIMIT 1
");
$stmt->bind_param("ii", $bid, $uid);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    die("Booking not found.");
}

$booking = $result->fetch_assoc();
$stmt->close();

if (strtolower($booking['status']) !== 'approved') {
    die("Damage report is only available for approved bookings.");
}

// Check duplicate report
$check = $conn->prepare("
    SELECT report_id 
    FROM damage_report 
    WHERE bid = ? AND uid = ?
    LIMIT 1
");
$check->bind_param("ii", $bid, $uid);
$check->execute();
$check_result = $check->get_result();

if ($check_result && $check_result->num_rows > 0) {
    header("Location: booking_details.php?bid=" . urlencode($bid));
    exit;
}

$check->close();

$page_title = "Report Existing Damage";
include("../includes/user_header.php");
include("../includes/user_navbar.php");
?>

<div class="max-w-4xl mx-auto px-4 py-10">
    <div class="mb-8">
        <a href="booking_details.php?bid=<?php echo urlencode($booking['bid']); ?>" 
           class="inline-flex items-center text-sm font-bold text-slate-500 hover:text-indigo-600 transition">
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

            <p class="text-sm text-slate-500 mt-2">
                Use this form to report any damage that already exists before you use the venue.
            </p>
        </div>

        <div class="p-6 bg-slate-50 border-b border-slate-200">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-slate-400 font-bold uppercase text-xs">Venue</p>
                    <p class="text-slate-800 font-bold">
                        <?php echo htmlspecialchars($booking['vname']); ?>
                    </p>
                </div>

                <div>
                    <p class="text-slate-400 font-bold uppercase text-xs">Booking Time</p>
                    <p class="text-slate-800 font-bold">
                        <?php echo date("d M Y", strtotime($booking['date_booked'])); ?>,
                        <?php echo substr($booking['time_start'], 0, 5); ?> -
                        <?php echo substr($booking['time_end'], 0, 5); ?>
                    </p>
                </div>
            </div>
        </div>

        <form method="POST" action="user_report_damage_process.php" enctype="multipart/form-data" class="p-6 space-y-6">
            <input type="hidden" name="bid" value="<?php echo htmlspecialchars($booking['bid']); ?>">
            <input type="hidden" name="vid" value="<?php echo htmlspecialchars($booking['vid']); ?>">

            <div>
                <label class="block text-sm font-black text-slate-700 mb-2">
                    Damage Description <span class="text-red-500">*</span>
                </label>

                <textarea 
                    name="damage_description"
                    rows="6"
                    required
                    placeholder="Example: The chair near the front table is broken. There are scratches on the wall beside the projector screen."
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                ></textarea>

                <p class="text-xs text-slate-400 mt-2">
                    Please describe the damage clearly, including the location inside the venue.
                </p>
            </div>

            <div>
                <label class="block text-sm font-black text-slate-700 mb-2">
                    Damage Photo
                </label>

                <input 
                    type="file"
                    name="damage_photo"
                    accept="image/jpeg,image/png,image/jpg,image/webp"
                    class="block w-full text-sm text-slate-600 border border-slate-200 rounded-xl cursor-pointer bg-white focus:outline-none file:mr-4 file:py-3 file:px-4 file:border-0 file:text-sm file:font-bold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100"
                >

                <p class="text-xs text-slate-400 mt-2">
                    Upload a clear photo if possible. Accepted formats: JPG, PNG, WEBP.
                </p>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                <p class="text-sm text-amber-800 leading-relaxed">
                    Please submit this report before using the venue. False or misleading reports may be reviewed by the admin.
                </p>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <a 
                    href="booking_details.php?bid=<?php echo urlencode($booking['bid']); ?>"
                    class="px-5 py-2.5 text-sm font-bold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition"
                >
                    Cancel
                </a>

                <button 
                    type="submit"
                    class="px-6 py-2.5 text-sm font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-lg shadow-sm transition flex items-center"
                >
                    <i data-lucide="send" class="w-4 h-4 mr-2"></i>
                    Submit Report
                </button>
            </div>
        </form>
    </div>
</div>

<script>
lucide.createIcons();
</script>

<?php include("../includes/user_footer.php"); ?>