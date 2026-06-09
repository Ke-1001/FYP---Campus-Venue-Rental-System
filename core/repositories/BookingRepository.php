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
     * 核心排程獲取：動態注入 FilterBuilder 狀態
     */
    /**
     * 獲取尚待審批的預約請求矩陣 (Pending Requests Queue)
     * ∴ 嚴格過濾狀態為 pending 且已付款 (paid) 的紀錄
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
                WHERE b.status = 'pending' AND b.payment_status = 'paid'";

        // ∴ 動態注入過濾器拓撲
        $sql .= $filterBuilder->buildSqlWhere($this->conn);

        // 依照請求建立時間遞增排序 (FIFO 排程邏輯)
        $sql .= " ORDER BY b.created_at ASC";
        
        return $this->conn->query($sql);
    }
}
?>