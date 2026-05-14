<?php
include("../includes/user_auth.php");
include("../includes/user_header.php");
include("../includes/user_navbar.php");
include("../config/db.php");

$user_id = $_SESSION['uid'];

// 🔒 validate bid
$bid = trim($_GET['bid'] ?? '');

if ($bid === '' || !ctype_digit($bid)) {
    die("<div class='p-10 text-center text-red-600 font-bold'>Invalid Booking ID</div>");
}

// 🔐 fetch booking (must belong to user)
$stmt = $conn->prepare("
    SELECT 
        b.bid,
        b.date_booked,
        b.time_start,
        b.time_end,
        b.status,
        b.payment_status,
        b.transaction_ref,
        b.purpose,
        b.approve_date,
        v.vname,
        v.max_cap,
        v.deposit,
        vc.category,
        a.admin_name

    FROM booking b

    JOIN venue v
        ON b.vid = v.vid 

    JOIN vcategory vc
        ON v.vcid = vc.vcid 

    LEFT JOIN admin a
        ON b.aid = a.aid 

    WHERE b.bid = ?
    AND b.uid = ?

    LIMIT 1
");

$stmt->bind_param("is", $bid, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    die("<div class='p-10 text-center text-slate-600 font-bold'>Booking not found or access denied.</div>");
}
?>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">

        <!-- Header -->
        <div class="mb-8">
            <a href="my_bookings.php" class="text-sm text-indigo-600 font-bold hover:underline">
                ← Back to My Bookings
            </a>

            <h1 class="text-3xl font-extrabold text-slate-800 mt-3">
                Booking Details
            </h1>
            <p class="text-sm text-slate-500">
                View full information of your booking record
            </p>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">

            <!-- Header -->
            <div class="p-6 border-b border-slate-100 flex justify-between items-start">
                <div>
                    <h2 class="text-xl font-bold text-slate-800">
                        <?php echo htmlspecialchars($booking["vname"]); ?>
                    </h2>
                    <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">
                        <?php echo htmlspecialchars($booking["category"]); ?>
                    </p>
                </div>

                <?php
                    $status = $booking['status'];

                    $badge = "bg-yellow-100 text-yellow-700";
                    if ($status === "approved") {
                        $badge = "bg-emerald-100 text-emerald-700";
                    } elseif ($status === "rejected") {
                        $badge = "bg-red-100 text-red-700";
                    }
                ?>

                <span class="px-3 py-1 text-xs font-black uppercase rounded <?php echo $badge; ?>">
                    <?php echo htmlspecialchars($status); ?>
                </span>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-6">

                <!-- Grid Info -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div class="p-4 bg-slate-50 rounded-lg border">
                        <p class="text-xs text-slate-500 font-bold uppercase">Date</p>
                        <p class="text-slate-800 font-semibold">
                            <?php echo htmlspecialchars($booking["date_booked"]); ?>
                        </p>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-lg border">
                        <p class="text-xs text-slate-500 font-bold uppercase">Time</p>
                        <p class="text-slate-800 font-semibold">
                            <?php echo htmlspecialchars($booking["time_start"]); ?>
                            →
                            <?php echo htmlspecialchars($booking["time_end"]); ?>
                        </p>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-lg border">
                        <p class="text-xs text-slate-500 font-bold uppercase">Capacity</p>
                        <p class="text-slate-800 font-semibold">
                            <?php echo (int)$booking["max_cap"]; ?> Pax
                        </p>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-lg border">
                        <p class="text-xs text-slate-500 font-bold uppercase">Deposit</p>
                        <p class="text-emerald-600 font-extrabold">
                            RM <?php echo number_format((float)$booking["deposit"], 2); ?>
                        </p>
                    </div>

                </div>

                <!-- Purpose -->
                <div class="p-4 bg-indigo-50 border border-indigo-100 rounded-lg">
                    <p class="text-xs font-bold text-indigo-600 uppercase">Purpose</p>
                    <p class="text-slate-800 font-medium mt-1">
                        <?php echo htmlspecialchars($booking["purpose"] ?? '-'); ?>
                    </p>
                </div>

                <!-- Payment -->
                <div class="p-4 border rounded-lg bg-white">
                    <p class="text-xs text-slate-500 font-bold uppercase">Payment Status</p>
                    <p class="font-bold text-slate-800">
                        <?php echo htmlspecialchars($booking["payment_status"]); ?>
                    </p>
                </div>

                <?php if (!empty($booking['admin_name'])): ?>

                    <div class="p-5 rounded-xl border border-slate-200 bg-slate-50">

                        <p class="text-xs uppercase font-bold text-slate-400 tracking-wider">
                            Reviewed By
                        </p>

                        <p class="text-slate-800 font-bold mt-2">
                            <?php echo htmlspecialchars($booking['admin_name']); ?>
                        </p>

                        <?php if (!empty($booking['approve_date'])): ?>

                            <p class="text-xs text-slate-400 mt-2">
                                <?php echo htmlspecialchars($booking['approve_date']); ?>
                            </p>

                        <?php endif; ?>

                    </div>

                    <?php endif; ?>

            </div>

        </div>

    </div>
</div>

<?php include("../includes/user_footer.php"); ?>

<script>
lucide.createIcons();
</script>