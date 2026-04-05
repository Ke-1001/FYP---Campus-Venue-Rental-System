<?php
// 檔案路徑：mock_payment.php
session_start();

$booking_id = isset($_GET['booking_id']) ? $_GET['booking_id'] : 'BKG-0000';
$amount = isset($_GET['amount']) ? $_GET['amount'] : '0.00';
$payment_type = isset($_GET['type']) ? $_GET['type'] : 'Deposit';

if ($amount <= 0) {
    die("🚨 無效的支付請求！");
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>Secure Payment Gateway (Sandbox)</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #e9ecef; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .payment-container { background: #fff; padding: 35px; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.1); width: 100%; max-width: 450px; border-top: 5px solid #004085; position: relative; overflow: hidden; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .header h2 { margin: 0; color: #004085; font-size: 22px; }
        .header p { color: #6c757d; font-size: 13px; margin-top: 5px; }
        .amount-display { font-size: 28px; font-weight: bold; text-align: center; color: #28a745; margin: 20px 0; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #495057; font-weight: bold; font-size: 13px; }
        input { width: 100%; padding: 10px 12px; border: 1px solid #ced4da; border-radius: 6px; box-sizing: border-box; font-size: 15px; font-family: monospace; }
        input:focus { border-color: #80bdff; outline: 0; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }
        
        .row { display: flex; gap: 15px; }
        .row .form-group { flex: 1; }
        
        .btn-pay { width: 100%; padding: 14px; background-color: #004085; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-pay:hover { background-color: #002752; }
        
        /* OTP 區塊預設隱藏 */
        #step-2-otp { display: none; text-align: center; }
        .otp-inputs { display: flex; justify-content: center; gap: 10px; margin: 20px 0; }
        .otp-inputs input { width: 45px; height: 50px; text-align: center; font-size: 24px; font-weight: bold; border: 2px solid #ced4da; border-radius: 8px; padding: 0; }
        .otp-message { font-size: 14px; color: #6c757d; margin-bottom: 20px; line-height: 1.5; }
        
        .security-badge { text-align: center; margin-top: 20px; font-size: 11px; color: #adb5bd; display: flex; align-items: center; justify-content: center; gap: 5px; }
    </style>
</head>
<body>

<div class="payment-container">
    
    <div id="step-1-card">
        <div class="header">
            <h2>🔒 Secure Checkout</h2>
            <p>Order Ref: #<?php echo htmlspecialchars($booking_id); ?> (<?php echo htmlspecialchars($payment_type); ?>)</p>
        </div>

        <div class="amount-display">
            RM <?php echo number_format($amount, 2); ?>
        </div>

        <form id="cardForm" onsubmit="triggerOTP(event)">
            <div class="form-group">
                <label>Cardholder Name</label>
                <input type="text" placeholder="e.g. SITI NURHALIZA" required autocomplete="off" style="font-family: inherit;">
            </div>

            <div class="form-group">
                <label>Card Number</label>
                <input type="text" id="cardNumber" placeholder="0000 0000 0000 0000" maxlength="19" required autocomplete="off" oninput="formatCardNumber(this)">
            </div>

            <div class="row">
                <div class="form-group">
                    <label>Expiry Date</label>
                    <input type="text" placeholder="MM/YY" maxlength="5" required autocomplete="off" oninput="formatExpiry(this)">
                </div>
                <div class="form-group">
                    <label>CVV / CVC</label>
                    <input type="password" placeholder="•••" maxlength="3" required autocomplete="off">
                </div>
            </div>

            <button type="submit" class="btn-pay" id="proceedBtn">Proceed to Verification</button>
        </form>
    </div>

    <div id="step-2-otp">
        <div class="header">
            <h2>📱 Bank Authentication</h2>
            <p>3D Secure Verification</p>
        </div>
        
        <div class="otp-message">
            A 6-digit verification code has been sent via SMS to your registered mobile number <strong>(+60 12-***-**89)</strong>.<br>
            Please enter the code to authorize this transaction.
        </div>

        <form action="actions/process_mock_payment.php" method="POST" id="finalPaymentForm">
            <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_id); ?>">
            <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
            <input type="hidden" name="payment_type" value="<?php echo htmlspecialchars($payment_type); ?>">
            
            <div class="otp-inputs">
                <input type="text" id="otp_input" maxlength="6" style="width: 150px; letter-spacing: 10px;" required autocomplete="off">
            </div>

            <button type="button" class="btn-pay" onclick="verifyOTP()" id="confirmBtn" style="background-color: #28a745;">Confirm & Pay</button>
        </form>
        
        <p style="font-size: 12px; margin-top: 15px;"><a href="#" style="color: #004085;" onclick="alert('In sandbox mode, just check the fake SMS pop-up again.')">Resend OTP</a></p>
    </div>

    <div class="security-badge">
        <span>🔒 256-bit SSL Encrypted • PCI-DSS Compliant (Sandbox)</span>
    </div>
</div>

<script>
    let generatedOTP = "";

    // 自動格式化卡號 (每4碼加一個空格)
    function formatCardNumber(input) {
        let value = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = '';
        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) formattedValue += ' ';
            formattedValue += value[i];
        }
        input.value = formattedValue;
    }

    // 自動格式化有效期限 (MM/YY)
    function formatExpiry(input) {
        let value = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        input.value = value;
    }

    // 觸發 OTP 階段
    function triggerOTP(e) {
        e.preventDefault(); // 阻止表單重整
        
        // 隱藏卡片輸入，顯示 OTP 畫面
        document.getElementById('step-1-card').style.display = 'none';
        document.getElementById('step-2-otp').style.display = 'block';
        
        // 隨機生成 6 位數 OTP
        generatedOTP = Math.floor(100000 + Math.random() * 900000).toString();
        
        // 模擬銀行發送 SMS (延遲 1 秒後彈出)
        setTimeout(() => {
            alert("📩 【Bank Sandbox SMS】\nYour OTP for RM <?php echo $amount; ?> is: " + generatedOTP + "\nDo not share this code with anyone.");
        }, 1000);
    }

    // 驗證 OTP
    function verifyOTP() {
        const userInput = document.getElementById('otp_input').value;
        const btn = document.getElementById('confirmBtn');

        if (userInput === generatedOTP) {
            btn.innerHTML = '🔄 Processing...';
            btn.style.backgroundColor = '#6c757d';
            btn.disabled = true;
            
            // OTP 正確，觸發真正的表單送出給 PHP 後端
            document.getElementById('finalPaymentForm').submit();
        } else {
            alert('❌ OTP 驗證失敗 (Invalid OTP Code)！請重新輸入。');
            document.getElementById('otp_input').value = '';
            document.getElementById('otp_input').focus();
        }
    }
</script>

</body>
</html>