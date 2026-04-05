<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. role check: only allow logged-in users with 'Admin' role to access these pages
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Normal_Admin', 'Super_Admin'])) {
    header("Location: ../admin/login.php?error=access_denied");
    exit();
}

// 2. Idle Timeout: If admin is idle for more than 30 minutes, automatically log them out for security
$timeout_duration = 1800; // Set idle time: 1800 seconds (30 minutes)

if (isset($_SESSION['last_activity'])) {
    // Calculate the difference between "current time" and "last activity time"
    $elapsed_time = time() - $_SESSION['last_activity'];
    
    if ($elapsed_time > $timeout_duration) {
        // If idle for more than 30 minutes, force session destruction and redirect to login page
        session_unset();
        session_destroy();
        header("Location: ../admin/login.php?error=timeout");
        exit();
    }
}

// 3. Update the last activity time to "now"
// As long as the admin is clicking around the page, this time will keep refreshing, so they won't be logged out
$_SESSION['last_activity'] = time(); 

?>