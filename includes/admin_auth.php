<?php
// File path: includes/admin_auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Dynamic Identifier Resolution (Forward Compatibility for Migration)
$admin_identifier = $_SESSION['aid'] ?? ($_SESSION['user_id'] ?? null);
$admin_role = $_SESSION['role'] ?? '';

// 2. Strict RBAC Verification (Mapped to new Schema ENUMs)
if (!$admin_identifier || !in_array($admin_role, ['admin', 'super_admin'], true)) {
    // Inject a forced logout to clear corrupted legacy sessions
    session_unset();
    session_destroy();
    header("Location: ../admin/login.php?error=access_denied");
    exit();
}

// 3. Continuous Status Verification (狀態機即時攔截)
// 確保該帳號沒有在登入後被其他 Super Admin 設為 Inactive 或刪除
global $conn;
if (isset($conn)) {
    $auth_stmt = $conn->prepare("SELECT status FROM admin WHERE aid = ? LIMIT 1");
    $auth_stmt->bind_param("i", $admin_identifier);
    $auth_stmt->execute();
    $auth_res = $auth_stmt->get_result();
    
    if ($auth_res->num_rows === 1) {
        $auth_row = $auth_res->fetch_assoc();
        if ($auth_row['status'] === 'inactive') {
            // 💡 若狀態為 inactive，強制銷毀 Session 並踢回登入頁
            session_unset();
            session_destroy();
            header("Location: ../admin/login.php?error=account_revoked");
            exit();
        }
    } else {
        // 💡 找不到該帳號 (可能已被物理刪除)，強制登出
        session_unset();
        session_destroy();
        header("Location: ../admin/login.php?error=account_deleted");
        exit();
    }
    $auth_stmt->close();
}

// 4. Idle Timeout Security (30 minutes)
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

// 5. Refresh Activity Timestamp
$_SESSION['last_activity'] = time(); 
?>