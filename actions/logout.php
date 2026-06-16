<?php
// File path: actions/logout.php
session_start();
$_SESSION = array();
session_destroy();
header("Location: ../admin/login.php");
exit;
?>