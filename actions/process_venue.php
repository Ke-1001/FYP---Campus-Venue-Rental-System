<?php
// File path: actions/process_venue.php
session_start();
require_once '../includes/admin_auth.php'; // 💡 補上安全閘道，確保 API 受到 RBAC 保護
require_once '../config/db.php';

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // 💡 適配新架構：接收前端的 new_vid 以及新欄位名稱
    $vid = htmlspecialchars(trim($_POST['new_vid'] ?? ''));
    $vname = htmlspecialchars(trim($_POST['vname'] ?? ''));
    $category = $_POST['category'] ?? '';
    $max_cap = intval($_POST['max_cap'] ?? 0);
    $deposit = floatval($_POST['deposit'] ?? 0.00);
    $status = 'available'; // 預設小寫狀態

    // 1. 防護機制：檢查 VID 是否已存在 (因為現在是手動輸入的 VARCHAR)
    $sql_check = "SELECT vid FROM venue WHERE vid = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $vid);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Conflict: Venue ID already exists in the registry.'];
        $stmt_check->close();
        header("Location: ../admin/manage_venues.php");
        exit;
    }
    $stmt_check->close();

    // 2. 寫入資料庫 (💡 注入空字串給 description 以滿足 NOT NULL 條件)
    $sql = "INSERT INTO venue (vid, vname, category, max_cap, deposit, status, description) VALUES (?, ?, ?, ?, ?, ?, '')";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // sssids -> 3 Strings, 1 Int, 1 Double, 1 String
        $stmt->bind_param("sssids", $vid, $vname, $category, $max_cap, $deposit, $status);
        if ($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Node Registered: Venue added successfully.'];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database insertion failed: ' . $stmt->error];
        }
        $stmt->close();
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'SQL Syntax Error: ' . $conn->error];
    }
    header("Location: ../admin/manage_venues.php");
    exit;

} elseif ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // 💡 適配新架構：接收 vid 作為字串
    $vid = htmlspecialchars(trim($_POST['vid'] ?? ''));
    $vname = htmlspecialchars(trim($_POST['vname'] ?? ''));
    $category = $_POST['category'] ?? '';
    $max_cap = intval($_POST['max_cap'] ?? 0);
    $deposit = floatval($_POST['deposit'] ?? 0.00);
    $status = $_POST['status'] ?? 'available';

    // 💡 適配新架構：更新 venue 表
    $sql = "UPDATE venue SET vname = ?, category = ?, max_cap = ?, deposit = ?, status = ? WHERE vid = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // ssidss -> 2 Strings, 1 Int, 1 Double, 2 Strings
        $stmt->bind_param("ssidss", $vname, $category, $max_cap, $deposit, $status, $vid);
        if ($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Configuration Deployed: Venue updated successfully.'];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database update failed: ' . $stmt->error];
        }
        $stmt->close();
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'SQL Syntax Error: ' . $conn->error];
    }
    header("Location: ../admin/manage_venues.php");
    exit;

} elseif ($action === 'delete' && isset($_GET['vid'])) {
    // 💡 適配新架構：GET 參數已經改為 vid，且為字串
    $vid = htmlspecialchars(trim($_GET['vid']));

    // 💡 檢查關聯的 booking 表，確認是否有活躍訂單
    $sql_check = "SELECT COUNT(*) AS active_count FROM booking WHERE vid = ? AND status IN ('pending', 'approved')";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $vid);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();
    $stmt_check->close();

    if ($row_check['active_count'] > 0) {
        $_SESSION['toast'] = [
            'type' => 'error', 
            'msg' => "Termination Blocked: Node has {$row_check['active_count']} active bookings."
        ];
        header("Location: ../admin/manage_venues.php");
        exit;
    }

    // 💡 適配新架構：從 venue 表刪除
    $sql = "DELETE FROM venue WHERE vid = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $vid);
        if ($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Node Terminated: Venue deleted successfully.'];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database deletion failed: ' . $stmt->error];
        }
        $stmt->close();
    }
    header("Location: ../admin/manage_venues.php");
    exit;
} else {
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Invalid request vector.'];
    header("Location: ../admin/manage_venues.php");
    exit;
}
?>