<?php
// File path: mock_payment.php
session_start();

// 💡 1. 嚴格驗證來自 process_booking.php 的 URL 參數
if (!isset($_GET['bid']) || !isset($_GET['amount'])) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:50px; color:#ef4444;'><h2>Violation: Invalid Payment Gateway Access.</h2></div>");
}

// 💡 2. 嚴格轉型
$bid = intval($_GET['bid']);
$amount = number_format((float)$_GET['amount'], 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sandbox Payment Gateway</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-50 h-screen flex items-center justify-center font-sans antialiased">
    <div class="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full border border-slate-200">
        
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="credit-card" class="w-8 h-8 text-indigo-600"></i>
            </div>
            <h2 class="text-2xl font-extrabold text-slate-800">Secure Checkout</h2>
            <p class="text-sm text-slate-500 mt-1">Complete your deposit to secure the venue.</p>
        </div>

        <div class="bg-slate-50 p-5 rounded-xl border border-slate-100 mb-8">
            <div class="flex justify-between items-center mb-3">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Transaction Ref</span>
                <span class="font-mono font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded"><?php echo $bid; ?></span>
            </div>
            <div class="flex justify-between items-center border-t border-slate-200 pt-3">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Amount Due</span>
                <span class="font-mono font-black text-emerald-600 text-2xl">RM <?php echo $amount; ?></span>
            </div>
        </div>

        <form action="actions/process_mock_payment.php" method="POST" id="paymentForm">
            <input type="hidden" name="bid" value="<?php echo $bid; ?>">
            <input type="hidden" name="amount" value="<?php echo htmlspecialchars($_GET['amount']); ?>">
            
            <button type="submit" id="payBtn" class="w-full py-4 bg-indigo-600 text-white text-sm font-bold rounded-xl shadow-md hover:bg-indigo-700 hover:shadow-lg transition-all flex items-center justify-center transform active:scale-95">
                <i data-lucide="lock" class="w-4 h-4 mr-2"></i> Pay RM <?php echo $amount; ?>
            </button>
        </form>
        
        <div class="mt-6 text-center flex items-center justify-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">
            <i data-lucide="shield-check" class="w-4 h-4 mr-1 text-emerald-500"></i> Sandbox Environment
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        // UI 狀態防護：防止重複點擊提交
        document.getElementById('paymentForm').addEventListener('submit', function() {
            const btn = document.getElementById('payBtn');
            btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 mr-2 animate-spin"></i> Processing Transaction...';
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            btn.classList.remove('hover:bg-indigo-700', 'active:scale-95');
        });
    </script>
</body>
</html>