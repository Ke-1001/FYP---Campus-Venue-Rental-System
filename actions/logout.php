<?php
session_start();

// clear all session variables
session_unset();

// destroy the session
session_destroy();

// redirect to login page
header("Location: ../admin/login.php");
exit();
?>