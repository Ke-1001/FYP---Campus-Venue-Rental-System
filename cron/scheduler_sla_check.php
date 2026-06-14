<?php
date_default_timezone_set('Asia/Kuala_Lumpur');
// File: cron/scheduler_sla_check.php

// ∴ 拓撲轉移：防禦性 Loopback 與加密權杖雙重認證
$is_localhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
$expected_token = 'a7b8c9d0-f1e2-4g5h-8i9j-klmnopqrstuv'; 
$provided_token = $_GET['token'] ?? '';

if (!$is_localhost || $provided_token !== $expected_token) {
    http_response_code(403);
    die("Security Exception: Unauthorized invocation vector.");
}

// 引入資料庫核心配置
require_once __DIR__ . '/../config/db.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting SLA Master Execution Daemon...\n";

// 啟動全域事務矩陣以維持原子性 ≡ {ACID}
$conn->begin_transaction();

try {
    /*
    |--------------------------------------------------------------------------
    | Logic Pipeline A: SLA 過期未處理節點硬性收斂
    |--------------------------------------------------------------------------
    | 轉換條件：狀態為 pending 且已付款，但目前系統時間已超越預約起始時間
    */
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
    } else {
        echo "Zero state variances detected in Logic Pipeline A.\n";
    }


/*
    |--------------------------------------------------------------------------
    | Logic Pipeline B: 時序感知自動指派引擎 (臨界前 15 分鐘觸發)
    |--------------------------------------------------------------------------
    | 轉換條件：狀態為 approved、未配置檢驗員，且距離結束時間 \le 15 分鐘的預約節點
    | 排除條件：排程衝突緩衝期 Δt = 30 分鐘
    */
    echo "Initializing JIT (Just-In-Time) Allocation Matrix...\n";
    
    $buffer_minutes = 30; // 檢驗員前後任務防撞緩衝時間
    
    // 💡 修正點：將 HOUR 改為 MINUTE，且變更閾值為 <= 15
    $sql_target_nodes = "
        SELECT b.bid, b.date_booked, b.time_end 
        FROM booking b
        LEFT JOIN inspection i ON b.bid = i.bid
        WHERE b.status = 'approved' 
          AND i.ins_id IS NULL 
          AND TIMESTAMPDIFF(MINUTE, NOW(), CONCAT(b.date_booked, ' ', b.time_end)) <= 15
    ";
    
    $pending_assignments = $conn->query($sql_target_nodes);
    $auto_assigned_count = 0;

    if ($pending_assignments && $pending_assignments->num_rows > 0) {
        
        $stmt_assign = $conn->prepare("INSERT INTO inspection (bid, sid, ins_status) VALUES (?, ?, 'pending')");
        
        while ($booking = $pending_assignments->fetch_assoc()) {
            $target_bid = $booking['bid'];
            $target_date = $booking['date_booked'];
            $target_time = $booking['time_end'];

            // 💡 核心動態限制演算法保持不變，持續鎖定當日排程不衝突且負載最輕的檢驗員
            $sql_find_optimal_staff = "
                SELECT s.sid
                FROM staff s
                WHERE s.position = 'inspector' AND s.status = 'active'
                  AND s.sid NOT IN (
                      SELECT i2.sid
                      FROM inspection i2
                      JOIN booking b2 ON i2.bid = b2.bid
                      WHERE i2.ins_status = 'pending'
                        AND b2.date_booked = ?
                        AND ABS(TIMESTAMPDIFF(MINUTE, b2.time_end, ?)) < ?
                  )
                ORDER BY (
                    SELECT COUNT(*) 
                    FROM inspection i3 
                    JOIN booking b3 ON i3.bid = b3.bid 
                    WHERE i3.sid = s.sid 
                      AND b3.date_booked = ?
                ) ASC
                LIMIT 1
            ";

            $stmt_staff = $conn->prepare($sql_find_optimal_staff);
            $stmt_staff->bind_param("ssis", $target_date, $target_time, $buffer_minutes, $target_date);
            $stmt_staff->execute();
            $staff_result = $stmt_staff->get_result();

            if ($staff_row = $staff_result->fetch_assoc()) {
                $optimal_sid = $staff_row['sid'];
                
                $stmt_assign->bind_param("is", $target_bid, $optimal_sid);
                $stmt_assign->execute();
                
                $auto_assigned_count++;
                echo "  ⇒ JIT Mapped Booking [{$target_bid}] to Inspector [{$optimal_sid}].\n";
            } else {
                echo "  ⚠ Capacity Exhausted: Schedule collision detected for Booking [{$target_bid}] at {$target_date} {$target_time}.\n";
            }
            $stmt_staff->close();
        }
        $stmt_assign->close();
    }

    echo "Successfully resolved {$auto_assigned_count} JIT assignment constraint(s).\n";

    // ∴ 雙管線執行無誤，雙向提交資料庫變更
    $conn->commit();
    echo "[" . date('Y-m-d H:i:s') . "] Execution complete. Transaction committed.\n\n";

} catch (Exception $e) {
    // ∴ 任何管線拋出異常，立即執行全局回滾以防數據碎裂
    $conn->rollback();
    echo "CRITICAL: Transaction Aborted due to exception: " . $e->getMessage() . "\n";
}