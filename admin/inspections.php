<?php
// This section prepares the admin inspections page.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../core/repositories/MetricsRepository.php';
require_once __DIR__ . '/../core/components/FioriTileBuilder.php';
use Core\Repositories\MetricsRepository;
use Core\Components\FioriTileBuilder as TileBuilder;
$metricsRepo = new MetricsRepository($conn);
$kpi = $metricsRepo->getInspectionKPIs();
$page_title = "Inspection Dashboard";
$page_description = "Select a module below to execute post-usage assessments and track inspection history.";
$topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Operations / Inspections Dashboard</h2>';
$extra_css = [];
ob_start();
echo TileBuilder::renderSection('Venue Inspections', 'Execute physical assessments for recently utilized venues and review historical logs.', [
    [
        'url' => 'pending_inspections.php',
        'title' => 'Pending Inspections',
        'icon' => 'clipboard-list',
        'desc' => 'Execute physical assessments for recently utilized venues.',
        'kpi' => $kpi['pending_inspections'],
        'action' => 'Process Queue'
    ],
    [
        'url' => 'track_inspections.php',
        'title' => 'Track Inspections',
        'icon' => 'history',
        'desc' => 'Review historical assessment logs and penalty records.',
        'kpi' => $kpi['tracked_inspections'],
        'action' => 'View History'
    ]
]);
$page_content = ob_get_clean();
require_once __DIR__ . '/../core/layout.php';
?>
