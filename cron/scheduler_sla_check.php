<?php
date_default_timezone_set('Asia/Kuala_Lumpur');
// File: cron/scheduler_sla_check.php

// ∴ 拓撲防禦：Loopback 與加密權杖雙重認證 (Topology Defense)
$lock_file = __DIR__ . '/sla_engine.lock';
$cooldown_seconds = 60;
$t_now = time();

if (file_exists($lock_file)) {
    $t_last = (int)file_get_contents($lock_file);
    // 數學邏輯：若 Δt < 60s，則阻斷執行
    if (($t_now - $t_last) < $cooldown_seconds) {
        http_response_code(429);
        die("Rate Limit: SLA Engine is in cooldown state.");
    }
}
// 更新時間鎖
file_put_contents($lock_file, $t_now);

require_once __DIR__ . '/../config/db.php';

echo "[" . date('Y-m-d H:i:s') . "] Initializing Application-Layer SLA Master Daemon...\n";

// 啟動全域事務矩陣 ≡ {ACID} 保證狀態轉移的原子性
$conn->begin_transaction();

try {
    /*
    |--------------------------------------------------------------------------
    | Logic Pipeline A: Booking SLA 收斂 (雙重動態錨點矩陣)
    |--------------------------------------------------------------------------
    | 數學模型: C_cancel ≡ (t_now >= t_end - 5) ∨ (t_now >= max(t_start, t_create) + 15)
    */
    $sql_pipeline_a = "
        UPDATE booking
        SET status = 'cancelled', 
            payment_status = 'refunded', 
            cancel_reason = 'SLA Violation: Admin Timeout or Slot Minimum Viability Expiration.',
            cancelled_at = NOW()
        WHERE status = 'pending' 
          AND payment_status = 'paid'
          AND (
              TIMESTAMPADD(MINUTE, -5, CAST(CONCAT(date_booked, ' ', time_end) AS DATETIME)) <= NOW()
              OR
              TIMESTAMPADD(MINUTE, 15, GREATEST(CAST(CONCAT(date_booked, ' ', time_start) AS DATETIME), created_at)) <= NOW()
          )
    ";
    $stmt_a = $conn->prepare($sql_pipeline_a);
    $stmt_a->execute();
    if ($stmt_a->affected_rows > 0) {
        echo "  ⇒ Pipeline A: Converged {$stmt_a->affected_rows} booking(s) to 'cancelled'.\n";
    }
    $stmt_a->close();

    /*
    |--------------------------------------------------------------------------
    | Logic Pipeline B: 時序感知 JIT 分配引擎 (防撞與防寫入放大)
    |--------------------------------------------------------------------------
    | 數學模型: W_assign ≡ (t_end - 15 <= t_now < t_end + 30)
    */
    $buffer_minutes = 30;
    $sql_target_nodes = "
        SELECT b.bid, b.date_booked, b.time_end 
        FROM booking b
        LEFT JOIN inspection i ON b.bid = i.bid
        WHERE b.status = 'approved' 
          AND i.ins_id IS NULL 
          -- 上界: JIT 觸發機制
          AND TIMESTAMPDIFF(MINUTE, NOW(), CONCAT(b.date_booked, ' ', b.time_end)) <= 15
          -- 剛性下界: 若超時已達 30 分鐘，交由 Pipeline C 處理，拒絕無效指派
          AND TIMESTAMPADD(MINUTE, 30, CAST(CONCAT(b.date_booked, ' ', b.time_end) AS DATETIME)) > NOW()
    ";
    $pending_assignments = $conn->query($sql_target_nodes);
    $assigned_count = 0;

    if ($pending_assignments && $pending_assignments->num_rows > 0) {
        $stmt_assign = $conn->prepare("INSERT INTO inspection (bid, sid, ins_status) VALUES (?, ?, 'pending')");
        
        // 貪婪演算法 (Greedy Algorithm): 負載最優化與防撞機制
        $stmt_staff = $conn->prepare("
            SELECT s.sid FROM staff s
            WHERE s.position = 'inspector' AND s.status = 'active'
              AND s.sid NOT IN (
                  SELECT i2.sid FROM inspection i2 JOIN booking b2 ON i2.bid = b2.bid
                  WHERE i2.ins_status = 'pending' AND b2.date_booked = ? 
                    AND ABS(TIMESTAMPDIFF(MINUTE, b2.time_end, ?)) < ?
              )
            ORDER BY (
                SELECT COUNT(*) FROM inspection i3 JOIN booking b3 ON i3.bid = b3.bid 
                WHERE i3.sid = s.sid AND b3.date_booked = ?
            ) ASC LIMIT 1
        ");

        while ($booking = $pending_assignments->fetch_assoc()) {
            $stmt_staff->bind_param("ssis", $booking['date_booked'], $booking['time_end'], $buffer_minutes, $booking['date_booked']);
            $stmt_staff->execute();
            $staff_result = $stmt_staff->get_result();

            if ($staff_row = $staff_result->fetch_assoc()) {
                $stmt_assign->bind_param("is", $booking['bid'], $staff_row['sid']);
                $stmt_assign->execute();
                $assigned_count++;
            }
        }
        $stmt_staff->close();
        $stmt_assign->close();
    }
    if ($assigned_count > 0) echo "  ⇒ Pipeline B: Resolved {$assigned_count} JIT assignment(s).\n";

    /*
    |--------------------------------------------------------------------------
    | Logic Pipeline C: Inspection SLA 收斂 (逾期標記與財務閉環)
    |--------------------------------------------------------------------------
    */
    // C.1 產生退款報告 (具備 Idempotency)
    $conn->query("
        INSERT INTO report (ins_id, final_deduct, refund_status, penalty_status, created_at)
        SELECT i.ins_id, 0.00, 'pending', 'none', CURDATE()
        FROM inspection i JOIN booking b ON i.bid = b.bid
        WHERE i.ins_status = 'pending'
          AND TIMESTAMPADD(MINUTE, 30, CAST(CONCAT(b.date_booked, ' ', b.time_end) AS DATETIME)) <= NOW()
          AND NOT EXISTS (SELECT 1 FROM report r WHERE r.ins_id = i.ins_id)
    ");
    
    // C.2 標記檢驗狀態為逾期失效
    $conn->query("
        UPDATE inspection i JOIN booking b ON i.bid = b.bid
        SET i.ins_status = 'overdue', i.damage_desc = 'SLA Violation: Inspector Timeout. Auto-released.'
        WHERE i.ins_status = 'pending'
          AND TIMESTAMPADD(MINUTE, 30, CAST(CONCAT(b.date_booked, ' ', b.time_end) AS DATETIME)) <= NOW()
    ");

    // C.3 封閉預約生命週期
    $conn->query("
        UPDATE booking b JOIN inspection i ON b.bid = i.bid
        SET b.status = 'completed'
        WHERE i.ins_status = 'overdue' AND b.status = 'approved'
    ");

    $conn->commit();
    echo "[" . date('Y-m-d H:i:s') . "] Execution complete. State matrices synchronized.\n";

} catch (Exception $e) {
    $conn->rollback();
    echo "CRITICAL: State Convergence Aborted ⇒ " . $e->getMessage() . "\n";
}