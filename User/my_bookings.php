<?php
include("../includes/user_auth.php");
include("../includes/user_header.php");
include("../includes/user_navbar.php");
include("../config/db.php");

$user_id = $_SESSION['uid'];

$stmt = $conn->prepare("
    SELECT b.bid, b.date_booked, b.status, b.payment_status,
           v.vname, v.vcid, v.max_cap, vc.category
    FROM booking b
    JOIN venue v ON b.vid = v.vid
    JOIN vcategory vc ON v.vcid = vc.vcid
    WHERE b.uid = ?
    ORDER BY b.date_booked DESC
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

function bookingStatusBadgeClass($status) {
    $status = strtolower((string)$status);

    if ($status === "approved") {
        return "bg-emerald-100 text-emerald-700 border-emerald-200";
    }

    if ($status === "rejected") {
        return "bg-red-100 text-red-700 border-red-200";
    }

    if ($status === "completed") {
        return "bg-blue-100 text-blue-700 border-blue-200";
    }

    return "bg-yellow-100 text-yellow-700 border-yellow-200";
}

function paymentStatusBadgeClass($status) {
    $status = strtolower((string)$status);

    if ($status === "paid") {
        return "bg-emerald-100 text-emerald-700 border-emerald-200";
    }

    if ($status === "refunded") {
        return "bg-blue-100 text-blue-700 border-blue-200";
    }

    return "bg-orange-100 text-orange-700 border-orange-200";
}

function formatBookingDate($date) {
    if (empty($date) || $date === "0000-00-00") {
        return "-";
    }

    $timestamp = strtotime($date);
    return $timestamp ? date("d M Y", $timestamp) : htmlspecialchars($date);
}
?>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-6xl mx-auto">

        <!-- Page Header -->
        <div class="mb-8 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">
                    My Bookings
                </h1>
                <p class="text-sm text-slate-500 mt-2">
                    View your booking records and track each booking progress.
                </p>
            </div>

            <a href="venues.php"
               class="inline-flex items-center justify-center px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl shadow-sm transition">
                <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                New Booking
            </a>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>

            <!-- Desktop Table -->
            <div class="hidden md:block bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                    <h2 class="text-lg font-extrabold text-slate-800">
                        Booking Records
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Latest bookings are shown first.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-white border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">
                                    Booking ID
                                </th>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">
                                    Venue
                                </th>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">
                                    Date
                                </th>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">
                                    Payment
                                </th>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">
                                    Status
                                </th>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php
                                    $bookingStatus = strtolower((string)$row["status"]);
                                    $paymentStatus = strtolower((string)($row["payment_status"] ?: "unpaid"));
                                ?>

                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-5 align-middle">
                                        <span class="text-sm font-black text-slate-700">
                                            #<?php echo htmlspecialchars($row["bid"]); ?>
                                        </span>
                                    </td>

                                    <td class="px-6 py-5 align-middle">
                                        <div>
                                            <p class="text-sm font-extrabold text-slate-800">
                                                <?php echo htmlspecialchars($row["vname"]); ?>
                                            </p>
                                            <p class="text-xs text-slate-400 font-bold uppercase mt-1">
                                                <?php echo htmlspecialchars($row["category"]); ?>
                                                •
                                                <?php echo (int)$row["max_cap"]; ?> Pax
                                            </p>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5 align-middle">
                                        <div class="flex items-center text-sm text-slate-600 font-semibold">
                                            <i data-lucide="calendar" class="w-4 h-4 mr-2 text-slate-400"></i>
                                            <?php echo formatBookingDate($row["date_booked"]); ?>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5 align-middle">
                                        <span class="inline-flex px-3 py-1 text-[11px] font-black uppercase tracking-wider rounded-full border <?php echo paymentStatusBadgeClass($paymentStatus); ?>">
                                            <?php echo htmlspecialchars($paymentStatus); ?>
                                        </span>
                                    </td>

                                    <td class="px-6 py-5 align-middle">
                                        <span class="inline-flex px-3 py-1 text-[11px] font-black uppercase tracking-wider rounded-full border <?php echo bookingStatusBadgeClass($bookingStatus); ?>">
                                            <?php echo htmlspecialchars($bookingStatus); ?>
                                        </span>
                                    </td>

                                    <td class="px-6 py-5 align-middle text-right">
                                        <a href="booking_details.php?bid=<?php echo urlencode($row['bid']); ?>"
                                           class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                                            View Progress
                                            <i data-lucide="arrow-right" class="w-4 h-4 ml-2"></i>
                                        </a>
                                    </td>
                                </tr>

                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php
            // Reset result pointer for mobile list rendering.
            $result->data_seek(0);
            ?>

            <!-- Mobile List -->
            <div class="md:hidden space-y-4">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                        $bookingStatus = strtolower((string)$row["status"]);
                        $paymentStatus = strtolower((string)($row["payment_status"] ?: "unpaid"));
                    ?>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs text-slate-400 font-black uppercase tracking-widest">
                                        Booking #<?php echo htmlspecialchars($row["bid"]); ?>
                                    </p>

                                    <h3 class="text-lg font-extrabold text-slate-800 mt-1">
                                        <?php echo htmlspecialchars($row["vname"]); ?>
                                    </h3>

                                    <p class="text-xs text-slate-400 font-bold uppercase mt-1">
                                        <?php echo htmlspecialchars($row["category"]); ?>
                                        •
                                        <?php echo (int)$row["max_cap"]; ?> Pax
                                    </p>
                                </div>

                                <span class="inline-flex px-3 py-1 text-[10px] font-black uppercase tracking-wider rounded-full border <?php echo bookingStatusBadgeClass($bookingStatus); ?>">
                                    <?php echo htmlspecialchars($bookingStatus); ?>
                                </span>
                            </div>

                            <div class="mt-4 space-y-3">
                                <div class="flex items-center text-sm text-slate-600">
                                    <i data-lucide="calendar" class="w-4 h-4 mr-2 text-slate-400"></i>
                                    <span>
                                        Date:
                                        <strong><?php echo formatBookingDate($row["date_booked"]); ?></strong>
                                    </span>
                                </div>

                                <div class="flex items-center text-sm text-slate-600">
                                    <i data-lucide="credit-card" class="w-4 h-4 mr-2 text-slate-400"></i>
                                    <span>
                                        Payment:
                                        <span class="ml-1 inline-flex px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full border <?php echo paymentStatusBadgeClass($paymentStatus); ?>">
                                            <?php echo htmlspecialchars($paymentStatus); ?>
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 border-t border-slate-100 bg-slate-50">
                            <a href="booking_details.php?bid=<?php echo urlencode($row['bid']); ?>"
                               class="flex items-center justify-center w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-sm transition">
                                View Progress
                                <i data-lucide="arrow-right" class="w-4 h-4 ml-2"></i>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

        <?php else: ?>

            <!-- Empty State -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm py-16 px-6 text-center">
                <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-4">
                    <i data-lucide="inbox" class="w-8 h-8 text-slate-400"></i>
                </div>

                <h2 class="text-xl font-extrabold text-slate-800">
                    No booking records yet
                </h2>

                <p class="text-sm text-slate-500 mt-2">
                    Once you make a booking, your record will appear here.
                </p>

                <a href="venues.php"
                   class="inline-flex items-center justify-center mt-6 px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl shadow-sm transition">
                    <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                    Make a Booking
                </a>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php include("../includes/user_footer.php"); ?>

<script>
lucide.createIcons();
</script>