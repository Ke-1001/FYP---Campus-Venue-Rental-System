<?php
// File path: includes/admin_sidebar.php

$current_page = basename($_SERVER['PHP_SELF']);

// 💡 1. 全域狀態機同步 (Global Sweep Logic)
if (isset($conn)) {
    $sweep_sql = "
        UPDATE booking 
        SET status = 'completed' 
        WHERE status = 'approved' 
        AND CONCAT(date_booked, ' ', time_end) <= NOW()
    ";
    $conn->query($sweep_sql);
}

// 💡 2. 動態 Badge 計算 (精確化過濾)
$pending_bookings_count = 0;
$pending_inspections_count = 0;

if (isset($conn)) {
    // Metric A
    $sql_bookings_count = "SELECT COUNT(*) FROM booking WHERE status = 'pending' AND payment_status = 'paid'";
    $res_bookings = $conn->query($sql_bookings_count);
    if ($res_bookings) {
        $pending_bookings_count = $res_bookings->fetch_row()[0];
    }

    // Metric B
    $sql_inspections_count = "
        SELECT COUNT(*) 
        FROM inspection i 
        JOIN booking b ON i.bid = b.bid 
        WHERE i.ins_status = 'pending' AND b.status = 'completed'
    ";
    $res_inspections = $conn->query($sql_inspections_count);
    if ($res_inspections) {
        $pending_inspections_count = $res_inspections->fetch_row()[0];
    }
}
?>
<style>
    /* Scrollbar Suppression Logic */
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<aside id="system-sidebar" class="mmu-sidebar bg-[#1e293b] text-white flex flex-col shadow-xl z-20 shrink-0 border-r border-slate-800">
    
    <div class="brand-header h-16 flex items-center px-5 border-b border-slate-700/50 shrink-0 transition-all bg-[#0f172a]/30">
        <span class="text-sm font-black tracking-widest text-slate-100 uppercase brand-text">CVBMS Admin</span>
    </div>
    
    <nav class="flex-1 overflow-y-auto py-4 scrollbar-hide">
        <ul class="space-y-1 px-3">

            <li>
                <a href="dashboard.php" class="nav-item flex items-center px-3 py-2.5 text-sm font-semibold <?php echo ($current_page == 'dashboard.php') ? 'bg-cstyle-blue text-white shadow-sm' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'; ?> rounded-md transition-all">
                    <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i>
                    <span class="ml-3 nav-text">System Dashboard</span>
                </a>
            </li>
            
            <li>
                <a href="manage_bookings.php" class="nav-item flex items-center px-3 py-2.5 text-sm font-semibold <?php echo (in_array($current_page, ['manage_bookings.php', 'pending_requests.php', 'assign_inspector.php', 'assign_inspector_detail.php', 'track_bookings.php', 'process_flow.php'])) ? 'bg-cstyle-blue text-white shadow-sm' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'; ?> rounded-md transition-all">
                    <i data-lucide="calendar-check" class="w-4 h-4 shrink-0"></i>
                    <span class="ml-3 nav-text">Manage Bookings</span>
                    <?php if ($pending_bookings_count > 0): ?>
                        <span class="ml-auto bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm nav-badge"><?php echo $pending_bookings_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li>
                <a href="inspections.php" class="nav-item flex items-center px-3 py-2.5 text-sm font-semibold <?php echo (in_array($current_page, ['inspections.php', 'pending_inspections.php', 'execute_inspection.php', 'track_inspections.php'])) ? 'bg-cstyle-blue text-white shadow-sm' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'; ?> rounded-md transition-all">
                    <i data-lucide="clipboard-check" class="w-4 h-4 shrink-0"></i>
                    <span class="ml-3 nav-text">Inspections</span>
                    <?php if ($pending_inspections_count > 0): ?>
                        <span class="ml-auto bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm nav-badge"><?php echo $pending_inspections_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li>
                <a href="manage_venues.php" class="nav-item flex items-center px-3 py-2.5 text-sm font-semibold <?php echo (in_array($current_page, ['manage_venues.php', 'register_venue.php', 'edit_venue.php', 'venue_directory.php'])) ? 'bg-cstyle-blue text-white shadow-sm' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'; ?> rounded-md transition-all">
                    <i data-lucide="map-pin" class="w-4 h-4 shrink-0"></i>
                    <span class="ml-3 nav-text">Venue Registry</span>
                </a>
            </li>
            
            <li class="pt-5 pb-1.5 px-3 text-[11px] font-bold text-slate-500 uppercase tracking-widest nav-header">Identity Matrix</li>
            
            <li>
                <a href="manage_admins.php" class="nav-item flex items-center px-3 py-2.5 text-sm font-semibold <?php echo (in_array($current_page, ['manage_admins.php', 'add_admin.php', 'admin_directory.php', 'add_staff.php', 'staff_directory.php'])) ? 'bg-cstyle-blue text-white shadow-sm' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'; ?> rounded-md transition-all">
                    <i data-lucide="shield" class="w-4 h-4 shrink-0"></i>
                    <span class="ml-3 nav-text">Personnel Directory</span>
                </a>
            </li>
            
            <li>
                <a href="manage_students.php" class="nav-item flex items-center px-3 py-2.5 text-sm font-semibold <?php echo (in_array($current_page, ['manage_students.php', 'add_student.php'])) ? 'bg-cstyle-blue text-white shadow-sm' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'; ?> rounded-md transition-all">
                    <i data-lucide="users" class="w-4 h-4 shrink-0"></i>
                    <span class="ml-3 nav-text">Student Directory</span>
                </a>
            </li>
            
            <li class="pt-5 pb-1.5 px-3 text-[11px] font-bold text-slate-500 uppercase tracking-widest nav-header">Analytics & Operations</li>
                
            <li>
                <a href="report.php" class="nav-item flex items-center px-3 py-2.5 text-sm font-semibold <?php echo ($current_page == 'report.php') ? 'bg-cstyle-blue text-white shadow-sm' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'; ?> rounded-md transition-all">
                    <i data-lucide="line-chart" class="w-4 h-4 shrink-0"></i>
                    <span class="ml-3 nav-text">Statistical Reports</span>
                </a>
            </li>

            <li>
                <a href="academic.php" class="nav-item flex items-center px-3 py-2.5 text-sm font-semibold <?php echo (in_array($current_page, ['academic.php', 'semester_mamangement.php', 'academic_schedule.php'])) ? 'bg-cstyle-blue text-white shadow-sm' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'; ?> rounded-md transition-all">
                    <i data-lucide="book-open" class="w-4 h-4 shrink-0"></i>
                    <span class="ml-3 nav-text">Academic Schedule</span>
                </a>
            </li>

            <li class="mt-6 border-t border-slate-700/50 pt-2">
                <a href="../actions/logout.php" class="nav-item flex items-center px-3 py-2.5 text-sm font-semibold text-red-400 hover:bg-red-500/10 hover:text-red-300 rounded-md transition-all">
                    <i data-lucide="log-out" class="w-4 h-4 shrink-0"></i>
                    <span class="ml-3 nav-text">Log Out</span>
                </a>
            </li>

        </ul>
    </nav>

    <div class="profile-container flex items-center p-4 border-t border-slate-700/50 bg-[#0f172a]/30 shrink-0">
        <div class="w-9 h-9 rounded-md bg-[#004aad] border border-blue-400/30 flex items-center justify-center text-xs font-black text-white shrink-0 shadow-sm">
            <?php echo isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin' ? 'SA' : 'A'; ?>
        </div>
        <div class="ml-3 profile-text overflow-hidden flex-1">
            <p class="text-sm font-bold text-slate-200 truncate">
                <?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Administrator'; ?>
            </p>
            <p class="text-[10px] text-slate-500 font-mono tracking-widest uppercase truncate mt-0.5">
                Auth: <?php echo isset($_SESSION['role']) ? ($_SESSION['role'] === 'super_admin' ? 'Root' : 'Standard') : 'Unknown'; ?>
            </p>
        </div>
    </div>
</aside>