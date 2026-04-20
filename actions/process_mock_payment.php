<?php
// File: actions/process_mock_payment.php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Extract string format (e.g. BKG-0015) and convert to INT (15)
    $booking_ref = $_POST['booking_ref'];
    $raw_booking_id = (int)str_replace('BKG-', '', $booking_ref);
    $amount = (float)$_POST['amount'];

    // Simulate Network Latency
    sleep(1); 
    $transaction_id = 'TXN-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

    $conn->begin_transaction();

    try {
        // 💡 1. Update Booking Status
        $sql_booking = "UPDATE bookings SET payment_status = 'Paid', transaction_ref = ? WHERE booking_id = ?";
        $stmt_booking = $conn->prepare($sql_booking);
        $stmt_booking->bind_param("si", $transaction_id, $raw_booking_id);
        $stmt_booking->execute();

        // 💡 2. CRITICAL FIX: Insert into Payments table so Inspections module can find it
        $sql_payment = "INSERT INTO payments (booking_id, deposit_paid, payment_status) VALUES (?, ?, 'Deposit_Held')";
        $stmt_payment = $conn->prepare($sql_payment);
        $stmt_payment->bind_param("id", $raw_booking_id, $amount);
        $stmt_payment->execute();

        $conn->commit();

        // 3. Render Modern Success Splash Screen and Auto-Redirect
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Transaction Complete</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-slate-50 flex items-center justify-center min-h-screen">
            <div class="bg-white p-10 rounded-2xl shadow-xl text-center max-w-sm w-full border border-slate-100">
                <div class="w-20 h-20 bg-emerald-100 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h2 class="text-2xl font-extrabold text-slate-800 mb-2">Payment Verified</h2>
                <p class="text-slate-500 text-sm mb-4">Your deposit has been secured.</p>
                <div class="bg-slate-50 p-4 rounded-lg font-mono text-xs text-slate-600 font-bold mb-6">
                    REF: ' . $transaction_id . '
                </div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center justify-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Routing to Dashboard...
                </div>
            </div>
            <script>
                setTimeout(() => { window.location.href = "../user/homepage.php"; }, 2500);
            </script>
        </body>
        </html>';

    } catch (Exception $e) {
        $conn->rollback();
        die("Transaction Failed: " . $e->getMessage());
    }

    $conn->close();
}
?>