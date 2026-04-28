<?php
// File path: actions/process_inspection.php
session_start();
require_once '../includes/admin_auth.php'; // 確保 API 受到權限保護
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Invalid request protocol.'];
    header("Location: ../admin/inspections.php");
    exit;
}

// 💡 1. 接收並清理新版 Payload
$bid = htmlspecialchars(trim($_POST['bid']));
$ins_status = $_POST['ins_status']; // 'passed' or 'failed'
$damage_desc = htmlspecialchars(trim($_POST['damage_desc']));
$penalty = floatval($_POST['penalty']);

// 💡 2. 解析目標訂單與場地
$sql_check = "SELECT b.vid, v.deposit FROM booking b JOIN venue v ON b.vid = v.vid WHERE b.bid = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("s", $bid);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows === 0) {
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Critical Error: Booking payload not found.'];
    header("Location: ../admin/inspections.php");
    exit;
}
$row = $result->fetch_assoc();
$vid = $row['vid']; 
$deposit_paid = $row['deposit']; 
$stmt_check->close();

// 💡 3. Staff ID (sid) 關聯防護機制
// 由於 inspection 表強制要求 FK `sid`，但操作者是 Admin，我們在此抓取或建立一個系統預設的 Staff
$sid = 'SYS-STAFF';
$staff_check = $conn->query("SELECT sid FROM staff LIMIT 1");
if ($staff_check->num_rows > 0) {
    $sid = $staff_check->fetch_row()[0];
} else {
    // 系統初次運行，沒有 Staff 時自動建立一個虛擬檢驗員
    $conn->query("INSERT INTO staff (sid, staff_name, email, password, phone_num) VALUES ('$sid', 'System Auto Inspector', 'sys@mmu.edu.my', 'N/A', '000')");
}

// 💡 4. 生成新架構所需的主鍵 (UUID Hash)
$ins_id = 'INS-' . strtoupper(substr(md5(uniqid('ins', true)), 0, 8));
$rid = 'RPT-' . strtoupper(substr(md5(uniqid('rpt', true)), 0, 8));

$conn->begin_transaction();

try {
    // Action A: 寫入 inspection 表
    $sql_inspect = "INSERT INTO inspection (ins_id, bid, sid, ins_status, damage_desc, damage_cost, penalty, inspected_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt_inspect = $conn->prepare($sql_inspect);
    $stmt_inspect->bind_param("sssssdd", $ins_id, $bid, $sid, $ins_status, $damage_desc, $penalty, $penalty);
    $stmt_inspect->execute();
    $stmt_inspect->close();

    // Action B: 寫入 report 表 (完成財務結算閉環)
    $refund_status = ($penalty >= $deposit_paid) ? 'none' : 'processed';
    $penalty_status = ($penalty > 0) ? 'paid' : 'none';
    $sql_report = "INSERT INTO report (rid, ins_id, final_deduct, refund_status, penalty_status, created_at) VALUES (?, ?, ?, ?, ?, CURDATE())";
    $stmt_report = $conn->prepare($sql_report);
    $stmt_report->bind_param("ssdss", $rid, $ins_id, $penalty, $refund_status, $penalty_status);
    $stmt_report->execute();
    $stmt_report->close();

    // Action C: 更新 booking 表，標記資金流為 'refunded' (代表押金已處理/結算完畢)
    // 註：狀態已由 Sweep 腳本設為 completed，故此處僅需更新 payment_status
    $sql_booking = "UPDATE booking SET payment_status = 'refunded' WHERE bid = ?";
    $stmt_booking = $conn->prepare($sql_booking);
    $stmt_booking->bind_param("s", $bid);
    $stmt_booking->execute();
    $stmt_booking->close();

    $toast_msg = "Booking {$bid} finalized. Report generated. Penalty: RM " . number_format($penalty, 2) . ". ";

    // 💡 Action D: 嚴重損壞處理 (Cascade Lock & Cancellation)
    if ($ins_status === 'failed') {
        // 鎖定場地
        $sql_venue = "UPDATE venue SET status = 'maintenance' WHERE vid = ?";
        $stmt_venue = $conn->prepare($sql_venue);
        $stmt_venue->bind_param("s", $vid);
        $stmt_venue->execute();
        $stmt_venue->close();

        // 撈取並取消未來衝突的訂單
        $sql_future = "SELECT bid FROM booking WHERE vid = ? AND status IN ('pending', 'approved')";
        $stmt_future = $conn->prepare($sql_future);
        $stmt_future->bind_param("s", $vid);
        $stmt_future->execute();
        $future_result = $stmt_future->get_result();
        
        $cancelled_count = 0;
        while ($future_row = $future_result->fetch_assoc()) {
            $f_bid = $future_row['bid'];
            // 將未來的訂單強制轉為 rejected 與 refunded
            $conn->query("UPDATE booking SET status = 'rejected', payment_status = 'refunded' WHERE bid = '$f_bid'");
            $cancelled_count++;
        }
        $stmt_future->close();
        
        $toast_msg .= "WARNING: Venue Locked. {$cancelled_count} future active bookings cancelled and refunded.";
    }

    $conn->commit();
    $_SESSION['toast'] = ['type' => 'success', 'msg' => $toast_msg];

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'System settlement failed: ' . $e->getMessage()];
}

$conn->close();
header("Location: ../admin/inspections.php");
exit;
?>