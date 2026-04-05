<?php
// 檔案路徑：actions/process_mock_payment.php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'];
    $amount = $_POST['amount'];
    $payment_type = $_POST['payment_type'];
    $bank = $_POST['bank'];

    // 💡 1. 模擬網路延遲 (Simulate API Latency)
    // 讓伺服器暫停 2 秒鐘，創造「正在與銀行連線」的逼真錯覺
    sleep(2); 

    // 💡 2. 產生一組假的銀行交易流水號 (Mock Transaction ID)
    // 格式例如：TXN-847A9B2C
    $transaction_id = 'TXN-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

    // 💡 3. 更新資料庫狀態 (Transaction Update)
    // 這裡你需要根據你實際的資料表結構來修改。
    // 假設我們更新 bookings 表裡的 payment_status：
    
    $sql = "UPDATE bookings SET payment_status = 'Paid', transaction_ref = ? WHERE booking_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // 注意：如果你的 booking_id 是整數 (INT)，這裡的 "ss" 要改成 "si"
        $stmt->bind_param("ss", $transaction_id, $booking_id);
        
        if ($stmt->execute()) {
            // 💡 4. 付款成功，引導回系統並顯示特效
            echo "<!DOCTYPE html>
                  <html>
                  <head>
                      <title>Payment Successful</title>
                      <style>
                          body { display: flex; justify-content: center; align-items: center; height: 100vh; font-family: sans-serif; background-color: #f4f7f6; text-align: center; }
                          .success-box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
                          h1 { color: #28a745; }
                      </style>
                  </head>
                  <body>
                      <div class='success-box'>
                          <h1 style='font-size: 60px; margin: 0;'>✅</h1>
                          <h2>付款成功 (Payment Successful)</h2>
                          <p>交易序號 (TXN ID): <strong>{$transaction_id}</strong></p>
                          <p>系統已自動更新您的預約狀態。</p>
                          <p>正在為您導回系統主頁...</p>
                      </div>
                      <script>
                          // 3秒後自動跳轉回前台 User Dashboard (請改成你前端隊友的實際路徑)
                          setTimeout(function() {
                              window.location.href = '../index.php'; // 或者 '../user/dashboard.php'
                          }, 3000);
                      </script>
                  </body>
                  </html>";
        } else {
            echo "資料庫更新失敗: " . $stmt->error;
        }
        $stmt->close();
    }
}
$conn->close();
?>