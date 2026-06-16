<?php
// File: admin/manage_bookings.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/booking_functions.php';

// Load framework dependency
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
// Run logic: auto-expire unpaid bookings
expireUnpaidBookings($conn);

// Create metrics repository and get metrics
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
$extra_css = [];

/*
|--------------------------------------------------------------------------
| V: View Rendering (Launchpad Matrix)
|--------------------------------------------------------------------------
*/
ob_start();

// Build tiles using a config array (Declarative Tile Topology)
// iffuturehideModule,
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
    ]]);

$page_content = ob_get_clean();

/*
|--------------------------------------------------------------------------
| L: Global Layout Engine
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../core/layout.php';
?>