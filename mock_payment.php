<?php
// File: mock_payment.php
session_start();

$booking_id = $_GET['booking_id'] ?? 'BKG-0000';
$amount = $_GET['amount'] ?? '0.00';
$payment_type = $_GET['type'] ?? 'Deposit';

if ($amount <= 0) {
    die("Invalid transaction vector.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment Gateway | Sandbox</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen p-6 font-sans">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden border border-slate-200">
        
        <div class="bg-slate-900 p-6 text-center border-b-4 border-emerald-500">
            <h2 class="text-xl font-bold text-white tracking-wide">Secure Checkout</h2>
            <p class="text-slate-400 text-xs font-mono mt-1">REF: <?php echo htmlspecialchars($booking_id); ?></p>
            <div class="text-4xl font-extrabold text-emerald-400 mt-4 tracking-tighter">RM <?php echo number_format($amount, 2); ?></div>
        </div>

        <div class="p-6">
            <div id="step-1-card" class="block">
                <form id="cardForm" onsubmit="triggerOTP(event)" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Cardholder Name</label>
                        <input type="text" placeholder="e.g. SITI NURHALIZA" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none uppercase font-mono text-sm bg-slate-50">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Card Number</label>
                        <input type="text" placeholder="0000 0000 0000 0000" maxlength="19" required oninput="formatCardNumber(this)" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none font-mono tracking-widest text-sm bg-slate-50">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Expiry Date</label>
                            <input type="text" placeholder="MM/YY" maxlength="5" required oninput="formatExpiry(this)" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none font-mono text-center text-sm bg-slate-50">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">CVV</label>
                            <input type="password" placeholder="•••" maxlength="3" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none font-mono text-center text-sm bg-slate-50">
                        </div>
                    </div>
                    <button type="submit" class="w-full mt-4 py-3.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-lg transition shadow-md">
                        Proceed to Verification
                    </button>
                </form>
            </div>

            <div id="step-2-otp" class="hidden text-center py-4">
                <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="smartphone" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800">Bank Authentication</h3>
                <p class="text-xs text-slate-500 mt-2 px-4">Enter the 6-digit OTP sent to your registered mobile number ending in **89.</p>
                
                <form action="actions/process_mock_payment.php" method="POST" id="finalCardForm" class="mt-6">
                    <input type="hidden" name="booking_ref" value="<?php echo htmlspecialchars($booking_id); ?>">
                    <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
                    
                    <input type="text" id="otp_input" maxlength="6" required autocomplete="off" class="w-48 mx-auto text-center font-mono text-2xl tracking-[0.5em] px-4 py-3 border-2 border-slate-300 rounded-lg focus:border-emerald-500 focus:outline-none bg-slate-50">
                    
                    <button type="button" onclick="verifyOTP()" id="confirmBtn" class="w-full mt-6 py-3.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-lg transition shadow-md flex justify-center items-center">
                        <i data-lucide="shield-check" class="w-4 h-4 mr-2"></i> Confirm Transaction
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-slate-50 p-4 border-t border-slate-200 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest flex justify-center items-center">
            <i data-lucide="lock" class="w-3 h-3 mr-1"></i> 256-bit SSL Encrypted Sandbox
        </div>
    </div>

    <script>
        lucide.createIcons();

        function formatCardNumber(input) {
            let value = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formatted = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) formatted += ' ';
                formatted += value[i];
            }
            input.value = formatted;
        }

        function formatExpiry(input) {
            let value = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            if (value.length >= 2) value = value.substring(0, 2) + '/' + value.substring(2, 4);
            input.value = value;
        }

        let generatedOTP = "";

        function triggerOTP(e) {
            e.preventDefault(); 
            document.getElementById('step-1-card').classList.replace('block', 'hidden');
            document.getElementById('step-2-otp').classList.replace('hidden', 'block');
            
            generatedOTP = Math.floor(100000 + Math.random() * 900000).toString();
            setTimeout(() => {
                alert("[SANDBOX SMS]\nYour OTP code is: " + generatedOTP);
            }, 800);
        }

        function verifyOTP() {
            const userInput = document.getElementById('otp_input').value;
            const btn = document.getElementById('confirmBtn');

            if (userInput === generatedOTP) {
                btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 mr-2 animate-spin"></i> Processing...';
                btn.classList.replace('bg-emerald-500', 'bg-slate-400');
                btn.disabled = true;
                lucide.createIcons();
                document.getElementById('finalCardForm').submit();
            } else {
                alert('Authentication Failed: Invalid OTP Code.');
                document.getElementById('otp_input').value = '';
            }
        }
    </script>
</body>
</html>