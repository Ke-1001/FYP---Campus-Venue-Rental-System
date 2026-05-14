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

        // 💡 步驟 B：生成結算 Report (修復 Enum 崩潰漏洞)
        $res = $conn->query("SELECT ins_id FROM inspection WHERE bid = $bid");
        if ($res && $res->num_rows > 0) {
            $ins_id = $res->fetch_assoc()['ins_id'];
            
            // 嚴格對齊 rental_venue (4).sql 的 Enum: 'none','pending','processed'
            // 檢驗完成後，退款狀態一律進入 'pending' 讓財務去處理。
            $refund_status = 'pending'; 
            $penalty_status = ($penalty > 0) ? 'pending' : 'none';
            
            // 加入 CURDATE() 解決 created_at 變成 0000-00-00 的問題
            $sql_rpt = "INSERT IGNORE INTO report (ins_id, final_deduct, refund_status, penalty_status, created_at) VALUES (?, ?, ?, ?, CURDATE())";
            $stmt_rpt = $conn->prepare($sql_rpt);
            $stmt_rpt->bind_param("idss", $ins_id, $penalty, $refund_status, $penalty_status);
            $stmt_rpt->execute();
            $stmt_rpt->close();
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