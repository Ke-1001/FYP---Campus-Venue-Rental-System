<?php
// File: admin/manage_bookings.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/booking_functions.php';

// ∴ 嚴格引入架構依賴
require_once __DIR__ . '/../core/repositories/MetricsRepository.php';
require_once __DIR__ . '/../core/components/FioriTileBuilder.php';

use Core\Repositories\MetricsRepository;
use Core\Components\FioriTileBuilder as TileBuilder;

syncCompletedBookings($conn);

/*
|--------------------------------------------------------------------------
| I & D: Initialization & Data Extraction Protocol
|--------------------------------------------------------------------------
*/
// ∴ 觸發領域邏輯：自動過期未付款之預約
expireUnpaidBookings($conn);

// ∴ 實例化分析倉儲並提取局部切片
$metricsRepo = new MetricsRepository($conn);
$kpi = $metricsRepo->getBookingKPIs();

/*
|--------------------------------------------------------------------------
| C: Configuration Definitions
|--------------------------------------------------------------------------
*/
$page_title = "Manage Bookings";
$page_description = "Select a module below to manage venue bookings, assign inspectors, and track records.";
$topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Bookings / Dashboard</h2>';
$extra_css = ["../assets/css/fiori-tile.css"];

/*
|--------------------------------------------------------------------------
| V: View Rendering (Launchpad Matrix)
|--------------------------------------------------------------------------
*/
ob_start();

// ∴ 透過宣告式陣列建構磁磚拓撲 (Declarative Tile Topology)
// 若未來需隱藏特定模組，僅需將該陣列元素註解即可
echo TileBuilder::renderSection('Operational Modules', 'Execute booking approvals, assign staff, and monitor active usage.', [
    [
        'url' => 'pending_requests.php', 
        'title' => 'Pending Requests', 
        'icon' => 'shield-alert',
        'desc' => 'Review and approve paid venue booking requests.', 
        'kpi' => $kpi['pending_requests'], 
        'action' => 'View Requests'
    ],
    [
        'url' => 'assign_inspector.php', 
        'title' => 'Assign Inspector', 
        'icon' => 'users',
        'desc' => 'Assign staff to inspect venues after they are used.', 
        'kpi' => $kpi['pending_assignments'], 
        'action' => 'Assign Staff'
    ],
    [
        'url' => 'track_bookings.php', 
        'title' => 'Track Bookings', 
        'icon' => 'activity',
        'desc' => 'Monitor ongoing bookings and view completed records.', 
        'kpi' => ($kpi['ongoing_bookings'] + $kpi['completed_bookings']), 
        'action' => 'View Bookings'
    ],
    [
        'url' => 'damage_reports.php', 
        'title' => 'Damage Reports', 
        'icon' => 'triangle-alert',
        'desc' => 'View damage reports submitted by users before venue usage.', 
        'kpi' => $kpi['damage_reports'], 
        'action' => 'View Reports'
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