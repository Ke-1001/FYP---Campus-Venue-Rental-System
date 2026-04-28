<?php
// File path: includes/admin_auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Dynamic Identifier Resolution (Forward Compatibility for Migration)
// Resolves the new 'aid' schema while tolerating the legacy 'user_id' during transition
$admin_identifier = $_SESSION['aid'] ?? ($_SESSION['user_id'] ?? null);
$admin_role = $_SESSION['role'] ?? '';

// 2. Strict RBAC Verification (Mapped to new Schema ENUMs)
// Validates against the new lowercase 'admin' and 'super_admin' states
if (!$admin_identifier || !in_array($admin_role, ['admin', 'super_admin'], true)) {
    // Inject a forced logout to clear corrupted legacy sessions
    session_unset();
    session_destroy();
    header("Location: ../admin/login.php?error=access_denied");
    exit();
}

// 3. Idle Timeout Security (30 minutes)
$timeout_duration = 1800; 

if (isset($_SESSION['last_activity'])) {
    $elapsed_time = time() - $_SESSION['last_activity'];
    
    if ($elapsed_time > $timeout_duration) {
        session_unset();
        session_destroy();
        header("Location: ../admin/login.php?error=timeout");
        exit();
    }
}

// 4. Refresh Activity Timestamp
$_SESSION['last_activity'] = time(); 
?>