<?php
include("../includes/user_auth.php");
include("../config/db.php");
include("../includes/booking_functions.php");

expireUnpaidBookings($conn);

$user_id = $_SESSION['uid'];

if (!isset($_GET['bid']) || !ctype_digit($_GET['bid'])) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:50px; color:#ef4444;'><h2>Invalid Payment Access.</h2></div>");
}

$bid = intval($_GET['bid']);

$stmt = $conn->prepare("
    SELECT 
        b.bid,
        b.uid,
        b.status,
        b.payment_status,
        b.payment_due_at,
        TIMESTAMPDIFF(SECOND, NOW(), b.payment_due_at) AS remaining_seconds,
        b.date_booked,
        b.time_start,
        b.time_end,
        v.vname,
        v.deposit,
        vc.category
    FROM booking b
    JOIN venue v ON b.vid = v.vid
    JOIN vcategory vc ON v.vcid = vc.vcid
    WHERE b.bid = ?
      AND b.uid = ?
    LIMIT 1
");

$stmt->bind_param("is", $bid, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:50px; color:#ef4444;'><h2>Booking not found or access denied.</h2></div>");
}

$status = strtolower($booking['status']);
$payment_status = strtolower($booking['payment_status']);
$amount = number_format((float)$booking['deposit'], 2);

if ($payment_status === 'paid') {
    header("Location: booking_details.php?bid=" . urlencode($bid));
    exit;
}

if ($status === 'cancelled') {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:50px; color:#ef4444;'>
            <h2>Booking Cancelled</h2>
            <p>This booking was cancelled because the payment deadline expired.</p>
            <p><a href='venues.php'>Book another venue</a></p>
         </div>");
}

$remaining_seconds = max(0, (int)$booking['remaining_seconds']);

if ($remaining_seconds <= 0) {
    expireUnpaidBookings($conn);
    header("Location: my_bookings.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sandbox Payment Gateway</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-slate-50 min-h-screen flex items-center justify-center font-sans antialiased px-4">

    <div class="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full border border-slate-200">

        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="credit-card" class="w-8 h-8 text-indigo-600"></i>
            </div>

            <h2 class="text-2xl font-extrabold text-slate-800">
                Secure Checkout
            </h2>

            <p class="text-sm text-slate-500 mt-1">
                Complete your deposit before the payment deadline.
            </p>
        </div>

        <div class="bg-orange-50 border border-orange-200 text-orange-700 rounded-xl p-4 mb-6 text-center">
            <p class="text-xs font-black uppercase tracking-widest mb-1">
                Payment Time Remaining
            </p>

            <p id="countdown" class="text-2xl font-black font-mono">
                --:--
            </p>

            <p class="text-xs mt-1">
                Booking will be cancelled automatically if payment is not completed in time.
            </p>
        </div>

        <div class="bg-slate-50 p-5 rounded-xl border border-slate-100 mb-8 space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Booking ID</span>
                <span class="font-mono font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded">
                    <?php echo htmlspecialchars($booking['bid']); ?>
                </span>
            </div>

            <div class="flex justify-between items-center border-t border-slate-200 pt-3">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Venue</span>
                <span class="font-bold text-slate-700 text-right">
                    <?php echo htmlspecialchars($booking['vname']); ?>
                </span>
            </div>

            <div class="flex justify-between items-center border-t border-slate-200 pt-3">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Date / Time</span>
                <span class="font-bold text-slate-700 text-right text-sm">
                    <?php echo htmlspecialchars($booking['date_booked']); ?><br>
                    <?php echo htmlspecialchars($booking['time_start']); ?> - <?php echo htmlspecialchars($booking['time_end']); ?>
                </span>
            </div>

            <div class="flex justify-between items-center border-t border-slate-200 pt-3">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Amount Due</span>
                <span class="font-mono font-black text-emerald-600 text-2xl">
                    RM <?php echo $amount; ?>
                </span>
            </div>
        </div>

        <form action="../actions/process_mock_payment.php" method="POST" id="paymentForm">
            <input type="hidden" name="bid" value="<?php echo htmlspecialchars($bid); ?>">

            <button type="submit" id="payBtn" class="w-full py-4 bg-indigo-600 text-white text-sm font-bold rounded-xl shadow-md hover:bg-indigo-700 hover:shadow-lg transition-all flex items-center justify-center transform active:scale-95">
                <i data-lucide="lock" class="w-4 h-4 mr-2"></i>
                Pay RM <?php echo $amount; ?>
            </button>
        </form>

        <div class="grid grid-cols-2 gap-3 mt-4">
            <a href="my_bookings.php" class="py-3 text-center bg-white border border-slate-300 text-slate-700 text-sm font-bold rounded-xl hover:bg-slate-50 transition">
                Pay Later
            </a>

            <a href="booking_details.php?bid=<?php echo urlencode($bid); ?>" class="py-3 text-center bg-white border border-slate-300 text-slate-700 text-sm font-bold rounded-xl hover:bg-slate-50 transition">
                View Booking
            </a>
        </div>

        <div class="mt-6 text-center flex items-center justify-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">
            <i data-lucide="shield-check" class="w-4 h-4 mr-1 text-emerald-500"></i>
            Sandbox Environment
        </div>
    </div>

    <script>
        lucide.createIcons();

        let remainingSeconds = <?php echo (int)$remaining_seconds; ?>;
            let countdownTimer = null;

        function updateCountdown() {
            const countdown = document.getElementById('countdown');

            if (!countdown) {
                return;
            }

            if (remainingSeconds <= 0) {
                countdown.innerText = "Expired";

                if (countdownTimer) {
                    clearInterval(countdownTimer);
                }

                setTimeout(function () {
                    window.location.href = "my_bookings.php";
                }, 1000);

                return;
            }

            const hours = Math.floor(remainingSeconds / 3600);
            const minutes = Math.floor((remainingSeconds % 3600) / 60);
            const seconds = remainingSeconds % 60;

            if (hours > 0) {
                countdown.innerText =
                    String(hours).padStart(2, '0') + "h " +
                    String(minutes).padStart(2, '0') + "m " +
                    String(seconds).padStart(2, '0') + "s";
            } else {
                countdown.innerText =
                    String(minutes).padStart(2, '0') + ":" +
                    String(seconds).padStart(2, '0');
            }

            remainingSeconds = remainingSeconds - 1;
        }

        updateCountdown();
        countdownTimer = setInterval(updateCountdown, 1000);

        document.getElementById('paymentForm').addEventListener('submit', function() {
            const btn = document.getElementById('payBtn');
            btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 mr-2 animate-spin"></i> Processing Transaction...';
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            btn.disabled = true;
            lucide.createIcons();
        });
    </script>

</body>
</html>