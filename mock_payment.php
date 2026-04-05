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
        .payment-container { background: #fff; padding: 0; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.1); width: 100%; max-width: 480px; border-top: 5px solid #004085; overflow: hidden; }
        
        /* 標題區塊 */
        .header-section { padding: 30px 30px 15px 30px; text-align: center; border-bottom: 1px solid #eee; background-color: #f8f9fa; }
        .header-section h2 { margin: 0; color: #004085; font-size: 22px; }
        .header-section p { color: #6c757d; font-size: 13px; margin-top: 5px; }
        .amount-display { font-size: 32px; font-weight: bold; color: #28a745; margin: 15px 0 5px 0; }

        /* 動態標籤列 (Tabs) */
        .tabs { display: flex; background-color: #f1f3f5; border-bottom: 2px solid #ddd; }
        .tab-btn { flex: 1; padding: 15px; border: none; background: none; font-size: 15px; font-weight: bold; color: #6c757d; cursor: pointer; transition: 0.3s; }
        .tab-btn:hover { background-color: #e9ecef; }
        .tab-btn.active { color: #004085; border-bottom: 3px solid #004085; background-color: #fff; }

        /* 內容區塊 */
        .content-section { padding: 25px 30px 30px 30px; }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.4s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        /* 表單元素 */
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; color: #495057; font-weight: bold; font-size: 13px; }
        input, select { width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 6px; box-sizing: border-box; font-size: 15px; }
        input[type="text"], input[type="password"] { font-family: monospace; }
        input:focus, select:focus { border-color: #80bdff; outline: 0; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }
        
        .row { display: flex; gap: 15px; }
        .row .form-group { flex: 1; }
        
        .btn-pay { width: 100%; padding: 15px; background-color: #004085; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 5px; }
        .btn-pay:hover { background-color: #002752; }

        /* OTP 專屬樣式 */
        #step-2-otp { display: none; text-align: center; }
        .otp-inputs { display: flex; justify-content: center; margin: 20px 0; }
        .otp-inputs input { width: 160px; height: 50px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 12px; border: 2px solid #ced4da; border-radius: 8px; }
        
        .security-badge { text-align: center; padding: 15px; font-size: 11px; color: #adb5bd; background-color: #f8f9fa; border-top: 1px solid #eee; }
    </style>
</head>
<body>

<div class="payment-container">
    
    <div class="header-section">
        <h2>🔒 Secure Checkout</h2>
        <p>Order Ref: #<?php echo htmlspecialchars($booking_id); ?> (<?php echo htmlspecialchars($payment_type); ?>)</p>
        <div class="amount-display">RM <?php echo number_format($amount, 2); ?></div>
    </div>

    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('card')" id="btn-card">Credit / Debit Card</button>
        <button class="tab-btn" onclick="switchTab('fpx')" id="btn-fpx">FPX (Online Banking)</button>
    </div>

    <div class="content-section">
        
        <div id="content-card" class="tab-content active">
            <div id="step-1-card">
                <form id="cardForm" onsubmit="triggerOTP(event)">
                    <div class="form-group">
                        <label>Cardholder Name</label>
                        <input type="text" placeholder="e.g. SITI NURHALIZA" required autocomplete="off" style="font-family: 'Segoe UI', sans-serif;">
                    </div>
                    <div class="form-group">
                        <label>Card Number</label>
                        <input type="text" placeholder="0000 0000 0000 0000" maxlength="19" required autocomplete="off" oninput="formatCardNumber(this)">
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
                    <button type="submit" class="btn-pay">Proceed to Verification</button>
                </form>
            </div>

            <div id="step-2-otp">
                <h3 style="color: #333; margin-top: 0;">📱 Bank Authentication</h3>
                <p style="font-size: 13px; color: #6c757d; line-height: 1.5;">
                    A 6-digit verification code has been sent via SMS to <strong>(+60 12-***-**89)</strong>.
                </p>
                <form action="actions/process_mock_payment.php" method="POST" id="finalCardForm">
                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_id); ?>">
                    <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
                    <input type="hidden" name="payment_type" value="<?php echo htmlspecialchars($payment_type); ?>">
                    <input type="hidden" name="bank" value="Credit Card"> <div class="otp-inputs">
                        <input type="text" id="otp_input" maxlength="6" required autocomplete="off">
                    </div>
                    <button type="button" class="btn-pay" onclick="verifyOTP()" id="confirmBtn" style="background-color: #28a745;">Confirm & Pay</button>
                </form>
            </div>
        </div>

        <div id="content-fpx" class="tab-content">
            <form action="actions/process_mock_payment.php" method="POST" id="fpxForm">
                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_id); ?>">
                <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
                <input type="hidden" name="payment_type" value="<?php echo htmlspecialchars($payment_type); ?>">

                <div class="form-group" style="margin-bottom: 25px;">
                    <label>Select Your Bank</label>
                    <select name="bank" required>
                        <option value="">-- Choose Bank (FPX Sandbox) --</option>
                        <option value="Maybank2U">Maybank2U</option>
                        <option value="CIMB Clicks">CIMB Clicks</option>
                        <option value="Public Bank">Public Bank</option>
                        <option value="RHB Now">RHB Now</option>
                        <option value="Bank Islam">Bank Islam</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-pay" id="fpxPayBtn">Login to Bank (RM <?php echo number_format($amount, 2); ?>)</button>
            </form>
        </div>

    </div>

    <div class="security-badge">
        🔒 256-bit SSL Encrypted • PCI-DSS Compliant Sandbox
    </div>
</div>

<script>
    // ================= 狀態機：標籤切換邏輯 =================
    function switchTab(tabId) {
        // 重置所有標籤按鈕的狀態
        document.getElementById('btn-card').classList.remove('active');
        document.getElementById('btn-fpx').classList.remove('active');
        
        // 重置所有內容區塊
        document.getElementById('content-card').classList.remove('active');
        document.getElementById('content-fpx').classList.remove('active');
        
        // 啟用目標標籤與內容
        document.getElementById('btn-' + tabId).classList.add('active');
        document.getElementById('content-' + tabId).classList.add('active');

        // 如果切換回 Card，將畫面重置為輸入卡號階段 (隱藏 OTP)
        if(tabId === 'card') {
            document.getElementById('step-1-card').style.display = 'block';
            document.getElementById('step-2-otp').style.display = 'none';
            document.getElementById('otp_input').value = '';
        }
    }

    // ================= 信用卡格式化邏輯 =================
    function formatCardNumber(input) {
        let value = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = '';
        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) formattedValue += ' ';
            formattedValue += value[i];
        }
        input.value = formattedValue;
    }

    function formatExpiry(input) {
        let value = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        if (value.length >= 2) value = value.substring(0, 2) + '/' + value.substring(2, 4);
        input.value = value;
    }

    // ================= OTP 驗證邏輯 =================
    let generatedOTP = "";

    function triggerOTP(e) {
        e.preventDefault(); 
        document.getElementById('step-1-card').style.display = 'none';
        document.getElementById('step-2-otp').style.display = 'block';
        
        generatedOTP = Math.floor(100000 + Math.random() * 900000).toString();
        
        setTimeout(() => {
            alert("📩 【Bank Sandbox SMS】\nYour OTP for RM <?php echo $amount; ?> is: " + generatedOTP);
        }, 800);
    }

    function verifyOTP() {
        const userInput = document.getElementById('otp_input').value;
        const btn = document.getElementById('confirmBtn');

        if (userInput === generatedOTP) {
            btn.innerHTML = '🔄 Processing...';
            btn.style.backgroundColor = '#6c757d';
            btn.disabled = true;
            document.getElementById('finalCardForm').submit();
        } else {
            alert('❌ OTP 驗證失敗 (Invalid OTP Code)！');
            document.getElementById('otp_input').value = '';
        }
    }

    // ================= FPX 提交防呆邏輯 =================
    document.getElementById('fpxForm').addEventListener('submit', function() {
        const btn = document.getElementById('fpxPayBtn');
        btn.innerHTML = '🔄 Redirecting...';
        btn.style.backgroundColor = '#6c757d';
        btn.style.pointerEvents = 'none';
    });
</script>

</body>
</html>