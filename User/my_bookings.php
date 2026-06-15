<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/user_auth.php';
require_once __DIR__ . '/../includes/user_header.php';
require_once __DIR__ . '/../includes/user_navbar.php';
require_once __DIR__ . '/../includes/booking_functions.php';
    
syncCompletedBookings($conn);

$user_id = $_SESSION['uid'];
$status_filter = trim($_GET['status'] ?? '');
$payment_filter = trim($_GET['payment'] ?? '');
$date_filter = trim($_GET['date_filter'] ?? '');
$search = trim($_GET['search'] ?? '');

$has_filter = (
    $status_filter !== '' ||
    $payment_filter !== '' ||
    $date_filter !== '' ||
    $search !== ''
);

$count_sql = "SELECT COUNT(*) AS total FROM booking WHERE uid = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("s", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$total_bookings = (int)($count_result['total'] ?? 0);
$count_stmt->close();

$sql = "
    SELECT 
        b.bid,
        b.date_booked,
        b.time_start,
        b.time_end,
        b.status,
        b.payment_status,
        b.created_at,
        v.vid,
        v.vname,
        v.max_cap,
        v.deposit,
        vc.category
    FROM booking b
    JOIN venue v ON b.vid = v.vid
    JOIN vcategory vc ON v.vcid = vc.vcid
    WHERE b.uid = ?
";

$params = [$user_id];
$types = "s";

// Filter by booking status
if ($status_filter !== '') {
    $sql .= " AND b.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Filter by payment status
if ($payment_filter !== '') {
    $sql .= " AND b.payment_status = ?";
    $params[] = $payment_filter;
    $types .= "s";
}

// Filter by date
if ($date_filter === 'upcoming') {
    $sql .= " AND b.date_booked >= CURDATE()";
} elseif ($date_filter === 'past') {
    $sql .= " AND b.date_booked < CURDATE()";
}

// Search by booking ID or venue name
if ($search !== '') {
    $sql .= " AND (CAST(b.bid AS CHAR) LIKE ? OR v.vname LIKE ? OR v.vid LIKE ?)";
    $search_like = "%" . $search . "%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $types .= "sss";
}

$sql .= " ORDER BY b.date_booked DESC, b.time_start DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
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

    if ($status === "cancelled") {
    return "bg-slate-100 text-slate-600 border-slate-200";
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

        <!-- Booking Filter Section -->
        <?php if ($total_bookings > 0): ?>
            <form method="GET" action="my_bookings.php" id="bookingFilterForm" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 mb-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                    <!-- Search -->
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">
                            Search
                        </label>
                        <div class="relative">
                            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-3"></i>
                            <input 
                                type="text" 
                                name="search"
                                id="bookingSearchInput"
                                value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Booking ID or venue..."
                                class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">
                            Booking Status
                        </label>
                        <select 
                            name="status"
                            class="auto-submit w-full px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">All Status</option>
                            <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo ($status_filter === 'approved') ? 'selected' : ''; ?>>Approved</option>
                            <option value="completed" <?php echo ($status_filter === 'completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo ($status_filter === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="rejected" <?php echo ($status_filter === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>

                    <!-- Payment Filter -->
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">
                            Payment Status
                        </label>
                        <select 
                            name="payment"
                            class="auto-submit w-full px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">All Payment</option>
                            <option value="unpaid" <?php echo ($payment_filter === 'unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                            <option value="paid" <?php echo ($payment_filter === 'paid') ? 'selected' : ''; ?>>Paid</option>
                            <option value="refunded" <?php echo ($payment_filter === 'refunded') ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>

                    <!-- Date Filter -->
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">
                            Date
                        </label>
                        <select 
                            name="date_filter"
                            class="auto-submit w-full px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">All Dates</option>
                            <option value="upcoming" <?php echo ($date_filter === 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="past" <?php echo ($date_filter === 'past') ? 'selected' : ''; ?>>Past</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-5">
                    <p class="text-xs text-slate-400 font-semibold">
                        Search and filters will be applied automatically.
                    </p>

                    <?php if ($search !== '' || $status_filter !== '' || $payment_filter !== '' || $date_filter !== ''): ?>
                        <a 
                            href="my_bookings.php"
                            class="inline-flex items-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition-colors"
                        >
                            <i data-lucide="rotate-ccw" class="w-4 h-4 mr-2"></i>
                            Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>

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
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="booking_details.php?bid=<?php echo urlencode($row['bid']); ?>"
                                            class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                                                Progress
                                                <i data-lucide="arrow-right" class="w-4 h-4 ml-2"></i>
                                            </a>

                                            <a href="booking_print.php?bid=<?php echo urlencode($row['bid']); ?>"
                                            target="_blank"
                                            class="inline-flex items-center justify-center px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold rounded-lg shadow-sm transition">
                                                View Report
                                                <i data-lucide="file-text" class="w-4 h-4 ml-2"></i>
                                            </a>
                                        </div>
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
                            <div class="grid grid-cols-2 gap-2">
                                <a href="booking_details.php?bid=<?php echo urlencode($row['bid']); ?>"
                                class="flex items-center justify-center w-full px-3 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-sm transition">
                                    Progress
                                </a>

                                <a href="booking_print.php?bid=<?php echo urlencode($row['bid']); ?>"
                                target="_blank"
                                class="flex items-center justify-center w-full px-3 py-2.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-sm font-bold rounded-lg shadow-sm transition">
                                    Report
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

        <?php else: ?>
            <?php if ($total_bookings === 0): ?>
                <!-- User has no booking at all -->
                <div class="py-12 text-center text-slate-500 bg-white rounded-2xl border border-slate-200">
                    <i data-lucide="calendar-plus" class="w-12 h-12 mx-auto text-slate-300 mb-3"></i>

                    <p class="font-bold text-slate-700">
                        No bookings yet.
                    </p>

                    <p class="text-sm text-slate-400 mt-1">
                        You have not made any venue booking yet.
                    </p>

                    <a 
                        href="venues.php"
                        class="inline-flex items-center justify-center mt-5 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-sm transition"
                    >
                        <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                        Make Your First Booking
                    </a>
                </div>

            <?php else: ?>
                <!-- User has bookings, but filter/search found nothing -->
                <div class="py-12 text-center text-slate-500 bg-white rounded-2xl border border-slate-200">
                    <i data-lucide="calendar-x" class="w-12 h-12 mx-auto text-slate-300 mb-3"></i>

                    <p class="font-bold text-slate-700">
                        No bookings matched your filter.
                    </p>

                    <p class="text-sm text-slate-400 mt-1">
                        Try changing the keyword, booking status, payment status, or date filter.
                    </p>

                    <a 
                        href="my_bookings.php"
                        class="inline-flex items-center justify-center mt-5 px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold rounded-lg transition"
                    >
                        <i data-lucide="rotate-ccw" class="w-4 h-4 mr-2"></i>
                        Clear Filters
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>


<script>
lucide.createIcons();

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('bookingFilterForm');
    const searchInput = document.getElementById('bookingSearchInput');
    const autoSubmitFields = document.querySelectorAll('.auto-submit');

    let searchTimer;

    if (form) {
        autoSubmitFields.forEach(function (field) {
            field.addEventListener('change', function () {
                form.submit();
            });
        });
    }

    if (form && searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);

            searchTimer = setTimeout(function () {
                form.submit();
            }, 500);
        });
    }
});
</script>

<?php include("../includes/user_footer.php"); ?>
