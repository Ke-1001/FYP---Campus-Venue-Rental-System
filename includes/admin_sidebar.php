<?php
// File path: includes/admin_sidebar.php

$current_page = basename($_SERVER['PHP_SELF']);

// 1. Dynamic Notification Metrics Calculation
$pending_bookings_count = 0;
$pending_inspections_count = 0;

if (isset($conn)) {
    // 💡 Metric A: Pending Approval Node (Synchronized with new schema vectors)
    // Condition: status is 'pending' ∩ payment_status is 'paid'
    $sql_bookings_count = "SELECT COUNT(*) FROM booking WHERE status = 'pending' AND payment_status = 'paid'";
    $res_bookings = $conn->query($sql_bookings_count);
    if ($res_bookings) {
        $pending_bookings_count = $res_bookings->fetch_row()[0];
    }

    // 💡 Metric B: Pending Inspection Node (Replaced 'Returned' state)
    // Condition: status is 'completed' ∩ payment_status is 'paid' (Deposit held, awaiting inspection)
    $sql_inspections_count = "SELECT COUNT(*) FROM booking WHERE status = 'completed' AND payment_status = 'paid'";
    $res_inspections = $conn->query($sql_inspections_count);
    if ($res_inspections) {
        $pending_inspections_count = $res_inspections->fetch_row()[0];
    }
}
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
                    <?php if ($pending_bookings_count > 0): ?>
                        <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full nav-badge"><?php echo $pending_bookings_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li>
                <a href="inspections.php" class="nav-item flex items-center px-4 py-3 <?php echo ($current_page == 'inspections.php') ? 'bg-mmu-blue' : 'text-slate-300 hover:bg-slate-800'; ?> rounded-lg transition-colors">
                    <i data-lucide="clipboard-check" class="w-5 h-5 shrink-0"></i>
                    <span class="ml-3 font-medium nav-text">Inspections</span>
                    <?php if ($pending_inspections_count > 0): ?>
                        <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full nav-badge"><?php echo $pending_inspections_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li>
                <a href="manage_venues.php" class="nav-item flex items-center px-4 py-3 <?php echo ($current_page == 'manage_venues.php') ? 'bg-mmu-blue' : 'text-slate-300 hover:bg-slate-800'; ?> rounded-lg transition-colors">
                    <i data-lucide="map-pin" class="w-5 h-5 shrink-0"></i>
                    <span class="ml-3 font-medium nav-text">Venue Registry</span>
                </a>
            </li>
            
            <li class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider nav-header">Identity Management</li>
            
            <li>
                <a href="manage_admins.php" class="nav-item flex items-center px-4 py-2 <?php echo ($current_page == 'manage_admins.php' || $current_page == 'add_admin.php') ? 'bg-mmu-blue' : 'text-slate-300 hover:bg-slate-800'; ?> rounded-lg transition-colors">
                    <i data-lucide="shield" class="w-4 h-4 shrink-0 text-mmu-accent"></i>
                    <span class="ml-3 font-medium text-sm nav-text">Personnel Directory</span>
                </a>
            </li>
            
            <li>
                <a href="manage_students.php" class="nav-item flex items-center px-4 py-2 <?php echo ($current_page == 'manage_students.php' || $current_page == 'add_student.php') ? 'bg-mmu-blue' : 'text-slate-300 hover:bg-slate-800'; ?> rounded-lg transition-colors">
                    <i data-lucide="graduation-cap" class="w-4 h-4 shrink-0"></i>
                    <span class="ml-3 font-medium text-sm nav-text">Student Directory</span>
                </a>
            </li>
            
            <li class="pt-4 nav-header">
                <a href="report.php" class="nav-item flex items-center px-4 py-3 <?php echo ($current_page == 'report.php') ? 'bg-mmu-blue' : 'text-slate-300 hover:bg-slate-800'; ?> rounded-lg transition-colors">
                    <i data-lucide="line-chart" class="w-5 h-5 shrink-0"></i>
                    <span class="ml-3 font-medium nav-text">Statistical Reports</span>
                </a>
            </li>

            <li class="mt-4">
                <a href="../actions/logout.php" class="nav-item flex items-center px-4 py-3 text-red-400 hover:bg-red-500/10 hover:text-red-300 rounded-lg transition-colors">
                    <i data-lucide="log-out" class="w-5 h-5 shrink-0"></i>
                    <span class="ml-3 font-bold nav-text">Log Out</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="profile-container flex items-center p-4 border-t border-slate-700 bg-slate-800/50 shrink-0">
        <div class="w-10 h-10 rounded-full bg-mmu-blue flex items-center justify-center text-sm font-bold shrink-0">
            <?php echo isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin' ? 'SA' : 'A'; ?>
        </div>
        <div class="ml-3 profile-text overflow-hidden flex-1">
            <p class="text-sm font-bold text-white truncate">
                <?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Administrator'; ?>
            </p>
            <p class="text-xs text-slate-400 font-mono truncate">
                Level: <?php echo isset($_SESSION['role']) ? ($_SESSION['role'] === 'super_admin' ? 'Root' : 'Standard') : 'Unknown'; ?>
            </p>
        </div>
    </div>
</aside>