<?php
// File: admin/inspections.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

// ∴ 嚴格引入架構依賴
require_once __DIR__ . '/../core/repositories/MetricsRepository.php';
require_once __DIR__ . '/../core/components/FioriTileBuilder.php';

use Core\Repositories\MetricsRepository;
use Core\Components\FioriTileBuilder as TileBuilder;

/*
|--------------------------------------------------------------------------
| I & D: Initialization & Data Extraction Protocol
|--------------------------------------------------------------------------
*/
// ∴ 實例化分析倉儲並提取檢驗領域的局部切片
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
$extra_css = ["../assets/css/fiori-tile.css"];

/*
|--------------------------------------------------------------------------
| V: View Rendering (Launchpad Matrix)
|--------------------------------------------------------------------------
*/
ob_start();

// ∴ 透過宣告式陣列建構磁磚拓撲 (Declarative Tile Topology)
// 未來若需新增或關閉特定 Tile，僅需在此陣列中進行 O(1) 增刪即可
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