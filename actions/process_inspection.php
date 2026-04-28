<?php
// File: actions/process_inspection.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Payload 提取與嚴格轉型
    $bid = intval($_POST['bid']);
    $ins_status = $_POST['ins_status']; // 'passed' 或 'failed'
    $damage_desc = trim($_POST['damage_desc'] ?? '');
    $penalty = floatval($_POST['penalty'] ?? 0.00);

    // 2. 狀態機約束防護：如果是 Passed，強制清洗不合理的罰款資料
    if ($ins_status === 'passed') {
        $damage_desc = 'No damage. Venue is in standard condition.';
        $penalty = 0.00;
    }

    // 3. 啟動原子交易 (Atomic Transaction)
    $conn->begin_transaction();

    try {
        // 💡 步驟 A：更新 Inspection 表 (將 pending 轉為 passed/failed)
        $sql_ins = "UPDATE inspection 
                    SET ins_status = ?, damage_desc = ?, penalty = ? 
                    WHERE bid = ? AND ins_status = 'pending'";
        
        $stmt_ins = $conn->prepare($sql_ins);
        $stmt_ins->bind_param("ssdi", $ins_status, $damage_desc, $penalty, $bid);
        $stmt_ins->execute();

        // 如果 affected_rows 為 0，代表該訂單不存在，或是已經被檢驗過了 (防止重複點擊/重複執行)
        if ($stmt_ins->affected_rows === 0) {
            throw new Exception("State Mutation Rejected: Inspection already processed or invalid.");
        }
        $stmt_ins->close();

        // 💡 步驟 B：生成結算 Report (選配，確保 process_flow.php 中能讀取到)
        // 取得剛剛更新的 ins_id
        $res = $conn->query("SELECT ins_id FROM inspection WHERE bid = $bid");
        if ($res && $res->num_rows > 0) {
            $ins_id = $res->fetch_assoc()['ins_id'];
            $refund_status = ($penalty > 0) ? 'partial_refund' : 'full_refund';
            
            // 寫入報表
            $sql_rpt = "INSERT IGNORE INTO report (ins_id, refund_status) VALUES (?, ?)";
            $stmt_rpt = $conn->prepare($sql_rpt);
            $stmt_rpt->bind_param("is", $ins_id, $refund_status);
            $stmt_rpt->execute();
            $stmt_rpt->close();
        }

        // 交易提交
        $conn->commit();
        $_SESSION['toast'] = ['type' => 'success', 'msg' => "Execution Success: Assessment for Booking {$bid} has been finalized."];

    } catch (Exception $e) {
        // 交易回滾
        $conn->rollback();
        $_SESSION['toast'] = ['type' => 'error', 'msg' => $e->getMessage()];
    }

    // 💡 任務完成，跳轉至追蹤歷史頁面
    header("Location: ../admin/track_inspections.php");
    exit;
} else {
    header("Location: ../admin/inspections.php");
    exit;
}
?>