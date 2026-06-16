<?php
// This section prepares the admin manage venues page.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../core/repositories/MetricsRepository.php';
require_once __DIR__ . '/../core/components/FioriTileBuilder.php';

use Core\Repositories\MetricsRepository;
use Core\Components\FioriTileBuilder as TileBuilder;

$metricsRepo = new MetricsRepository($conn);
$kpi = $metricsRepo->getVenueKPIs();

$page_title = "Venue Management";
$page_description = "Configure physical assets, capacities, and system categories.";
$topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">System Administration / Manage Venues</h2>';
$extra_css = [];

ob_start();

echo TileBuilder::renderSection('Venue Registry', 'Configure venues, capacity constraints, and operational states.', [
    [
        'url' => 'manage_vcategory.php', 'title' => 'Venue Categories', 'icon' => 'tags',
        'desc' => 'Manage system-wide categories and base attributes.', 'kpi' => $kpi['total_categories'], 'action' => 'Manage Categories'
    ],
    [
        'url' => 'register_venue.php', 'title' => 'Register Venue', 'icon' => 'plus-square',
        'desc' => 'Add a new physical venue to the system repository.', 'kpi' => '<i data-lucide="door-open" class="w-8 h-8 opacity-20"></i>', 'action' => 'New Entry'
    ],
    [
        'url' => 'venue_directory.php', 'title' => 'Venue Directory', 'icon' => 'database',
        'desc' => 'Manage existing venues, capacities, and statuses.', 'kpi' => $kpi['total_venues'], 'action' => 'View Records'
    ]
]);

$page_content = ob_get_clean();
require_once __DIR__ . '/../core/layout.php';
?>
