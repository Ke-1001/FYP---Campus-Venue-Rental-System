<?php
// File: actions/process_assign_inspector.php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bid = intval($_POST['bid']);
    $sid_raw = $_POST['sid'];

    if ($sid_raw === 'RA01') {
        // 💡 RA01 邏輯：從 inspector 池中隨機挑選一位
        $res = $conn->query("SELECT sid FROM staff WHERE position = 'inspector' ORDER BY RAND() LIMIT 1");
        if ($res->num_rows > 0) {
            $sid = $res->fetch_assoc()['sid'];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'No active inspectors found in system.'];
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
        $_SESSION['toast'] = ['type' => 'success', 'msg' => "Success: Inspector ID {$sid} allocated to Booking {$bid}."];
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => "DB Error: " . $stmt->error];
    }
    
    header("Location: ../admin/assign_inspector.php");
    exit;
}