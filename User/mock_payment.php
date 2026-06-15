<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/user_auth.php';
require_once __DIR__ . '/../includes/booking_functions.php';

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
        b.transaction_ref,
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
$raw_amount = (float)$booking['deposit'];

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

$qr_seed = 'CVBMS|BID=' . $bid . '|AMOUNT=' . number_format($raw_amount, 2, '.', '') . '|USER=' . $user_id;
$hash = hash('sha256', $qr_seed);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sandbox Payment Gateway</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .qr-grid {
            display: grid;
            grid-template-columns: repeat(13, 1fr);
            gap: 3px;
        }
        .qr-cell {
            aspect-ratio: 1 / 1;
            border-radius: 2px;
        }
        .method-panel { display: none; }
        .method-panel.active { display: block; }
    </style>
</head>

<body class="bg-slate-50 min-h-screen font-sans antialiased px-4 py-10">
    <div class="max-w-5xl mx-auto">
        <div class="mb-6 flex items-center justify-between">
            <a href="my_bookings.php" class="inline-flex items-center text-sm font-bold text-slate-500 hover:text-indigo-600 transition">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                Back to My Bookings
            </a>

            <div class="inline-flex items-center text-[10px] font-black text-slate-400 uppercase tracking-widest">
                <i data-lucide="shield-check" class="w-4 h-4 mr-1 text-emerald-500"></i>
                Sandbox Environment
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden sticky top-8">
                    <div class="bg-slate-900 px-6 py-5 flex items-center">
                        <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center mr-3">
                            <i data-lucide="receipt" class="w-5 h-5 text-white"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-extrabold text-white tracking-wide">Payment Summary</h2>
                            <p class="text-xs text-slate-400 font-semibold">Campus Venue Rental System</p>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="bg-orange-50 border border-orange-200 text-orange-700 rounded-xl p-4 mb-6 text-center">
                            <p class="text-xs font-black uppercase tracking-widest mb-1">Payment Time Remaining</p>
                            <p id="countdown" class="text-2xl font-black font-mono">--:--</p>
                            <p class="text-xs mt-1">Booking will be cancelled automatically if payment is not completed in time.</p>
                        </div>

                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Booking ID</span>
                                <span class="font-mono font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded">
                                    #<?php echo htmlspecialchars($booking['bid']); ?>
                                </span>
                            </div>

                            <div class="border-t border-slate-100 pt-4">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Venue</p>
                                <p class="font-black text-slate-800"><?php echo htmlspecialchars($booking['vname']); ?></p>
                                <p class="text-xs text-slate-400 font-bold uppercase mt-1"><?php echo htmlspecialchars($booking['category']); ?></p>
                            </div>

                            <div class="border-t border-slate-100 pt-4">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Date / Time</p>
                                <p class="font-bold text-slate-700 text-sm">
                                    <?php echo date("d M Y", strtotime($booking['date_booked'])); ?>,
                                    <?php echo substr($booking['time_start'], 0, 5); ?> - <?php echo substr($booking['time_end'], 0, 5); ?>
                                </p>
                            </div>

                            <div class="border-t border-slate-100 pt-4 flex justify-between items-center">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Deposit Amount</p>
                                    <p class="text-xs text-slate-400 mt-1">Refundable after inspection if no issue is found.</p>
                                </div>
                                <span class="font-mono font-black text-emerald-600 text-2xl whitespace-nowrap">
                                    RM <?php echo $amount; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100">
                        <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mb-4">
                            <i data-lucide="credit-card" class="w-8 h-8 text-indigo-600"></i>
                        </div>
                        <h1 class="text-2xl font-extrabold text-slate-800">Secure Checkout</h1>
                        <p class="text-sm text-slate-500 mt-1">Choose a simulated payment method to complete your deposit payment.</p>
                    </div>

                    <div class="p-6">
                        <?php if (isset($_GET['error']) && $_GET['error'] !== ''): ?>
                            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-semibold">
                                <?php echo htmlspecialchars($_GET['error']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                            <button type="button" data-method-tab="tng" class="method-tab border-2 border-indigo-600 bg-indigo-50 text-indigo-700 rounded-xl p-4 text-left transition">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-lg bg-white flex items-center justify-center mr-3 shadow-sm">
                                        <i data-lucide="qr-code" class="w-5 h-5"></i>
                                    </div>
                                    <div>
                                        <p class="font-black text-sm">Touch 'n Go QR</p>
                                        <p class="text-xs opacity-75">Scan and confirm payment</p>
                                    </div>
                                </div>
                            </button>

                            <button type="button" data-method-tab="card" class="method-tab border-2 border-slate-200 hover:border-indigo-300 bg-white text-slate-700 rounded-xl p-4 text-left transition">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-lg bg-slate-50 flex items-center justify-center mr-3 shadow-sm">
                                        <i data-lucide="credit-card" class="w-5 h-5"></i>
                                    </div>
                                    <div>
                                        <p class="font-black text-sm">Credit / Debit Card</p>
                                        <p class="text-xs opacity-75">Card payment simulation</p>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <div id="panel-tng" class="method-panel active">
                            <div class="border border-slate-200 rounded-2xl p-6 bg-slate-50">
                                <div class="flex flex-col items-center">
                                    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm w-56">
                                        <div class="qr-grid">
                                            <?php for ($i = 0; $i < 169; $i++): ?>
                                                <?php
                                                    $char = hexdec($hash[$i % strlen($hash)]);
                                                    $isFinder = ($i < 39 && $i % 13 < 3) || ($i < 39 && $i % 13 > 9) || ($i > 129 && $i % 13 < 3);
                                                    $dark = $isFinder || (($char + $i) % 3 === 0);
                                                ?>
                                                <div class="qr-cell <?php echo $dark ? 'bg-slate-900' : 'bg-white'; ?>"></div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>

                                    <form action="../actions/process_mock_payment.php" method="POST" class="mt-5 payment-submit-form">
                                        <input type="hidden" name="bid" value="<?php echo htmlspecialchars($bid); ?>">
                                        <input type="hidden" name="payment_method" value="tng">
                                        <button type="submit" class="payment-btn px-6 py-3 bg-indigo-600 text-white text-sm font-bold rounded-xl shadow-md hover:bg-indigo-700 transition-all inline-flex items-center justify-center">
                                            <i data-lucide="check-circle" class="w-4 h-4 mr-2"></i>
                                            I Have Paid with TNG
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div id="panel-card" class="method-panel">
                            <form action="../actions/process_mock_payment.php" method="POST" class="border border-slate-200 rounded-2xl p-6 bg-slate-50 payment-submit-form">
                                <input type="hidden" name="bid" value="<?php echo htmlspecialchars($bid); ?>">
                                <input type="hidden" name="payment_method" value="card">

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Cardholder Name</label>
                                        <input type="text" name="cardholder_name" placeholder="Name on card" class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" required>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Card Number</label>
                                        <input type="text" name="card_number" id="card_number" inputmode="numeric" maxlength="19" placeholder="4242 4242 4242 4242" class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none" required>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Expiry</label>
                                            <input type="text" name="card_expiry" id="card_expiry" maxlength="5" placeholder="MM/YY" class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none" required>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">CVV</label>
                                            <input type="password" name="card_cvv" inputmode="numeric" maxlength="4" placeholder="123" class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none" required>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="payment-btn mt-6 w-full py-4 bg-indigo-600 text-white text-sm font-bold rounded-xl shadow-md hover:bg-indigo-700 hover:shadow-lg transition-all flex items-center justify-center transform active:scale-95">
                                    <i data-lucide="lock" class="w-4 h-4 mr-2"></i>
                                    Pay RM <?php echo $amount; ?> by Card
                                </button>

                                <p class="text-xs text-slate-400 mt-4 text-center">
                                    This is a simulation. Card details are validated for testing only and will not be stored.
                                </p>
                            </form>
                        </div>

                        <div class="grid grid-cols-2 gap-3 mt-6">
                            <a href="my_bookings.php" class="py-3 text-center bg-white border border-slate-300 text-slate-700 text-sm font-bold rounded-xl hover:bg-slate-50 transition">Pay Later</a>
                            <a href="booking_details.php?bid=<?php echo urlencode($bid); ?>" class="py-3 text-center bg-white border border-slate-300 text-slate-700 text-sm font-bold rounded-xl hover:bg-slate-50 transition">View Booking</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        let remainingSeconds = <?php echo (int)$remaining_seconds; ?>;
        let countdownTimer = null;

        function updateCountdown() {
            const countdown = document.getElementById('countdown');
            if (!countdown) return;

            if (remainingSeconds <= 0) {
                countdown.innerText = "Expired";
                if (countdownTimer) clearInterval(countdownTimer);
                setTimeout(function () { window.location.href = "my_bookings.php"; }, 1000);
                return;
            }

            const hours = Math.floor(remainingSeconds / 3600);
            const minutes = Math.floor((remainingSeconds % 3600) / 60);
            const seconds = remainingSeconds % 60;

            if (hours > 0) {
                countdown.innerText = String(hours).padStart(2, '0') + "h " + String(minutes).padStart(2, '0') + "m " + String(seconds).padStart(2, '0') + "s";
            } else {
                countdown.innerText = String(minutes).padStart(2, '0') + ":" + String(seconds).padStart(2, '0');
            }

            remainingSeconds--;
        }

        updateCountdown();
        countdownTimer = setInterval(updateCountdown, 1000);

        const tabs = document.querySelectorAll('.method-tab');
        const panels = {
            tng: document.getElementById('panel-tng'),
            card: document.getElementById('panel-card')
        };

        tabs.forEach(tab => {
            tab.addEventListener('click', function () {
                const method = this.dataset.methodTab;

                tabs.forEach(item => {
                    item.classList.remove('border-indigo-600', 'bg-indigo-50', 'text-indigo-700');
                    item.classList.add('border-slate-200', 'bg-white', 'text-slate-700');
                });

                this.classList.remove('border-slate-200', 'bg-white', 'text-slate-700');
                this.classList.add('border-indigo-600', 'bg-indigo-50', 'text-indigo-700');

                Object.values(panels).forEach(panel => panel.classList.remove('active'));
                panels[method].classList.add('active');
            });
        });

        const cardNumberInput = document.getElementById('card_number');
        const cardExpiryInput = document.getElementById('card_expiry');

        if (cardNumberInput) {
            cardNumberInput.addEventListener('input', function () {
                let value = this.value.replace(/\D/g, '').slice(0, 16);
                this.value = value.replace(/(.{4})/g, '$1 ').trim();
            });
        }

        if (cardExpiryInput) {
            cardExpiryInput.addEventListener('input', function () {
                let value = this.value.replace(/\D/g, '').slice(0, 4);
                if (value.length >= 3) value = value.slice(0, 2) + '/' + value.slice(2);
                this.value = value;
            });
        }

        document.querySelectorAll('.payment-submit-form').forEach(form => {
            form.addEventListener('submit', function () {
                const btn = this.querySelector('.payment-btn');
                if (!btn) return;
                btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 mr-2 animate-spin"></i> Processing Transaction...';
                btn.classList.add('opacity-75', 'cursor-not-allowed');
                btn.disabled = true;
                lucide.createIcons();
            });
        });
    </script>
</body>
</html>
