<?php
// This section prepares the admin manage admins page.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../core/repositories/MetricsRepository.php';
require_once __DIR__ . '/../core/components/FioriTileBuilder.php';

use Core\Repositories\MetricsRepository;
use Core\Components\FioriTileBuilder as TileBuilder;

$metricsRepo = new MetricsRepository($conn);
$kpi = $metricsRepo->getPersonnelKPIs();

$page_title = "Identity Management";
$page_description = "Manage operational personnel, administrators, and student entities.";
$topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Identity Management / Dashboard</h2>';
$extra_css = [];

ob_start();

echo TileBuilder::renderSection('Operational Personnel', 'Register and manage system administrators and operational staff.', [
    [
        'url' => 'add_staff.php', 'title' => 'Register Staff', 'icon' => 'user-plus',
        'desc' => 'Add a new administrative or inspector node to the system.', 'kpi' => '<i data-lucide="user" class="w-8 h-8 opacity-20"></i>', 'action' => 'Execute Registration'
    ],
    [
        'url' => 'staff_directory.php', 'title' => 'Staff Directory', 'icon' => 'shield',
        'desc' => 'Manage existing staff members, roles, and access privileges.', 'kpi' => $kpi['combined_personnel'], 'action' => 'View Personnel'
    ]
]);

echo TileBuilder::renderSection('Client Entities', 'Manage registered end-users and resolve identity constraints.', [
    [
        'url' => 'manage_students.php', 'title' => 'Student Directory', 'icon' => 'graduation-cap',
        'desc' => 'Search, filter, and manage registered student accounts.', 'kpi' => $kpi['total_students'], 'action' => 'View Students'
    ]
]);

$page_content = ob_get_clean();
require_once __DIR__ . '/../core/layout.php';
?>
