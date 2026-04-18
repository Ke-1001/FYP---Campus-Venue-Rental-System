<?php
// 路徑：includes/admin_sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside id="system-sidebar" class="mmu-sidebar bg-mmu-dark text-white flex flex-col shadow-2xl z-20 shrink-0">
    
    <div class="brand-header h-16 flex items-center px-6 border-b border-slate-700 shrink-0 transition-all">
        <i data-lucide="building-2" class="w-6 h-6 text-mmu-accent shrink-0"></i>
        <span class="ml-3 text-lg font-bold tracking-wider brand-text">MMU Admin</span>
    </div>
    
    <nav class="flex-1 overflow-y-auto py-4">
        <ul class="space-y-1 px-3">
            <li>
                <a href="dashboard.php" class="nav-item flex items-center px-4 py-3 <?php echo ($current_page == 'dashboard.php') ? 'bg-mmu-blue' : 'text-slate-300 hover:bg-slate-800'; ?> rounded-lg transition-colors">
                    <i data-lucide="layout-dashboard" class="w-5 h-5 shrink-0"></i>
                    <span class="ml-3 font-medium nav-text">System Dashboard</span>
                </a>
            </li>
            <li>
                <a href="manage_bookings.php" class="nav-item flex items-center px-4 py-3 <?php echo ($current_page == 'manage_bookings.php') ? 'bg-mmu-blue' : 'text-slate-300 hover:bg-slate-800'; ?> rounded-lg transition-colors">
                    <i data-lucide="calendar-check" class="w-5 h-5 shrink-0"></i>
                    <span class="ml-3 font-medium nav-text">Manage Bookings</span>
                    <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full nav-badge">12</span>
                </a>
            </li>
            <li>
                <a href="manage_venues.php" class="nav-item flex items-center px-4 py-3 <?php echo ($current_page == 'manage_venues.php') ? 'bg-mmu-blue' : 'text-slate-300 hover:bg-slate-800'; ?> rounded-lg transition-colors">
                    <i data-lucide="map-pin" class="w-5 h-5 shrink-0"></i>
                    <span class="ml-3 font-medium nav-text">Venue Registry</span>
                </a>
            </li>

            <li>
                <a href="report.php" class="nav-item flex items-center px-4 py-3 <?php echo ($current_page == 'report.php') ? 'bg-mmu-blue' : 'text-slate-300 hover:bg-slate-800'; ?> rounded-lg transition-colors">
                    <i data-lucide="line-chart" class="w-5 h-5 shrink-0"></i>
                    <span class="ml-3 font-medium nav-text">Statistical Reports</span>
                </a>
            </li>
            
            <li class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider nav-header">Identity Management</li>
            <li>
                <a href="manage_admins.php" class="nav-item flex items-center px-4 py-2 <?php echo ($current_page == 'manage_admins.php') ? 'bg-mmu-blue' : 'text-slate-300 hover:bg-slate-800'; ?> rounded-lg transition-colors">
                    <i data-lucide="shield" class="w-4 h-4 shrink-0 text-mmu-accent"></i>
                    <span class="ml-3 font-medium text-sm nav-text">Administrators</span>
                </a>
            </li>
            <li>
                <a href="manage_students.php" class="nav-item flex items-center px-4 py-2 <?php echo ($current_page == 'manage_students.php') ? 'bg-mmu-blue' : 'text-slate-300 hover:bg-slate-800'; ?> rounded-lg transition-colors">
                    <i data-lucide="graduation-cap" class="w-4 h-4 shrink-0"></i>
                    <span class="ml-3 font-medium text-sm nav-text">Student Directory</span>
                </a>
            </li>
            
        </ul>
    </nav>

    <div class="profile-container flex items-center p-4 border-t border-slate-700 bg-slate-800/50 shrink-0">
        <div class="w-10 h-10 rounded-full bg-mmu-blue flex items-center justify-center text-sm font-bold shrink-0">SA</div>
        <div class="ml-3 profile-text overflow-hidden flex-1">
            <p class="text-sm font-bold text-white truncate">Super Admin</p>
            <p class="text-xs text-slate-400 font-mono truncate">ID: EMP-0001</p>
        </div>
    </div>
</aside>