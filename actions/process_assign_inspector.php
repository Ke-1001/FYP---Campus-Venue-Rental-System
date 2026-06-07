<?php
// File: actions/process_assign_inspector.php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bid = intval($_POST['bid']);
    $sid_raw = $_POST['sid'];

    // 💡 [NEW] 狀態機前置防護 (State Machine Validation)
    $stmt_check_booking = $conn->prepare("SELECT status FROM booking WHERE bid = ?");
    $stmt_check_booking->bind_param("i", $bid);
    $stmt_check_booking->execute();
    $b_res = $stmt_check_booking->get_result()->fetch_assoc();
    $stmt_check_booking->close();

    // 僅允許 approved 或 completed 狀態的訂單指派檢驗員
    if (!$b_res || !in_array($b_res['status'], ['approved', 'completed'])) {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => "State Fault: Booking {$bid} is not eligible for inspection (Current state: " . ($b_res['status'] ?? 'NULL') . ")."];
        header("Location: ../admin/assign_inspector.php");
        exit;
    }

    // 💡 [NEW] 冪等性約束 (Idempotency Constraint: 1:1 Mapping)
    $stmt_check_ins = $conn->prepare("SELECT ins_id FROM inspection WHERE bid = ? LIMIT 1");
    $stmt_check_ins->bind_param("i", $bid);
    $stmt_check_ins->execute();
    if ($stmt_check_ins->get_result()->num_rows > 0) {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => "Mapping Fault: Booking {$bid} already possesses an active inspection node."];
        header("Location: ../admin/assign_inspector.php");
        exit;
    }
    $stmt_check_ins->close();

    if ($sid_raw === 'RA01') {
        // 💡 [UPDATE] RA01 邏輯：增加 status = 'active' 約束
        $res = $conn->query("SELECT sid FROM staff WHERE position = 'inspector' AND status = 'active' ORDER BY RAND() LIMIT 1");
        if ($res->num_rows > 0) {
            $sid = $res->fetch_assoc()['sid'];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Resource Exhaustion: No active inspectors available in the pool.'];
            header("Location: ../admin/assign_inspector.php");
            exit;
        }
    } else {
        $sid = intval($sid_raw);
    }

    // 💡 注入檢驗節點 (Inspection Record)
    $sql = "INSERT INTO inspection (bid, sid, ins_status) VALUES (?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $bid, $sid);

    if ($stmt->execute()) {
        $_SESSION['toast'] = ['type' => 'success', 'msg' => "Execution Success: Inspector ID {$sid} allocated to Booking {$bid}."];
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => "Database Fault: " . $stmt->error];
    }
    
    header("Location: ../admin/assign_inspector.php");
    exit;
}