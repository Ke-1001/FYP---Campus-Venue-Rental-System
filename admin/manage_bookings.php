<?php

session_start();

require_once("../config/db.php");
require_once('../includes/admin_auth.php');

/*
|--------------------------------------------------------------------------
| KPI Queries
|--------------------------------------------------------------------------
*/

$kpi_pending = $conn->query("
    SELECT COUNT(*)
    FROM booking
    WHERE status = 'pending'
    AND payment_status = 'paid'
")->fetch_row()[0] ?? 0;

$kpi_ongoing = $conn->query("
    SELECT COUNT(*)
    FROM booking
    WHERE status = 'approved'
")->fetch_row()[0] ?? 0;

$kpi_returned = $conn->query("
    SELECT COUNT(*)
    FROM booking
    WHERE status = 'completed'
")->fetch_row()[0] ?? 0;

$sql_kpi_assign = "
    SELECT COUNT(*)
    FROM booking b
    LEFT JOIN inspection i ON b.bid = i.bid
    WHERE b.status IN ('approved', 'completed')
    AND b.payment_status = 'paid'
    AND i.ins_id IS NULL
";

$kpi_assign = $conn->query($sql_kpi_assign)->fetch_row()[0] ?? 0;

/*
|--------------------------------------------------------------------------
| Page Config
|--------------------------------------------------------------------------
*/

$page_title = "Manage Bookings";

$page_description = "Select a module below to manage venue bookings, assign inspectors, and track records.";

$topbar_content = '
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">
        Bookings / Dashboard
    </h2>
';

$extra_css = [
    "../assets/css/fiori-tile.css"
];

/*
|--------------------------------------------------------------------------
| Page Content
|--------------------------------------------------------------------------
*/

ob_start();
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

    <!-- Pending Requests -->
    <a href="pending_requests.php" class="fiori-tile">

        <div class="fiori-tile-header">
            <h3 class="fiori-tile-title">
                Pending Requests
            </h3>

            <i data-lucide="shield-alert"
               class="w-5 h-5 fiori-tile-icon"></i>
        </div>

        <p class="fiori-tile-desc">
            Review and approve new venue booking requests.
        </p>

        <div class="fiori-tile-kpi">
            <?php echo $kpi_pending; ?>
        </div>

        <div class="fiori-tile-footer">
            View Requests

            <i data-lucide="arrow-right"
               class="w-3 h-3 ml-2"></i>
        </div>

    </a>

    <!-- Assign Inspector -->
    <a href="assign_inspector.php" class="fiori-tile">

        <div class="fiori-tile-header">
            <h3 class="fiori-tile-title">
                Assign Inspector
            </h3>
        </div>

        <p class="fiori-tile-desc">
            Assign staff to inspect venues after they are used.
        </p>

        <div class="fiori-tile-kpi">
            <i data-lucide="users"
               class="w-5 h-5 fiori-tile-icon"></i>

            <?php echo $kpi_assign; ?>
        </div>

        <div class="fiori-tile-footer">
            Assign Staff

            <i data-lucide="arrow-right"
               class="w-3 h-3 ml-2"></i>
        </div>

    </a>

    <!-- Track Bookings -->
    <a href="track_bookings.php" class="fiori-tile">

        <div class="fiori-tile-header">
            <h3 class="fiori-tile-title">
                Track Bookings
            </h3>

            <i data-lucide="activity"
               class="w-5 h-5 fiori-tile-icon"></i>
        </div>

        <p class="fiori-tile-desc">
            Monitor ongoing bookings and view past records.
        </p>

        <div class="fiori-tile-kpi">
            <?php echo ($kpi_ongoing + $kpi_returned); ?>
        </div>

        <div class="fiori-tile-footer">
            View Bookings

            <i data-lucide="arrow-right"
               class="w-3 h-3 ml-2"></i>
        </div>

    </a>

</div>

<?php

$page_content = ob_get_clean();

/*
|--------------------------------------------------------------------------
| Render Layout
|--------------------------------------------------------------------------
*/

include('../core/layout.php');
?>