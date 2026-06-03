<?php
session_start();
require_once '../config/db.php';
require_once '../includes/booking_functions.php';

$uid = $_SESSION['uid'] ?? null;

if (!$uid) {
    header("Location: ../User/user_login.php");
    exit;
}

expireUnpaidBookings($conn);

$bid = intval($_POST['bid'] ?? 0);

if ($bid === 0) {
    die("Invalid Booking ID.");
}

$conn->begin_transaction();

try {
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

    if (!$booking) {
        throw new Exception("Booking not found or access denied.");
    }

    if ($booking['status'] === 'cancelled') {
        throw new Exception("This booking has been cancelled.");
    }

    if ($booking['payment_status'] === 'paid') {
        $conn->rollback();
        header("Location: ../User/booking_details.php?bid=" . urlencode($bid));
        exit;
    }

    if (empty($booking['payment_due_at']) || strtotime($booking['payment_due_at']) < time()) {
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

        die("<div style='font-family:sans-serif; text-align:center; margin-top:50px; color:#ef4444;'>
                <h2>Payment Deadline Expired</h2>
                <p>This booking has been cancelled because payment was not completed in time.</p>
                <p><a href='../User/venues.php'>Book another venue</a></p>
             </div>");
    }

    sleep(1);

    $transaction_id = 'TXN-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

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
    $stmt_update->execute();

    if ($stmt_update->affected_rows === 0) {
        throw new Exception("Payment could not be processed.");
    }

    $stmt_update->close();
    $conn->commit();

    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Transaction Complete</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-slate-50 flex items-center justify-center min-h-screen font-sans">
        <div class="bg-white p-10 rounded-2xl shadow-lg border border-slate-200 text-center max-w-sm w-full">
            <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h2 class="text-2xl font-extrabold text-slate-800 mb-2">
                Payment Verified Successfully
            </h2>

            <div class="bg-slate-50 p-3 rounded-lg font-mono text-xs text-slate-600 font-bold mb-6 mt-4 border border-slate-100">
                REF: ' . htmlspecialchars($transaction_id) . ' <br>
                BID: ' . htmlspecialchars($bid) . '
            </div>

            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">
                Redirecting to booking details...
            </p>

            <script>
                setTimeout(() => {
                    window.location.href = "../User/booking_details.php?bid=' . urlencode($bid) . '";
                }, 2000);
            </script>
        </div>
    </body>
    </html>';

} catch (Exception $e) {
    $conn->rollback();

    die("<div style='font-family:sans-serif; padding:20px; color:#ef4444; background:#fef2f2; border:1px solid #f87171; border-radius:8px; max-width:600px; margin:40px auto;'>
            <h3 style='margin-top:0;'>Transaction Fault</h3>
            <p><b>Error:</b> " . htmlspecialchars($e->getMessage()) . "</p>
            <p><a href='../User/my_bookings.php'>Back to My Bookings</a></p>
         </div>");
}

$conn->close();
?>