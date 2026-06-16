<?php
// This section checks mock payment details and updates the booking payment status.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/booking_functions.php';

$uid = $_SESSION['uid'] ?? null;

if (!$uid)
{
    header("Location: ../User/user_login.php");
    exit;
}

function showPaymentPage($title, $message, $type = 'error', $bid = 0, $transaction_id = '')
{
    $isSuccess = ($type === 'success');
    $iconBg = $isSuccess ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600';
    $titleColor = $isSuccess ? 'text-slate-800' : 'text-red-700';
    $safeTitle = htmlspecialchars($title);
    $safeMessage = htmlspecialchars($message);
    $safeBid = htmlspecialchars((string)$bid);
    $safeTxn = htmlspecialchars($transaction_id);
    $redirect = $isSuccess && $bid > 0 ? '../User/booking_details.php?bid=' . urlencode((string)$bid) : '../User/my_bookings.php';

    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . $safeTitle . '</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-slate-50 flex items-center justify-center min-h-screen font-sans px-4">
        <div class="bg-white p-10 rounded-2xl shadow-lg border border-slate-200 text-center max-w-md w-full">
            <div class="w-16 h-16 ' . $iconBg . ' rounded-full flex items-center justify-center mx-auto mb-4">
                ' . ($isSuccess
                    ? '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>'
                    : '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>'
                ) . '
            </div>

            <h2 class="text-2xl font-extrabold ' . $titleColor . ' mb-2">' . $safeTitle . '</h2>
            <p class="text-sm text-slate-500 leading-relaxed">' . $safeMessage . '</p>';

    if ($isSuccess)
    {
        echo '<div class="bg-slate-50 p-3 rounded-lg font-mono text-xs text-slate-600 font-bold mb-6 mt-4 border border-slate-100">
                REF: ' . $safeTxn . ' <br>
                BID: ' . $safeBid . '
              </div>
              <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Redirecting to booking details...</p>
              <script>setTimeout(() => { window.location.href = "' . $redirect . '"; }, 2000);</script>';
    } else
    {
        echo '<div class="grid grid-cols-1 gap-3 mt-6">
                ' . ($bid > 0 ? '<a href="../User/mock_payment.php?bid=' . urlencode((string)$bid) . '" class="py-3 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 transition">Try Payment Again</a>' : '') . '
                <a href="../User/my_bookings.php" class="py-3 bg-white border border-slate-300 text-slate-700 text-sm font-bold rounded-xl hover:bg-slate-50 transition">Back to My Bookings</a>
              </div>';
    }

    echo '</div></body></html>';
    exit;
}

function validateCardInput($card_number, $expiry, $cvv, $cardholder)
{
    $digits = preg_replace('/\D/', '', $card_number);

    if (trim($cardholder) === '')
    {
        return 'Please enter the cardholder name.';
    }

    if (!preg_match('/^\d{16}$/', $digits))
    {
        return 'Please enter a valid 16-digit card number.';
    }

    if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry))
    {
        return 'Please enter a valid expiry date in MM/YY format.';
    }

    [$month, $year] = explode('/', $expiry);
    $expiryTimestamp = strtotime('20' . $year . '-' . $month . '-01 last day of 23:59:59');
    if ($expiryTimestamp === false || $expiryTimestamp < time())
    {
        return 'This card has expired.';
    }

    if (!preg_match('/^\d{3,4}$/', $cvv))
    {
        return 'Please enter a valid CVV.';
    }

    if ($digits === '4000000000000002')
    {
        return 'The card was declined by the simulated bank.';
    }

    return '';
}

expireUnpaidBookings($conn);

$bid = intval($_POST['bid'] ?? 0);
$payment_method = strtolower(trim($_POST['payment_method'] ?? ''));

if ($bid === 0)
{
    showPaymentPage('Invalid Payment', 'Invalid booking ID was provided.', 'error');
}

if (!in_array($payment_method, ['tng', 'card'], true))
{
    showPaymentPage('Invalid Payment Method', 'Please select Touch n Go QR or Credit / Debit Card payment.', 'error', $bid);
}

if ($payment_method === 'card')
{
    $cardError = validateCardInput(
        $_POST['card_number'] ?? '',
        $_POST['card_expiry'] ?? '',
        $_POST['card_cvv'] ?? '',
        $_POST['cardholder_name'] ?? ''
    );

    if ($cardError !== '')
    {
        showPaymentPage('Payment Failed', $cardError, 'error', $bid);
    }
}

$conn->begin_transaction();

try
{
    $stmt = $conn->prepare("
        SELECT
            bid,
            uid,
            status,
            payment_status,
            payment_due_at
        FROM booking
        WHERE bid = ?
          AND uid = ?
        FOR UPDATE
    ");

    $stmt->bind_param("is", $bid, $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();

    if (!$booking)
    {
        throw new Exception("Booking not found or access denied.");
    }

    if ($booking['status'] === 'cancelled')
    {
        throw new Exception("This booking has been cancelled.");
    }

    if ($booking['payment_status'] === 'paid')
    {
        $conn->rollback();
        header("Location: ../User/booking_details.php?bid=" . urlencode((string)$bid));
        exit;
    }

    if (empty($booking['payment_due_at']) || strtotime($booking['payment_due_at']) < time())
    {
        $cancel_stmt = $conn->prepare("
            UPDATE booking
            SET
                status = 'cancelled',
                cancelled_at = NOW(),
                cancel_reason = 'Payment deadline expired'
            WHERE bid = ?
              AND uid = ?
        ");

        $cancel_stmt->bind_param("is", $bid, $uid);
        $cancel_stmt->execute();
        $cancel_stmt->close();

        $conn->commit();
        showPaymentPage('Payment Deadline Expired', 'This booking has been cancelled because payment was not completed in time.', 'error', $bid);
    }

    sleep(1);

    $methodLabel = ($payment_method === 'tng') ? 'TNG' : 'CARD';
    $transaction_id = 'TXN-' . $methodLabel . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

    $columnCheck = $conn->query("SHOW COLUMNS FROM booking LIKE 'payment_method'");
    $hasPaymentMethod = $columnCheck && $columnCheck->num_rows > 0;

    $columnCheck = $conn->query("SHOW COLUMNS FROM booking LIKE 'paid_at'");
    $hasPaidAt = $columnCheck && $columnCheck->num_rows > 0;

    if ($hasPaymentMethod && $hasPaidAt)
    {
        $stmt_update = $conn->prepare("
            UPDATE booking
            SET
                payment_status = 'paid',
                transaction_ref = ?,
                payment_method = ?,
                paid_at = NOW()
            WHERE bid = ?
              AND uid = ?
              AND status = 'pending'
              AND payment_status = 'unpaid'
        ");
        $stmt_update->bind_param("ssis", $transaction_id, $payment_method, $bid, $uid);
    } elseif ($hasPaymentMethod)
    {
        $stmt_update = $conn->prepare("
            UPDATE booking
            SET
                payment_status = 'paid',
                transaction_ref = ?,
                payment_method = ?
            WHERE bid = ?
              AND uid = ?
              AND status = 'pending'
              AND payment_status = 'unpaid'
        ");
        $stmt_update->bind_param("ssis", $transaction_id, $payment_method, $bid, $uid);
    } elseif ($hasPaidAt)
    {
        $stmt_update = $conn->prepare("
            UPDATE booking
            SET
                payment_status = 'paid',
                transaction_ref = ?,
                paid_at = NOW()
            WHERE bid = ?
              AND uid = ?
              AND status = 'pending'
              AND payment_status = 'unpaid'
        ");
        $stmt_update->bind_param("sis", $transaction_id, $bid, $uid);
    } else
    {
        $stmt_update = $conn->prepare("
            UPDATE booking
            SET
                payment_status = 'paid',
                transaction_ref = ?
            WHERE bid = ?
              AND uid = ?
              AND status = 'pending'
              AND payment_status = 'unpaid'
        ");
        $stmt_update->bind_param("sis", $transaction_id, $bid, $uid);
    }

    $stmt_update->execute();

    if ($stmt_update->affected_rows === 0)
    {
        throw new Exception("Payment could not be processed. Please make sure the booking is still pending and unpaid.");
    }

    $stmt_update->close();
    $conn->commit();

    showPaymentPage('Payment Verified Successfully', 'Your simulated payment has been completed. The booking is now ready for admin review.', 'success', $bid, $transaction_id);

} catch (Exception $e)
{
    $conn->rollback();
    showPaymentPage('Transaction Fault', $e->getMessage(), 'error', $bid);
}

$conn->close();
?>
