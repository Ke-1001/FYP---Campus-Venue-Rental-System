<?php
// File path: actions/process_approval.php
session_start();
require_once '../includes/admin_auth.php'; // 🔒 確保只有管理員能觸發此 API
require_once '../config/db.php';

// Security check: ensure bid and action are received
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Invalid request parameters.'];
    header("Location: ../admin/manage_bookings.php");
    exit;
}

// 💡 1. 嚴格轉型：將 GET 傳入的識別碼強制轉為整數
$bid = intval($_GET['id']);
$action = $_GET['action'];

if ($bid === 0) {
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Fatal: Invalid Booking ID.'];
    header("Location: ../admin/manage_bookings.php");
    exit;
}

$conn->begin_transaction();

try {
    if ($action === 'approve') {
        // 💡 2. 狀態更新，綁定型態為 "i" (整數)
        $sql = "UPDATE booking SET status = 'approved', approve_date = NOW() WHERE bid = ? AND status = 'pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $bid); 
        $stmt->execute();
        
        $_SESSION['toast'] = [
            'type' => 'success', 
            'msg' => "Execution Successful: Order {$bid} has been approved."
        ];

    } elseif ($action === 'reject') {
        // 💡 3. 原子性退回，綁定型態為 "i" (整數)
        $sql_booking = "UPDATE booking SET status = 'rejected', payment_status = 'refunded' WHERE bid = ? AND status = 'pending'";
        $stmt_booking = $conn->prepare($sql_booking);
        $stmt_booking->bind_param("i", $bid);
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

// 審批完成後導向 Pending Requests
header("Location: ../admin/pending_requests.php");
exit;
?>