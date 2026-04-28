<?php
// File path: actions/process_booking_action.php
session_start();
require_once '../includes/admin_auth.php'; // Enforce RBAC boundary
require_once '../config/db.php';

// 1. Protocol & Vector Validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Violation: Invalid HTTP Protocol Vector.'];
    header("Location: ../admin/pending_requests.php");
    exit;
}

// 💡 適配新架構：booking_id 為 VARCHAR，移除 intval()
$bid = htmlspecialchars(trim($_POST['booking_id'] ?? ''));
$action_type = $_POST['action_type'] ?? '';

// 提取當前審批此訂單的管理員 ID
$admin_aid = $_SESSION['aid'] ?? null;

if (empty($bid) || !in_array($action_type, ['approve', 'reject'], true)) {
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Anomaly: Malformed data payload detected.'];
    header("Location: ../admin/pending_requests.php");
    exit;
}

// 2. Pre-execution State Verification
$sql_check = "SELECT status FROM booking WHERE bid = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("s", $bid);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows === 0) {
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Critical: Target booking node does not exist.'];
    $stmt_check->close();
    header("Location: ../admin/pending_requests.php");
    exit;
}

$booking = $result->fetch_assoc();
$stmt_check->close();

// Invariant: Only 'pending' requests can be processed.
// 💡 適配新架構：小寫 pending
if ($booking['status'] !== 'pending') {
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'State Fault: Booking is no longer in an actionable state.'];
    header("Location: ../admin/pending_requests.php");
    exit;
}

// 3. State Machine Execution (Branching)
if ($action_type === 'approve') {
    // Sequence A: Authorization Granted
    $new_state = 'approved';
    
    // 💡 適配新架構：同時記錄審批人 (aid) 與審批時間 (approve_date)
    $sql_update = "UPDATE booking SET status = ?, aid = ?, approve_date = NOW() WHERE bid = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("sss", $new_state, $admin_aid, $bid);
    
    if ($stmt->execute()) {
        $_SESSION['toast'] = ['type' => 'success', 'msg' => "Authorization Granted: Booking vector {$bid} approved and locked."];
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Fault: ' . $stmt->error];
    }
    $stmt->close();

} elseif ($action_type === 'reject') {
    // Sequence B: Request Denied
    // 💡 適配新架構：無需跨表 Transaction，單表同時更新 status, payment_status 與 aid
    $new_state = 'rejected';
    $sql_update = "UPDATE booking SET status = ?, payment_status = 'refunded', aid = ? WHERE bid = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("sss", $new_state, $admin_aid, $bid);
    
    if ($stmt->execute()) {
        $_SESSION['toast'] = ['type' => 'success', 'msg' => "Request Denied: Booking {$bid} rejected and associated funds flagged for refund."];
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Fault: ' . $stmt->error];
    }
    $stmt->close();
}

// 4. Return Routing (導回 Pending Requests 工作列表)
header("Location: ../admin/pending_requests.php");
exit;
?>