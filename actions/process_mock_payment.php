<?php
// File: actions/process_mock_payment.php
session_start();
require_once '../config/db.php';

// 💡 1. 降維打擊：全域接收 GET 與 POST 參數，相容各種前端命名
$raw_input = $_REQUEST['bid'] ?? $_REQUEST['booking_ref'] ?? $_REQUEST['booking_id'] ?? $_REQUEST['id'] ?? 0;

// 強制過濾並轉為整數 (相容 BKG- 前綴或純數字)
$bid = (int)str_ireplace('BKG-', '', $raw_input);

if ($bid === 0) {
    die("<div style='font-family:sans-serif; padding:20px; color:#ef4444;'>
         <h3>Fatal: Parameter Missing</h3>
         <p>No valid Booking ID was received by the payment processor.</p>
         <pre>Received Payload: " . print_r($_REQUEST, true) . "</pre>
         </div>");
}

// 模擬金流延遲與生成交易號
sleep(1); 
$transaction_id = 'TXN-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

$conn->begin_transaction();

try {
    // 💡 2. 狀態機檢查：先確認訂單是否存在以及當前狀態
    $check_sql = "SELECT payment_status FROM booking WHERE bid = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("i", $bid);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Target bid [{$bid}] does not exist in the database. Are you sure the booking was successfully created?");
    }

    $current_status = $result->fetch_assoc()['payment_status'];
    $stmt_check->close();

    if ($current_status === 'paid') {
        // 已經付款，視為成功，不報錯
        $conn->rollback(); // 撤銷無意義的交易
        $message = "Payment already processed for this booking.";
    } else {
        // 💡 3. 執行原子更新
        $sql_booking = "UPDATE booking SET payment_status = 'paid', transaction_ref = ? WHERE bid = ?";
        $stmt_booking = $conn->prepare($sql_booking);
        $stmt_booking->bind_param("si", $transaction_id, $bid);
        $stmt_booking->execute();
        
        if ($stmt_booking->affected_rows === 0) {
            throw new Exception("State Mutation Failed: Could not update the payment status.");
        }
        $stmt_booking->close();
        $conn->commit();
        $message = "Payment Verified Successfully.";
    }

    // 💡 4. 成功畫面與自動跳轉 (不依賴外部視圖，直接在這裡渲染企業級介面)
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
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h2 class="text-2xl font-extrabold text-slate-800 mb-2">' . $message . '</h2>
            <div class="bg-slate-50 p-3 rounded-lg font-mono text-xs text-slate-600 font-bold mb-6 mt-4 border border-slate-100">
                REF: ' . $transaction_id . ' <br> BID: ' . $bid . '
            </div>
            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Redirecting to dashboard...</p>
            <script>setTimeout(() => { window.location.href = "../user/homepage.php"; }, 2500);</script>
        </div>
    </body></html>';

} catch (Exception $e) {
    $conn->rollback();
    die("<div style='font-family:sans-serif; padding:20px; color:#ef4444; background:#fef2f2; border:1px solid #f87171; border-radius:8px; max-w:600px; margin:40px auto;'>
         <h3 style='margin-top:0;'>Transaction Fault</h3>
         <p><b>Error:</b> " . $e->getMessage() . "</p>
         <p style='font-size:12px; color:#7f1d1d; margin-top:20px;'>Please check if the booking ID ($bid) actually exists in your `booking` table.</p>
         <a href='../user/homepage.php' style='display:inline-block; margin-top:10px; padding:8px 16px; background:#ef4444; color:white; text-decoration:none; border-radius:4px; font-weight:bold; font-size:14px;'>Return to Home</a>
         </div>");
}

$conn->close();
?>