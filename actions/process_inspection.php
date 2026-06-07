<?php
// File: actions/process_inspection.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $bid = intval($_POST['bid']);
    $ins_status = $_POST['ins_status']; 
    $damage_desc = trim($_POST['damage_desc'] ?? '');
    $penalty = floatval($_POST['penalty'] ?? 0.00);

    // 狀態機約束防護
    if ($ins_status === 'passed') {
        $damage_desc = 'No damage. Venue is in standard condition.';
        $penalty = 0.00;
    }

    $conn->begin_transaction();

    try {
        // 💡 步驟 A：更新 Inspection 表
        $sql_ins = "UPDATE inspection 
                    SET ins_status = ?, damage_desc = ?, penalty = ?, inspected_at = NOW() 
                    WHERE bid = ? AND ins_status = 'pending'";
        
        $stmt_ins = $conn->prepare($sql_ins);
        $stmt_ins->bind_param("ssdi", $ins_status, $damage_desc, $penalty, $bid);
        $stmt_ins->execute();

        if ($stmt_ins->affected_rows === 0) {
            throw new Exception("State Mutation Rejected: Inspection already processed or invalid.");
        }
        $stmt_ins->close();

        // 💡 步驟 B：生成結算 Report 與更新用戶債務 (對齊超額罰款邏輯)
        $sql_fetch = "SELECT i.ins_id, b.uid, v.deposit 
                      FROM inspection i
                      JOIN booking b ON i.bid = b.bid
                      JOIN venue v ON b.vid = v.vid
                      WHERE i.bid = ?";
        $stmt_fetch = $conn->prepare($sql_fetch);
        $stmt_fetch->bind_param("i", $bid);
        $stmt_fetch->execute();
        $meta = $stmt_fetch->get_result()->fetch_assoc();
        $stmt_fetch->close();

        if ($meta) {
            $ins_id = $meta['ins_id'];
            $uid = $meta['uid'];
            $deposit = floatval($meta['deposit']);
            
            // 數學演算與增量定義
            $excess = $penalty - $deposit;
            $delta_debt = ($excess > 0) ? $excess : 0.00;
            
            // 動態狀態轉移：若罰款 >= 押金，則無剩餘金額可退 (refund_status = 'none')
            $refund_status = ($penalty >= $deposit) ? 'none' : 'pending'; 
            $penalty_status = ($penalty > 0) ? 'pending' : 'none';
            
            // 1. 寫入 Report 結算節點
            $sql_rpt = "INSERT IGNORE INTO report (ins_id, final_deduct, refund_status, penalty_status, created_at) VALUES (?, ?, ?, ?, CURDATE())";
            $stmt_rpt = $conn->prepare($sql_rpt);
            $stmt_rpt->bind_param("idss", $ins_id, $penalty, $refund_status, $penalty_status);
            $stmt_rpt->execute();
            $stmt_rpt->close();
            
            // 2. 執行資料庫級別原子化債務累加，防止 Race Condition
            if ($delta_debt > 0) {
                $sql_user = "UPDATE user SET outstanding_debt = outstanding_debt + ? WHERE uid = ?";
                $stmt_user = $conn->prepare($sql_user);
                $stmt_user->bind_param("ds", $delta_debt, $uid);
                $stmt_user->execute();
                $stmt_user->close();
            }
        }

        $conn->commit();
        $_SESSION['toast'] = ['type' => 'success', 'msg' => "Execution Success: Assessment finalized."];

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast'] = ['type' => 'error', 'msg' => $e->getMessage()];
    }

    header("Location: ../admin/pending_inspections.php");
    exit;
} else {
    header("Location: ../admin/pending_inspections.php");
    exit;
}