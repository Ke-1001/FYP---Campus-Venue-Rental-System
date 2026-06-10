<?php
// File: actions/process_personnel_deletion.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

// 💡 1. 擷取與解構 Bulk Payload (Bulk Payload Decoupling)
$action = $_POST['action'] ?? '';
$ids = $_POST['ids'] ?? [];

// 防禦點 A：拓撲結構斷言
if ($action !== 'bulk_delete' || empty($ids) || !is_array($ids)) {
    die("Execution Fault: Invalid Action or Payload Topology.");
}

// 啟動事務矩陣 (Transaction Matrix)
$conn->begin_transaction();

try {
    $success_count = 0;
    
    // ∴ 建立 Statement 緩存陣列，優化迴圈內的記憶體開銷
    $stmts = [];

    // 💡 2. 執行迭代解構 (Iterative Decoupling) 
    // ∀ entity ∈ ids 進行處理
    foreach ($ids as $compound_id) {
        $parts = explode('|', $compound_id);
        $raw_type = $parts[0] ?? '';
        $id = intval($parts[1] ?? 0);
        
        // 防禦點 B：字元正規化 (Normalization) 
        // f(x) = tolower(x) 解決 'Admin' 與 'admin' 的不對稱
        $type = strtolower($raw_type); 

        if (!in_array($type, ['admin', 'staff']) || $id <= 0) {
            throw new Exception("Security Exception: Illegal Entity Reference detected [{$compound_id}].");
        }

        // 💡 3. 分支路由與實體處置 (Branch Routing & Entity Disposal)
        if ($type === 'admin') {
            // 檢查關聯依賴
            if (!isset($stmts['chk_admin'])) {
                $stmts['chk_admin'] = $conn->prepare("SELECT COUNT(*) FROM booking WHERE aid = ?");
            }
            $stmts['chk_admin']->bind_param("i", $id);
            $stmts['chk_admin']->execute();
            $history_count = $stmts['chk_admin']->get_result()->fetch_row()[0];
            
            if ($history_count > 0) {
                // 🔒 軟停權
                if (!isset($stmts['upd_admin'])) $stmts['upd_admin'] = $conn->prepare("UPDATE admin SET status = 'inactive' WHERE aid = ?");
                $stmts['upd_admin']->bind_param("i", $id);
                $stmts['upd_admin']->execute();
            } else {
                // 🗑️ 物理刪除
                if (!isset($stmts['del_admin'])) $stmts['del_admin'] = $conn->prepare("DELETE FROM admin WHERE aid = ?");
                $stmts['del_admin']->bind_param("i", $id);
                $stmts['del_admin']->execute();
            }
        } 
        elseif ($type === 'staff') {
            // 檢查關聯依賴
            if (!isset($stmts['chk_staff'])) {
                $stmts['chk_staff'] = $conn->prepare("SELECT COUNT(*) FROM inspection WHERE sid = ?");
            }
            $stmts['chk_staff']->bind_param("i", $id);
            $stmts['chk_staff']->execute();
            $history_count = $stmts['chk_staff']->get_result()->fetch_row()[0];
            
            if ($history_count > 0) {
                // 🔒 軟停權
                if (!isset($stmts['upd_staff'])) $stmts['upd_staff'] = $conn->prepare("UPDATE staff SET status = 'inactive' WHERE sid = ?");
                $stmts['upd_staff']->bind_param("i", $id);
                $stmts['upd_staff']->execute();
            } else {
                // 🗑️ 物理刪除
                if (!isset($stmts['del_staff'])) $stmts['del_staff'] = $conn->prepare("DELETE FROM staff WHERE sid = ?");
                $stmts['del_staff']->bind_param("i", $id);
                $stmts['del_staff']->execute();
            }
        }
        $success_count++;
    }

    // 關閉所有已啟動的預處理語句 (Garbage Collection)
    foreach ($stmts as $stmt) {
        $stmt->close();
    }

    $conn->commit();
    $_SESSION['toast'] = ['type' => 'success', 'msg' => "Operation Executed: {$success_count} entity trace(s) successfully processed."];

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['toast'] = ['type' => 'error', 'msg' => "Transaction Aborted: " . $e->getMessage()];
}

header("Location: ../admin/staff_directory.php");
exit;