<?php
include("../includes/user_auth.php");
include("../includes/user_header.php");
include("../includes/user_navbar.php");
include("../config/db.php");

$user_id = $_SESSION['uid'];

// Validate booking id
$bid = trim($_GET['bid'] ?? '');

if ($bid === '' || !ctype_digit($bid)) {
    die("<div class='p-10 text-center text-red-600 font-bold'>Invalid Booking ID</div>");
}

// Fetch booking details together with admin review, inspection, report, and inspector info.
// This makes the user page reflect what admin/staff already processed.
$stmt = $conn->prepare("
    SELECT 
        b.bid,
        b.date_booked,
        b.time_start,
        b.time_end,
        b.status,
        b.payment_status,
        b.payment_due_at,
        b.cancelled_at,
        b.cancel_reason,
        b.transaction_ref,
        b.purpose,
        b.approve_date,
        b.created_at,
        v.vname,
        v.max_cap,
        v.deposit,
        vc.category,
        a.admin_name,
        i.ins_id,
        i.ins_status,
        i.damage_desc,
        i.damage_cost,
        i.penalty,
        i.inspected_at,
        s.staff_name AS inspector_name,
        r.rid,
        r.final_deduct,
        r.refund_status,
        r.penalty_status,
        r.created_at AS report_created_at
    FROM booking b
    JOIN venue v ON b.vid = v.vid 
    JOIN vcategory vc ON v.vcid = vc.vcid 
    LEFT JOIN admin a ON b.aid = a.aid 
    LEFT JOIN inspection i ON b.bid = i.bid
    LEFT JOIN staff s ON i.sid = s.sid
    LEFT JOIN report r ON i.ins_id = r.ins_id
    WHERE b.bid = ?
      AND b.uid = ?
    ORDER BY i.ins_id DESC, r.rid DESC
    LIMIT 1
");

$stmt->bind_param("is", $bid, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

$damage_report = null;

$damage_stmt = $conn->prepare("
    SELECT report_id, report_status, created_at
    FROM damage_report
    WHERE bid = ? AND uid = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$damage_stmt->bind_param("ii", $booking['bid'], $_SESSION['uid']);
$damage_stmt->execute();
$damage_result = $damage_stmt->get_result();

if ($damage_result && $damage_result->num_rows > 0) {
    $damage_report = $damage_result->fetch_assoc();
}

$damage_stmt->close();

$can_report_damage = (
    strtolower($booking['status']) === 'approved' && !$damage_report
);

if (!$booking) {
    die("<div class='p-10 text-center text-slate-600 font-bold'>Booking not found or access denied.</div>");
}

$status = strtolower((string)$booking['status']);
$paymentStatus = strtolower((string)$booking['payment_status']);
$inspectionStatus = strtolower((string)($booking['ins_status'] ?? ''));
$refundStatus = strtolower((string)($booking['refund_status'] ?? ''));
$penaltyStatus = strtolower((string)($booking['penalty_status'] ?? ''));
$hasInspection = !empty($booking['ins_id']);
$hasReport = !empty($booking['rid']);

function formatDateTimeSafe($value, $format = 'd M Y, h:i A') {
    if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return '-';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date($format, $timestamp) : htmlspecialchars($value);
}

function formatTimeSafe($value) {
    if (empty($value)) {
        return '-';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('h:i A', $timestamp) : htmlspecialchars($value);
}

function statusBadgeClass($status) {
    switch ($status) {
        case 'approved':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'rejected':
            return 'bg-red-100 text-red-700 border-red-200';
        case 'completed':
            return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'cancelled':
            return 'bg-slate-100 text-slate-600 border-slate-200';
        default:
            return 'bg-yellow-100 text-yellow-700 border-yellow-200';
    }
}

function paymentBadgeClass($status) {
    switch ($status) {
        case 'paid':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'refunded':
            return 'bg-blue-100 text-blue-700 border-blue-200';
        default:
            return 'bg-orange-100 text-orange-700 border-orange-200';
    }
}

function flowStepClass($state) {
    if ($state === 'done') {
        return [
            'circle' => 'bg-emerald-600 text-white border-emerald-600',
            'line' => 'bg-emerald-200',
            'card' => 'border-emerald-200 bg-emerald-50',
            'title' => 'text-emerald-800'
        ];
    }

    if ($state === 'current') {
        return [
            'circle' => 'bg-indigo-600 text-white border-indigo-600',
            'line' => 'bg-indigo-200',
            'card' => 'border-indigo-200 bg-indigo-50',
            'title' => 'text-indigo-800'
        ];
    }

    if ($state === 'danger') {
        return [
            'circle' => 'bg-red-600 text-white border-red-600',
            'line' => 'bg-red-200',
            'card' => 'border-red-200 bg-red-50',
            'title' => 'text-red-800'
        ];
    }

    return [
        'circle' => 'bg-white text-slate-400 border-slate-300',
        'line' => 'bg-slate-200',
        'card' => 'border-slate-200 bg-white',
        'title' => 'text-slate-600'
    ];
}


function translateSystemText($text) {
    if (empty($text)) return '';
    
    $dictionary = [
        'SYS_TIMEOUT_ADMIN' => 'Admin failed to approve the request within the functional timeframe.',
        'SYS_TIMEOUT_24H_RELEASE' => 'SLA Absolute Timeout: Auto-released after 24 hours of inactivity without consecutive bookings.',
        'SYS_TIMEOUT_30M_LOCK' => 'SLA Violation: Inspection delayed. Deposit temporarily frozen for maximum 24h.',
        '[SYSTEM ABSORBED]' => 'Chain of Custody Fault: Damage was detected, but a preceding SLA violation exists for this venue.'
    ];

    // 💡 升級：使用 stripos 達成 O(1) 的大小寫免疫，無懼髒數據
    foreach ($dictionary as $code => $translation) {
        if (stripos($text, $code) !== false) {
            return str_ireplace($code, $translation, $text);
        }
    }
    return $text;
}

$bookingRejected = ($status === 'rejected');
$bookingCancelled = ($status === 'cancelled');
$bookingApprovedOrDone = in_array($status, ['approved', 'completed'], true);
$bookingCompleted = ($status === 'completed');
$paymentDone = in_array($paymentStatus, ['paid', 'refunded'], true);

$flowSteps = [];

$flowSteps[] = [
    'title' => 'Request Submitted',
    'desc' => 'Your booking request has been created.',
    'meta' => formatDateTimeSafe($booking['created_at']),
    'state' => 'done',
    'icon' => 'file-check'
];

$flowSteps[] = [
    'title' => $paymentDone ? 'Payment Completed' : 'Payment Required',
    'desc' => $paymentDone ? 'Deposit payment has been recorded.' : 'Please complete payment before admin can review this booking.',
    'meta' => !empty($booking['transaction_ref']) ? 'Ref: ' . $booking['transaction_ref'] : strtoupper($paymentStatus ?: 'unpaid'),
    'state' => $paymentDone ? 'done' : 'current',
    'icon' => 'credit-card'
];

if ($bookingCancelled) {
    $reviewState = 'danger';
    $reviewTitle = 'Booking Cancelled';
    $reviewDesc = 'This booking was cancelled before admin review.';
} elseif ($bookingRejected) {
    $reviewState = 'danger';
    $reviewTitle = 'Booking Rejected';
    $reviewDesc = 'Admin has rejected this booking request.';
} elseif ($bookingApprovedOrDone) {
    $reviewState = 'done';
    $reviewTitle = 'Booking Approved';
    $reviewDesc = 'Admin has approved this booking request.';
} elseif ($paymentDone) {
    $reviewState = 'current';
    $reviewTitle = 'Waiting Admin Approval';
    $reviewDesc = 'Your paid request is waiting for admin review.';
} else {
    $reviewState = 'locked';
    $reviewTitle = 'Admin Review';
    $reviewDesc = 'Admin review will start after payment is completed.';
}

$flowSteps[] = [
    'title' => $reviewTitle,
    'desc' => $reviewDesc,
    'meta' => !empty($booking['admin_name']) ? $booking['admin_name'] . ' • ' . formatDateTimeSafe($booking['approve_date']) : 'Not reviewed yet',
    'state' => $reviewState,
    'icon' => 'shield-check'
];

if ($bookingCancelled) {
    $usageState = 'locked';
    $usageTitle = 'Venue Usage Cancelled';
    $usageDesc = 'This booking cannot continue because the payment deadline expired.';
} elseif ($bookingRejected) {
    $usageState = 'locked';
    $usageTitle = 'Venue Usage Cancelled';
    $usageDesc = 'This booking cannot continue because it was rejected.';
} elseif ($bookingCompleted) {
    $usageState = 'done';
    $usageTitle = 'Venue Usage Completed';
    $usageDesc = 'The booking period is completed.';
} elseif ($status === 'approved') {
    $usageState = 'current';
    $usageTitle = 'Venue Usage Upcoming';
    $usageDesc = 'Your venue has been approved and reserved.';
} else {
    $usageState = 'locked';
    $usageTitle = 'Venue Usage';
    $usageDesc = 'This step starts after admin approval.';
}

$flowSteps[] = [
    'title' => $usageTitle,
    'desc' => $usageDesc,
    'meta' => formatDateTimeSafe($booking['date_booked'], 'd M Y') . ' • ' . formatTimeSafe($booking['time_start']) . ' - ' . formatTimeSafe($booking['time_end']),
    'state' => $usageState,
    'icon' => 'calendar-check'
];

if ($bookingCancelled) {
    $inspectionState = 'locked';
    $inspectionTitle = 'Inspection Not Required';
    $inspectionDesc = 'Cancelled bookings do not require inspection.';
} elseif ($bookingRejected) {
    $inspectionState = 'locked';
    $inspectionTitle = 'Inspection Not Required';
    $inspectionDesc = 'Rejected bookings do not require inspection.';
} elseif ($hasInspection && in_array($inspectionStatus, ['passed', 'failed'], true)) {
    $inspectionState = $inspectionStatus === 'passed' ? 'done' : 'danger';
    $inspectionTitle = $inspectionStatus === 'passed' ? 'Inspection Passed' : 'Inspection Failed';
    $inspectionDesc = $inspectionStatus === 'passed' ? 'No issue was found during inspection.' : 'Damage or penalty may be recorded.';
} elseif ($hasInspection) {
    $inspectionState = 'current';
    $inspectionTitle = 'Inspection Pending';
    $inspectionDesc = 'Inspector has been assigned and inspection is waiting to be completed.';
} elseif ($bookingCompleted) {
    $inspectionState = 'current';
    $inspectionTitle = 'Waiting Inspection Assignment';
    $inspectionDesc = 'Admin needs to assign an inspector for this completed booking.';
} else {
    $inspectionState = 'locked';
    $inspectionTitle = 'Inspection';
    $inspectionDesc = 'Inspection will happen after venue usage is completed.';
}

$flowSteps[] = [
    'title' => $inspectionTitle,
    'desc' => $inspectionDesc,
    'meta' => !empty($booking['inspector_name']) ? $booking['inspector_name'] . ' • ' . formatDateTimeSafe($booking['inspected_at']) : 'No inspector assigned yet',
    'state' => $inspectionState,
    'icon' => 'clipboard-check'
];

if ($bookingCancelled) {
    $settlementState = 'locked';
    $settlementTitle = 'No Settlement Required';
    $settlementDesc = 'This booking was cancelled before payment was completed.';
    $isSystemTimeout = strpos($booking['cancel_reason'] ?? '', 'SYS_TIMEOUT') !== false;
    $settlementMeta = $isSystemTimeout ? 'System Auto-Cancellation' : 'Payment deadline expired';
} elseif ($bookingRejected) {
    $settlementState = $paymentStatus === 'refunded' ? 'done' : ($paymentDone ? 'current' : 'locked');
    $settlementTitle = $paymentStatus === 'refunded' ? 'Refund Completed' : 'Refund Pending';
    $settlementDesc = $paymentDone ? 'Rejected paid booking should be handled for refund by admin.' : 'No payment was completed, so no refund is required.';
    $settlementMeta = strtoupper($paymentStatus ?: 'unpaid');
} elseif ($hasReport) {
    $hasPendingSettlement = in_array($refundStatus, ['pending'], true) || in_array($penaltyStatus, ['pending'], true);
    $settlementState = $hasPendingSettlement ? 'current' : 'done';
    $settlementTitle = 'Refund / Penalty Result';
    $settlementDesc = 'Final inspection report has been generated.';
    $settlementMeta = 'Deduct: RM ' . number_format((float)$booking['final_deduct'], 2);
} elseif ($hasInspection && in_array($inspectionStatus, ['passed', 'failed'], true)) {
    $settlementState = 'current';
    $settlementTitle = 'Waiting Final Report';
    $settlementDesc = 'Inspection is completed and final refund/penalty report is waiting.';
    $settlementMeta = strtoupper($inspectionStatus);
} else {
    $settlementState = 'locked';
    $settlementTitle = 'Refund / Penalty Result';
    $settlementDesc = 'This step appears after inspection report is generated.';
    $settlementMeta = 'Not available yet';
}

$flowSteps[] = [
    'title' => $settlementTitle,
    'desc' => $settlementDesc,
    'meta' => $settlementMeta,
    'state' => $settlementState,
    'icon' => 'receipt'
];
?>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-5xl mx-auto">
        <div class="mb-8">
            <a href="my_bookings.php" class="inline-flex items-center text-sm text-indigo-600 font-bold hover:underline">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i>
                Back to My Bookings
            </a>

            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mt-4">
                <div>
                    <p class="text-xs text-slate-400 uppercase font-black tracking-widest">
                        Booking #<?php echo htmlspecialchars($booking['bid']); ?>
                    </p>

                    <h1 class="text-3xl font-extrabold text-slate-800 mt-1">
                        Booking Details & Progress
                    </h1>

                    <p class="text-sm text-slate-500 mt-1">
                        Track your booking status from request submission until final settlement.
                    </p>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm font-semibold">
                        <?php echo htmlspecialchars($_SESSION['success']); ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-semibold">
                        <?php echo htmlspecialchars($_SESSION['error']); ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Report Existing Damage Button -->
                <div class="mt-6">
                    <?php if ($damage_report): ?>
                        <button 
                            disabled
                            class="w-full inline-flex items-center justify-center px-5 py-3 bg-emerald-100 text-emerald-700 border border-emerald-200 text-sm font-bold rounded-lg cursor-not-allowed"
                        >
                            <i data-lucide="check-circle" class="w-4 h-4 mr-2"></i>
                            Damage Report Submitted
                        </button>

                    <?php elseif ($can_report_damage): ?>
                        <a 
                            href="report_damage.php?bid=<?php echo urlencode($booking['bid']); ?>"
                            class="w-full inline-flex items-center justify-center px-5 py-3 bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold rounded-lg shadow-sm transition"
                        >
                            <i data-lucide="camera" class="w-4 h-4 mr-2"></i>
                            Report Existing Damage
                        </a>

                    <?php else: ?>
                        <button 
                            disabled
                            class="w-full inline-flex items-center justify-center px-5 py-3 bg-slate-100 text-slate-400 text-sm font-bold rounded-lg cursor-not-allowed"
                        >
                            <i data-lucide="lock" class="w-4 h-4 mr-2"></i>
                            Report Existing Damage Not Available
                        </button>
                    <?php endif; ?>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a 
                        href="booking_print.php?bid=<?php echo urlencode($booking['bid']); ?>"
                        target="_blank"
                        class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-sm transition"
                    >
                        <i data-lucide="file-text" class="w-4 h-4 mr-2"></i>
                        View Report
                    </a>

                    <?php if ($status === "pending" && $paymentStatus === "unpaid"): ?>
                        <a 
                            href="mock_payment.php?bid=<?php echo urlencode($booking['bid']); ?>"
                            class="inline-flex items-center justify-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm transition"
                        >
                            <i data-lucide="credit-card" class="w-4 h-4 mr-2"></i>
                            Pay Now
                        </a>
                    <?php endif; ?>

                    <span class="px-3 py-1.5 text-xs font-black uppercase rounded-full border <?php echo statusBadgeClass($status); ?>">
                        <?php echo htmlspecialchars($status); ?>
                    </span>

                    <span class="px-3 py-1.5 text-xs font-black uppercase rounded-full border <?php echo paymentBadgeClass($paymentStatus); ?>">
                        <?php echo htmlspecialchars($paymentStatus ?: 'unpaid'); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-2 space-y-6">

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100">
                        <h2 class="text-xl font-bold text-slate-800">
                            Booking Process Flow
                        </h2>
                        <p class="text-sm text-slate-500 mt-1">
                            This progress is updated based on payment, admin approval, usage, inspection and report records.
                        </p>
                    </div>

                    <div class="p-6">
                        <div class="space-y-4">
                            <?php foreach ($flowSteps as $index => $step): ?>
                                <?php $classes = flowStepClass($step['state']); ?>

                                <div class="relative flex gap-4">
                                    <?php if ($index < count($flowSteps) - 1): ?>
                                        <div class="absolute left-5 top-11 bottom-[-18px] w-0.5 <?php echo $classes['line']; ?>"></div>
                                    <?php endif; ?>

                                    <div class="relative z-10 shrink-0 w-10 h-10 rounded-full border-2 flex items-center justify-center <?php echo $classes['circle']; ?>">
                                        <i data-lucide="<?php echo htmlspecialchars($step['icon']); ?>" class="w-5 h-5"></i>
                                    </div>

                                    <div class="flex-1 rounded-xl border p-4 <?php echo $classes['card']; ?>">
                                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                                            <div>
                                                <h3 class="font-extrabold <?php echo $classes['title']; ?>">
                                                    <?php echo htmlspecialchars($step['title']); ?>
                                                </h3>
                                                <p class="text-sm text-slate-600 mt-1">
                                                    <?php echo htmlspecialchars($step['desc']); ?>
                                                </p>
                                            </div>

                                            <span class="text-xs font-bold text-slate-500 sm:text-right">
                                                <?php echo htmlspecialchars($step['meta']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100">
                        <h2 class="text-xl font-bold text-slate-800">
                            Venue Information
                        </h2>
                    </div>

                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Venue</p>
                            <p class="text-slate-800 font-extrabold mt-1">
                                <?php echo htmlspecialchars($booking['vname']); ?>
                            </p>
                            <p class="text-xs text-slate-400 font-bold uppercase mt-1">
                                <?php echo htmlspecialchars($booking['category']); ?>
                            </p>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Booking Date</p>
                            <p class="text-slate-800 font-extrabold mt-1">
                                <?php echo formatDateTimeSafe($booking['date_booked'], 'd M Y'); ?>
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                <?php echo formatTimeSafe($booking['time_start']); ?> - <?php echo formatTimeSafe($booking['time_end']); ?>
                            </p>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Capacity</p>
                            <p class="text-slate-800 font-extrabold mt-1">
                                <?php echo (int)$booking['max_cap']; ?> Pax
                            </p>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Deposit</p>
                            <p class="text-emerald-600 font-extrabold mt-1">
                                RM <?php echo number_format((float)$booking['deposit'], 2); ?>
                            </p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="space-y-6">

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100">
                        <h2 class="text-lg font-extrabold text-slate-800">
                            Summary
                        </h2>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <p class="text-xs text-slate-400 font-black uppercase tracking-widest">Purpose</p>
                            <p class="text-sm text-slate-700 font-semibold mt-1">
                                <?php echo htmlspecialchars($booking['purpose'] ?? '-'); ?>
                            </p>
                        </div>

                        <div class="border-t pt-4">
                            <p class="text-xs text-slate-400 font-black uppercase tracking-widest">Payment</p>
                            <p class="text-sm text-slate-700 font-semibold mt-1">
                                <?php echo htmlspecialchars(strtoupper($paymentStatus ?: 'unpaid')); ?>
                            </p>

                            <?php if (!empty($booking['transaction_ref'])): ?>
                                <p class="text-xs text-slate-500 mt-1">
                                    Transaction: <?php echo htmlspecialchars($booking['transaction_ref']); ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="border-t pt-4">
                            <p class="text-xs text-slate-400 font-black uppercase tracking-widest">Admin Review</p>
                            <p class="text-sm text-slate-700 font-semibold mt-1">
                                <?php echo !empty($booking['admin_name']) ? htmlspecialchars($booking['admin_name']) : 'Not reviewed yet'; ?>
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                <?php echo formatDateTimeSafe($booking['approve_date']); ?>
                            </p>
                        </div>

                        <?php if ($status === 'cancelled'): ?>
                            <div class="border-t pt-4">
                                <p class="text-xs text-slate-400 font-black uppercase tracking-widest">Cancellation</p>

                                <p class="text-sm text-slate-700 font-semibold mt-1">
                                    Cancelled At:
                                    <?php echo formatDateTimeSafe($booking['cancelled_at']); ?>
                                </p>

                                <p class="text-xs text-slate-500 mt-1">
                                    Reason: 
                                    <?php 
                                        $reason = $booking['cancel_reason'] ?? 'Payment deadline expired';
                                        // 💡 只進行一次輸出，避免雙重印出
                                        echo htmlspecialchars(translateSystemText($reason)); 
                                    ?>
                                </p>

                                <?php if (!empty($booking['payment_due_at'])): ?>
                                    <p class="text-xs text-slate-500 mt-1">
                                        Payment Due:
                                        <?php echo formatDateTimeSafe($booking['payment_due_at']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="border-t pt-4">
                            <p class="text-xs text-slate-400 font-black uppercase tracking-widest">Inspection</p>
                            <p class="text-sm text-slate-700 font-semibold mt-1">
                                <?php echo $hasInspection ? htmlspecialchars(strtoupper($inspectionStatus)) : 'Not assigned yet'; ?>
                            </p>

                            <?php if (!empty($booking['inspector_name'])): ?>
                                <p class="text-xs text-slate-500 mt-1">
                                    Inspector: <?php echo htmlspecialchars($booking['inspector_name']); ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <?php if ($hasInspection): ?>
                            <div class="border-t pt-4">
                                <p class="text-xs text-slate-400 font-black uppercase tracking-widest">Damage / Penalty</p>

                                <p class="text-sm text-slate-700 font-semibold mt-1">
                                    Damage Cost: RM <?php echo number_format((float)$booking['damage_cost'], 2); ?>
                                </p>

                                <p class="text-sm text-slate-700 font-semibold mt-1">
                                    Penalty: RM <?php echo number_format((float)$booking['penalty'], 2); ?>
                                </p>

                                <?php if (!empty($booking['damage_desc'])): ?>
                                    <div class="border-t pt-4">
                                        <p class="text-xs text-slate-500 mt-2">
                                            <?php echo nl2br(htmlspecialchars(translateSystemText($booking['damage_desc']))); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($hasReport): ?>
                            <div class="border-t pt-4">
                                <p class="text-xs text-slate-400 font-black uppercase tracking-widest">Final Report</p>

                                <p class="text-sm text-slate-700 font-semibold mt-1">
                                    Final Deduct: RM <?php echo number_format((float)$booking['final_deduct'], 2); ?>
                                </p>

                                <p class="text-xs text-slate-500 mt-1">
                                    Refund: <?php echo htmlspecialchars(strtoupper($refundStatus ?: 'none')); ?>
                                </p>

                                <p class="text-xs text-slate-500 mt-1">
                                    Penalty: <?php echo htmlspecialchars(strtoupper($penaltyStatus ?: 'none')); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-indigo-50 rounded-2xl border border-indigo-100 p-5">
                    <div class="flex gap-3">
                        <i data-lucide="info" class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5"></i>

                        <p class="text-sm text-indigo-800 leading-relaxed">
                            If the progress is not updated yet, it means the related admin or inspection action has not been completed in the system.
                        </p>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<?php include("../includes/user_footer.php"); ?>

<script>
lucide.createIcons();
</script>