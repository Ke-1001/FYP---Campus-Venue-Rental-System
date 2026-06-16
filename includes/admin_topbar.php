<?php
// This section provides shared admin topbar logic or layout.
?>
<header class="w-full h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0">

    <div class="flex items-center">
        <button onclick="toggleSidebar()" class="p-1.5 mr-4 text-slate-500 hover:text-[#004aad] transition-colors rounded-md hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-[#004aad]/20" title="Toggle Menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>

        <div class="flex items-center">
            <?php echo isset($topbar_content) ? $topbar_content : ''; ?>
        </div>
    </div>

    <div class="flex items-center space-x-3">

        <button class="relative p-2 text-slate-500 hover:text-[#004aad] transition-colors rounded-md hover:bg-slate-100 focus:outline-none" title="Notifications">
            <i data-lucide="bell" class="w-4 h-4"></i>
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-[#dc2626] rounded-full border-2 border-white"></span>
        </button>

        <a href="profile.php" class="p-2 text-slate-500 hover:text-[#004aad] rounded-md hover:bg-slate-100 transition-colors focus:outline-none" title="Profile">
            <i data-lucide="user-cog" class="w-4 h-4"></i>
        </a>

    </div>

</header>
