<?php
// File: admin/inspections.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

// Load framework dependency
require_once __DIR__ . '/../core/repositories/MetricsRepository.php';
require_once __DIR__ . '/../core/components/FioriTileBuilder.php';

use Core\Repositories\MetricsRepository;
use Core\Components\FioriTileBuilder as TileBuilder;

/*
|--------------------------------------------------------------------------
| I & D: Initialization & Data Extraction Protocol
|--------------------------------------------------------------------------
*/
// Create metrics repository and get inspection metrics
$metricsRepo = new MetricsRepository($conn);
$kpi = $metricsRepo->getInspectionKPIs();

/*
|--------------------------------------------------------------------------
| C: Configuration Definitions
|--------------------------------------------------------------------------
*/
$page_title = "Inspection Dashboard";
$page_description = "Select a module below to execute post-usage assessments and track inspection history.";
$topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Operations / Inspections Dashboard</h2>';
$extra_css = [];

/*
|--------------------------------------------------------------------------
| V: View Rendering (Launchpad Matrix)
|--------------------------------------------------------------------------
*/
ob_start();

// Build tiles using a config array (Declarative Tile Topology)
// To add or hide a tile later, edit this array only
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

/*
|--------------------------------------------------------------------------
| L: Global Layout Engine
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../core/layout.php';
?>