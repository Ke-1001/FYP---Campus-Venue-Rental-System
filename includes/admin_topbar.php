<?php
// File path: includes/admin_topbar.php
// 💡 依賴注入：$topbar_content 變數必須在父頁面中被定義
?>
<header class="w-full h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0">
    
    <div class="flex items-center">
        <button onclick="toggleSidebar()" class="p-2 mr-4 text-slate-500 hover:text-mmu-blue transition-colors rounded-lg hover:bg-slate-100 focus:outline-none">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>
        
        <?php echo isset($topbar_content) ? $topbar_content : ''; ?>
    </div>
    
    <div class="flex items-center space-x-4">
        
        <button class="relative p-2 text-slate-500 hover:text-mmu-blue transition-colors rounded-full hover:bg-slate-100">
            <i data-lucide="bell" class="w-5 h-5"></i>
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
        </button>
        
        <a href="profile.php" class="p-2 text-slate-500 hover:text-mmu-blue rounded-full hover:bg-slate-100 transition-colors" title="Entity Configuration">
            <i data-lucide="user-circle" class="w-5 h-5"></i>
        </a>
        
    </div>

</header>