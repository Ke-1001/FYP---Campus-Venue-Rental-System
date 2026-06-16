<?php
// This section prepares the admin academic page.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../core/repositories/MetricsRepository.php';
require_once __DIR__ . '/../core/components/FioriTileBuilder.php';

use Core\Repositories\MetricsRepository;
use Core\Components\FioriTileBuilder as TileBuilder;

$metricsRepo = new MetricsRepository($conn);
$kpi = $metricsRepo->getAcademicKPIs();

$page_title = "Academic Arrangement";
$page_description = "Select a module below to manage semester bounds and schedule logic.";
$topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Academic / Dashboard</h2>';
$extra_css = [];

ob_start();

echo TileBuilder::renderSection('Academic Configuration', 'Define temporal constraints and block routine class schedules.', [
    [
        'url' => 'semester_management.php', 'title' => 'Semester Matrix', 'icon' => 'calendar-clock',
        'desc' => 'Configure academic semester start and end time boundaries.', 'kpi' => $kpi['active_semesters'] . ' <span class="text-sm font-normal text-slate-400">Active</span>', 'action' => 'Manage Configuration'
    ],
    [
        'url' => 'academic_schedule.php', 'title' => 'Class Schedule', 'icon' => 'book-open',
        'desc' => 'Manage weekly class schedules and block venue availability.', 'kpi' => $kpi['total_schedules'], 'action' => 'Arrange Schedule'
    ]
]);

$page_content = ob_get_clean();
require_once __DIR__ . '/../core/layout.php';
?>
