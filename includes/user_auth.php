<?php
session_start();

if (!isset($_SESSION['uid'])) {
    header("Location: user_login.php");
    exit();
}
?>