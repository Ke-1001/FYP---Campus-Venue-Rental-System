<?php
// File: includes/ui_components.php
// 此檔案應被 include 在每個頁面的 </body> 標籤正上方
?>

<?php if (isset($_SESSION['toast'])): ?>
    <?php 
        $toast = $_SESSION['toast'];
        $bg_color = ($toast['type'] === 'success') ? 'bg-emerald-500' : 'bg-red-500';
        $icon = ($toast['type'] === 'success') ? 'check-circle' : 'alert-circle';
    ?>
    <div id="system-toast" class="fixed top-5 right-5 z-50 flex items-center p-4 mb-4 text-white rounded-lg shadow-xl <?php echo $bg_color; ?> transform transition-all duration-500 translate-x-0 opacity-100">
        <i data-lucide="<?php echo $icon; ?>" class="w-5 h-5 mr-3"></i>
        <div class="text-sm font-bold tracking-wide"><?php echo htmlspecialchars($toast['msg']); ?></div>
        <button onclick="closeToast()" class="ml-auto -mx-1.5 -my-1.5 text-white hover:text-slate-200 rounded-lg focus:ring-2 p-1.5 inline-flex h-8 w-8 transition">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
    
    <script>
        // 3.5秒後自動衰減隱藏 (Information Entropy Decay)
        setTimeout(() => { closeToast(); }, 3500);
        function closeToast() {
            const toast = document.getElementById('system-toast');
            if(toast) {
                toast.classList.replace('translate-x-0', 'translate-x-full');
                toast.classList.replace('opacity-100', 'opacity-0');
                setTimeout(() => toast.remove(), 500); // 等待動畫結束後移除 DOM
            }
        }
    </script>
    <?php unset($_SESSION['toast']); // 顯示後立刻銷毀 Session 變數 ?>
<?php endif; ?>


<div id="custom-confirm-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center transition-opacity">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform scale-100 transition-transform">
        
        <div class="p-6 text-center">
            <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="alert-triangle" class="w-8 h-8"></i>
            </div>
            <h3 class="text-xl font-extrabold text-slate-800 mb-2">Are you absolutely sure?</h3>
            <p id="confirm-modal-msg" class="text-sm text-slate-500 font-medium px-4">
                </p>
        </div>
        
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex space-x-3 justify-center">
            <button onclick="closeCustomConfirm()" class="flex-1 px-4 py-2.5 text-sm font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-100 rounded-lg transition shadow-sm">
                Cancel
            </button>
            <a href="#" id="confirm-modal-btn" class="flex-1 px-4 py-2.5 text-sm font-bold text-white bg-mmu-blue hover:bg-blue-700 rounded-lg transition shadow text-center flex items-center justify-center">
                Confirm Execution
            </a>
        </div>
    </div>
</div>

<script>
    // 攔截原本的跳轉，喚起客製化 Modal
    function triggerCustomConfirm(event, message, targetUrl) {
        event.preventDefault(); // 阻擋 <a> 標籤的預設跳轉
        document.getElementById('confirm-modal-msg').innerText = message;
        document.getElementById('confirm-modal-btn').href = targetUrl;
        document.getElementById('custom-confirm-modal').classList.remove('hidden');
    }

    function closeCustomConfirm() {
        document.getElementById('custom-confirm-modal').classList.add('hidden');
    }
</script>