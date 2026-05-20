<?php

require_once("../config/db.php");
require_once('../includes/admin_auth.php');

/*
|--------------------------------------------------------------------------
| Page Config
|--------------------------------------------------------------------------
*/

$page_title = "Page Name";

$page_description = "Description";

$topbar_content = '
<h2>Path</h2>
';

$extra_css = [
    "../assets/css/table.css"
];

/*
|--------------------------------------------------------------------------
| Page Content
|--------------------------------------------------------------------------
*/

ob_start();
?>

YOUR CONTENT HERE

<?php

$page_content = ob_get_clean();

include('../core/layout.php');
?>