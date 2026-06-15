<?php
// File: admin/venue_directory.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

// 引入基礎架構
require_once __DIR__ . '/../core/components/FilterBuilder.php';
require_once __DIR__ . '/../core/components/DataGridBuilder.php';
require_once __DIR__ . '/../core/repositories/VenueRepository.php'; // ∴ 引入倉儲層

use Core\Components\FilterBuilder;
use Core\Components\DataGridBuilder;
use Core\Repositories\VenueRepository;

$page_title = "Venue Directory";
$page_description = "Search, filter, and manage existing physical assets.";
$topbar_content = '
<div class="flex items-center">
    <a href="manage_venues.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Asset Management / Directory</h2>
</div>';
$extra_css = [];

$controller_config = [
    'edit_url_base' => 'register_venue.php?vid=',
    'delete_entity_name' => 'venue'
];
require_once __DIR__ . '/../core/components/datagrid_controller.php';

/*
|--------------------------------------------------------------------------
| I: Repository & Builder Instantiation
|--------------------------------------------------------------------------
*/
// ∴ 1. 實例化倉儲 (注入資料庫連線)
$venueRepo = new VenueRepository($conn);

// ∴ 2. 透過 Repository 獲取乾淨的資料字典，無需接觸 SQL
$cat_options = $venueRepo->getCategoryOptions();

$filterBuilder = new FilterBuilder('venue_directory.php', true);
$filterBuilder
    ->addField('text', 'f_name', 'Venue Name', [], 'Search name...', 'v.vname', 'LIKE')
    ->addField('select', 'f_cat', 'Category', $cat_options, 'All Categories', 'vc.category', '=')
    ->addField('select', 'f_status', 'Current State', [
        'available' => 'Available',
        'maintenance' => 'Maintenance',
        'closed' => 'Closed'
    ], 'All States', 'v.status', '=');

$gridBuilder = new DataGridBuilder('vid', '../actions/process_venue.php', 'venue');
$gridBuilder->setCreateAction('register_venue.php', 'Create Venue');
$gridBuilder->setRowActionUrl('register_venue.php?vid=%s');
$gridBuilder
    ->addColumn('vid', 'Identifier', 'text_muted_mono', ['width' => 'w-32'])
    ->addColumn('vname', 'Venue Name', 'link', ['url_format' => 'register_venue.php?vid=%s', 'width' => 'w-48'])
    ->addColumn('venue_category', 'Category', 'badge', ['width' => 'w-40'])
    ->addColumn('max_cap', 'Capacity', 'suffix_text', ['suffix' => 'Pax'])
    ->addColumn('deposit', 'Deposit', 'currency', ['prefix' => 'RM '])
    ->addColumn('status', 'Current State', 'map_badge', [
        'width' => 'w-40',
        'map' => [
            'available' => ['label' => 'Available', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'maintenance' => ['label' => 'Maintenance', 'class' => 'bg-red-50 text-red-700 border-red-200'],
            'closed' => ['label' => 'Closed', 'class' => 'bg-blue-50 text-blue-700 border-blue-200']
        ]
    ]);

/*
|--------------------------------------------------------------------------
| D: Abstracted Data Execution (Zero-SQL Zone)
|--------------------------------------------------------------------------
*/
// ∴ 3. 將 Filter 傳入倉儲，獲取結果。控制層不再存在任何 SQL 字串。
$result = $venueRepo->getAllWithFilters($filterBuilder);

/*
|--------------------------------------------------------------------------
| V: View Rendering
|--------------------------------------------------------------------------
*/
ob_start();
?>
<?php echo $filterBuilder->render(); ?>
<?php echo $gridBuilder->render($result); ?>
<?php
$page_content = ob_get_clean();
require_once __DIR__ . '/../core/layout.php';
?>