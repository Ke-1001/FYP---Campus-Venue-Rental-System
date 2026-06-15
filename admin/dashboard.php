<?php
// File: admin/dashboard.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/booking_functions.php';
require_once __DIR__ . '/../core/repositories/MetricsRepository.php';
require_once __DIR__ . '/../core/components/FioriTileBuilder.php';

use Core\Repositories\MetricsRepository;
use Core\Components\FioriTileBuilder as TileBuilder;

// ∴ 實例化全域分析倉儲
$metricsRepo = new MetricsRepository($conn);
$kpi = $metricsRepo->getDashboardKPIs();
syncCompletedBookings($conn);

$page_title = "System Dashboard";
$page_description = "Centralized overview of all system modules and operational metrics.";
$extra_css = [];
$topbar_content = '
<div class="flex items-center text-slate-500 bg-white px-4 py-2 rounded-lg border border-slate-200 focus-within:border-[#004aad] shadow-sm transition-all">
    <i data-lucide="search" class="w-4 h-4 mr-2"></i>
    <input type="text" placeholder="Search system assets or modules..." class="bg-transparent border-none outline-none w-64 text-sm focus:ring-0">
</div>';

ob_start();

// 模組 1: Booking Management
echo TileBuilder::renderSection('Booking Operations', 'Manage venue reservations, staff assignments, and financial tracking.', [
    [
        'url' => 'pending_requests.php', 'title' => 'Approval Queue', 'icon' => 'shield-alert',
        'desc' => 'Review and approve paid venue booking requests.', 'kpi' => $kpi['pending_requests'], 'action' => 'View Requests'
    ],
    [
        'url' => 'assign_inspector.php', 'title' => 'Assign Inspector', 'icon' => 'users',
        'desc' => 'Assign staff to inspect venues after utilization.', 'kpi' => $kpi['pending_assignments'], 'action' => 'Assign Staff'
    ],
    [
        'url' => 'track_bookings.php', 'title' => 'Track Bookings', 'icon' => 'activity',
        'desc' => 'Monitor ongoing bookings and view historical records.', 'kpi' => ($kpi['ongoing_bookings'] + $kpi['completed_bookings']), 'action' => 'View Log'
    ],
    [
        'url' => 'damage_reports.php', 'title' => 'Damage Reports', 'icon' => 'triangle-alert',
        'desc' => 'Review pre-usage damage declarations by users.', 'kpi' => $kpi['damage_reports'], 'action' => 'Verify Reports'
    ]
]);

// 模組 2: Venue Inspections
echo TileBuilder::renderSection('Post-Usage Inspections', 'Execute physical assessments and finalize deposit logistics.', [
    [
        'url' => 'pending_inspections.php', 'title' => 'Pending Queue', 'icon' => 'clipboard-list',
        'desc' => 'Execute pending physical assessments for venues.', 'kpi' => $kpi['pending_inspections'], 'action' => 'Process Queue'
    ],
    [
        'url' => 'track_inspections.php', 'title' => 'Inspection History', 'icon' => 'history',
        'desc' => 'Review past assessment logs and applied penalties.', 'kpi' => $kpi['tracked_inspections'], 'action' => 'View Records'
    ]
]);

// 模組 3: Asset & Academic Configuration
echo TileBuilder::renderSection('Assets & Academic Matrix', 'Configure physical spaces and temporal academic bounds.', [
    [
        'url' => 'manage_vcategory.php', 'title' => 'Venue Categories', 'icon' => 'tags',
        'desc' => 'Define systemic categories for venue classification.', 'kpi' => $kpi['total_categories'], 'action' => 'Manage Categories'
    ],
    [
        'url' => 'venue_directory.php', 'title' => 'Venue Directory', 'icon' => 'database',
        'desc' => 'Manage existing venues, capacities, and states.', 'kpi' => $kpi['total_venues'], 'action' => 'View Venues'
    ],
    [
        'url' => 'semester_management.php', 'title' => 'Semester Bounds', 'icon' => 'calendar-clock',
        'desc' => 'Configure academic semester temporal limits.', 'kpi' => $kpi['active_semesters'], 'action' => 'Manage Config'
    ],
    [
        'url' => 'academic_schedule.php', 'title' => 'Class Schedule', 'icon' => 'book-open',
        'desc' => 'Manage weekly class routines and venue blocks.', 'kpi' => $kpi['total_schedules'], 'action' => 'Arrange Schedule'
    ]
]);

// 模組 4: Identity & Personnel (Super Admin only)
if (($_SESSION['role'] ?? '') === 'super_admin') {
    echo TileBuilder::renderSection('Identity Directory', 'Manage system access for staff, administrators, and students.', [
        [
            'url' => 'staff_directory.php', 'title' => 'Staff Directory', 'icon' => 'shield',
            'desc' => 'Manage operational staff and system administrators.', 'kpi' => $kpi['combined_personnel'], 'action' => 'View Personnel'
        ],
        [
            'url' => 'manage_students.php', 'title' => 'Student Entities', 'icon' => 'graduation-cap',
            'desc' => 'Monitor registered student accounts and contacts.', 'kpi' => $kpi['total_students'], 'action' => 'View Students'
        ]
    ]);
}

$page_content = ob_get_clean();
require_once __DIR__ . '/../core/layout.php';
?>