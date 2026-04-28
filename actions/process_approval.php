<?php
// File path: actions/process_approval.php
session_start();
require_once '../includes/admin_auth.php'; // 💡 補上安全閘道，確保只有管理員能觸發此 API
require_once '../config/db.php';

// Security check: ensure bid and action are received
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Invalid request parameters.'];
    header("Location: ../admin/manage_bookings.php");
    exit;
}

// 💡 適配新架構：主鍵為 VARCHAR，移除 intval()
$bid = htmlspecialchars(trim($_GET['id']));
$action = $_GET['action'];

$conn->begin_transaction();

try {
    if ($action === 'approve') {
        // 💡 適配新架構：更新 status 為 approved，並記錄 approve_date
        $sql = "UPDATE booking SET status = 'approved', approve_date = NOW() WHERE bid = ? AND status = 'pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $bid); // "s" for string
        $stmt->execute();
        
        $_SESSION['toast'] = [
            'type' => 'success', 
            'msg' => "Execution Successful: Order {$bid} has been approved."
        ];

    } elseif ($action === 'reject') {
        // 💡 適配新架構：在同一個表中同時更新訂單狀態與退款狀態，實現真正的原子性 (Atomicity)
        $sql_booking = "UPDATE booking SET status = 'rejected', payment_status = 'refunded' WHERE bid = ? AND status = 'pending'";
        $stmt_booking = $conn->prepare($sql_booking);
        $stmt_booking->bind_param("s", $bid); // "s" for string
        $stmt_booking->execute();

        $_SESSION['toast'] = [
            'type' => 'success', 
            'msg' => "Execution Successful: Order {$bid} rejected. Deposit refund initiated."
        ];
    } else {
        throw new Exception("Unknown action vector.");
    }

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['toast'] = [
        'type' => 'error', 
        'msg' => 'System error: ' . $e->getMessage()
    ];
}

$conn->close();

// 審批完成後導向 Launchpad 或 Pending Requests
header("Location: ../admin/pending_requests.php");
exit;
?>