<?php
// File: cron/scheduler_sla_check.php

// ∴ 拓撲轉移：廢除 CLI 檢查，改用 127.0.0.1 (Loopback) 與加密權杖進行雙重認證
$is_localhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
// 定義靜態密碼權杖 (Cryptographic Token)
$expected_token = 'a7b8c9d0-f1e2-4g5h-8i9j-klmnopqrstuv'; 
$provided_token = $_GET['token'] ?? '';

if (!$is_localhost || $provided_token !== $expected_token) {
    http_response_code(403);
    die("Security Exception: Unauthorized invocation vector.");
}

// 引入資料庫核心配置
require_once __DIR__ . '/../config/db.php';

// ... (下方的資料庫 UPDATE 與 CRON 邏輯保持完全不變) ...

echo "[" . date('Y-m-d H:i:s') . "] Starting SLA State Convergence Engine...\n";

// 啟動事務矩陣
$conn->begin_transaction();

try {
    // 💡 1. 執行狀態收斂：將過期且未處理的預約硬性轉為 cancelled 狀態
    $sql_update = "
        UPDATE booking 
        SET status = 'cancelled', 
            cancel_reason = 'SLA Violation: Automated threshold expiration purge.' 
        WHERE status = 'pending' 
          AND payment_status = 'paid' 
          AND CONCAT(date_booked, ' ', time_start) <= NOW()";
          
    $stmt = $conn->prepare($sql_update);
    $stmt->execute();
    $affected_rows = $stmt->affected_rows;
    $stmt->close();

    if ($affected_rows > 0) {
        echo "Successfully converged {$affected_rows} expired pending record(s) to 'cancelled'.\n";
        
        // 💡 2. 異步資金歸還觸發節點 (Refund Trigger Node)
        // 這裡應放置調用你的退款 API 或標記退款矩陣的代碼
        // eg. $refundEngine->batchProcessRefundsForExpiredSlots();
    } else {
        echo "Zero state variances detected. System in equilibrium.\n";
    }

    $conn->commit();
    echo "[" . date('Y-m-d H:i:s') . "] Execution complete. Transaction committed.\n\n";

} catch (Exception $e) {
    $conn->rollback();
    echo "CRITICAL: Transaction Aborted due to exception: " . $e->getMessage() . "\n";
}