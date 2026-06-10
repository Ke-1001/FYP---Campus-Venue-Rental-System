<?php
// File: core/repositories/BookingRepository.php

namespace Core\Repositories;

use mysqli;
use Core\Components\FilterBuilder;

class BookingRepository {
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * ==========================================
     * [業務場景 A: track_bookings.php 使用]
     * 函數: getAllWithFilters
     * 邏輯: 獲取全量預約歷史紀錄。
     * 約束: 絕對不可加入 SLA 或狀態過濾，全權交由 FilterBuilder 處理。
     * ==========================================
     */
    public function getAllWithFilters(FilterBuilder $filterBuilder) {
        $sql = "SELECT 
                    b.bid, b.date_booked, b.time_start, b.time_end, b.status, 
                    b.payment_status, b.payment_due_at, b.cancelled_at, b.cancel_reason, b.created_at,
                    u.uid AS student_id, u.username, u.email,
                    v.vid AS venue_id, v.vname, v.deposit,
                    vc.category AS venue_category
                FROM booking b
                JOIN user u ON b.uid = u.uid
                JOIN venue v ON b.vid = v.vid
                JOIN vcategory vc ON v.vcid = vc.vcid
                WHERE 1=1";

        // 拼接過濾器拓撲與排序規則
        $sql .= $filterBuilder->buildSqlWhere($this->conn);
        $sql .= " ORDER BY b.created_at DESC";
        
        $result = $this->conn->query($sql);

        // ∴ [探針 A：攔截執行期崩潰] 檢測 H_1 是否成立
        if (!$result) {
            die("<div style='background:#fee2e2; padding:20px; border:1px solid #ef4444; color:#991b1b; margin:20px; border-radius:8px; font-family:monospace;'>
                <b>[Diagnostic Probe A] SQL Execution Fault!</b><br><br>
                <b>MySQL Error:</b> " . $this->conn->error . "<br><br>
                <b>Generated Matrix:</b> " . htmlspecialchars($sql) . "
                </div>");
        }

        // ∴ [探針 B：攔截邏輯死鎖] 檢測 H_2 是否成立
        if ($result->num_rows === 0) {
            echo "<div style='background:#fef3c7; padding:20px; border:1px solid #f59e0b; color:#92400e; margin:20px; border-radius:8px; font-family:monospace;'>
                <b>[Diagnostic Probe B] Zero Records Returned (Query Successful)</b><br><br>
                <b>Generated Matrix:</b> " . htmlspecialchars($sql) . "
                </div>";
        }

        return $result;
    }

    /**
     * ==========================================
     * [業務場景 B: pending_requests.php 使用]
     * 函數: getPendingRequests
     * 邏輯: 獲取尚待審批的預約請求矩陣。
     * 約束: 嚴格過濾 pending, paid, 並錨定 time_end 確保 SLA。
     * ==========================================
     */
    public function getPendingRequests(FilterBuilder $filterBuilder) {
        $sql = "SELECT 
                    b.bid, b.date_booked, b.time_start, b.time_end, b.purpose, b.created_at,
                    u.uid AS student_id, u.username AS student_name, u.phone_num, u.email,
                    v.vname AS venue_name, v.deposit,
                    vc.category AS venue_category
                FROM booking b
                JOIN user u ON b.uid = u.uid
                JOIN venue v ON b.vid = v.vid
                JOIN vcategory vc ON v.vcid = vc.vcid 
                WHERE b.status = 'pending' 
                  AND b.payment_status = 'paid'
                  -- ∴ 核心防禦：錨定 terminal boundary (time_end)
                  AND (
                      b.date_booked > CURDATE() 
                      OR 
                      (b.date_booked = CURDATE() AND b.time_end > CURTIME())
                  )";

        // ∴ 動態注入過濾器拓撲
        $sql .= $filterBuilder->buildSqlWhere($this->conn);

        // 依照請求建立時間遞增排序 (FIFO 排程邏輯)
        $sql .= " ORDER BY b.created_at ASC";
        
        return $this->conn->query($sql);
    }

    /**
     * ==========================================
     * [業務場景 C: assign_inspector_detail.php 使用]
     * 函數: getDetailedBookingById
     * 邏輯: 基於主鍵 (Booking ID) 精確提取預約明細矩陣。
     * ==========================================
     * @param int $bid
     * @return array|null
     */
    public function getDetailedBookingById(int $bid): ?array {
        $sql = "SELECT 
                    b.*, 
                    u.username, u.email, 
                    v.vname, v.deposit 
                FROM booking b 
                JOIN user u ON b.uid = u.uid 
                JOIN venue v ON b.vid = v.vid 
                WHERE b.bid = ?";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $bid);
        $stmt->execute();
        
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }
}
?>