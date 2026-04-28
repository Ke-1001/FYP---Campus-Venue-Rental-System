<?php
// File path: actions/process_venue.php
session_start();
require_once '../config/db.php';

// 確保管理員權限
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    die("Access Denied: Administrative privileges required.");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // === 節點註冊 (Node Registration) ===
    $vname = htmlspecialchars(trim($_POST['vname'] ?? $_POST['venue_name'] ?? ''));
    $category = $_POST['category'];
    $max_cap = intval($_POST['max_cap'] ?? $_POST['capacity'] ?? 0);
    $deposit = floatval($_POST['deposit'] ?? $_POST['base_deposit'] ?? 0.00);
    $status = 'available'; // 強制小寫 Enum
    $description = ''; // 滿足 NOT NULL 約束

    // 💡 寫入 venue 表 (無須指定 vid，由 DB 自動生成 1000+ 數值)
    $sql = "INSERT INTO venue (vname, category, max_cap, deposit, status, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // "ssids" -> 2 Strings, 1 Int, 1 Double, 2 Strings
        $stmt->bind_param("ssisss", $vname, $category, $max_cap, $deposit, $status, $description);
        if ($stmt->execute()) {
            $new_vid = $conn->insert_id; // 提取原生的純整數 ID
            $_SESSION['toast'] = ['type' => 'success', 'msg' => "Node Registered: Venue [{$new_vid}] added successfully."];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Fault: ' . $stmt->error];
        }
        $stmt->close();
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'SQL Syntax Error: ' . $conn->error];
    }
    header("Location: ../admin/manage_venues.php");
    exit;

} elseif ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // === 組態更新 (Configuration Update) ===
    $vid = intval($_POST['vid'] ?? $_POST['venue_id'] ?? 0); // 💡 強制轉型為整數
    $vname = htmlspecialchars(trim($_POST['vname'] ?? $_POST['venue_name'] ?? ''));
    $category = $_POST['category'];
    $max_cap = intval($_POST['max_cap'] ?? $_POST['capacity'] ?? 0);
    $deposit = floatval($_POST['deposit'] ?? $_POST['base_deposit'] ?? 0.00);
    $status = strtolower($_POST['status'] ?? 'available'); // 保證小寫

    $sql = "UPDATE venue SET vname = ?, category = ?, max_cap = ?, deposit = ?, status = ? WHERE vid = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // "ssidsi" -> 2 Strings, 1 Int, 1 Double, 1 String, 1 Int(vid)
        $stmt->bind_param("ssidsi", $vname, $category, $max_cap, $deposit, $status, $vid);
        if ($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'msg' => "Configuration Deployed: Venue [{$vid}] updated."];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Fault: ' . $stmt->error];
        }
        $stmt->close();
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'SQL Syntax Error: ' . $conn->error];
    }
    header("Location: ../admin/manage_venues.php");
    exit;

} elseif ($action === 'delete') {
    // === 實體銷毀 (Entity Revocation) ===
    $vid = intval($_GET['vid'] ?? $_GET['id'] ?? 0);

    // 檢查是否有活躍預約 (狀態對齊 pending, approved)
    $sql_check = "SELECT COUNT(*) AS active_count FROM booking WHERE vid = ? AND status IN ('pending', 'approved')";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $vid); // "i"
    $stmt_check->execute();
    $row_check = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if ($row_check['active_count'] > 0) {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => "Termination Blocked: Node has {$row_check['active_count']} active reservations."];
        header("Location: ../admin/manage_venues.php");
        exit;
    }

    $sql = "DELETE FROM venue WHERE vid = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $vid); // "i"
        if ($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Node Terminated: Venue deleted successfully.'];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Deletion Fault: ' . $stmt->error];
        }
        $stmt->close();
    }
    header("Location: ../admin/manage_venues.php");
    exit;
} else {
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Invalid HTTP Request Vector.'];
    header("Location: ../admin/manage_venues.php");
    exit;
}
?>