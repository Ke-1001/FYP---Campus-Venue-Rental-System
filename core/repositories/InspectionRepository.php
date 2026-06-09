<?php
// File: core/repositories/InspectionRepository.php

namespace Core\Repositories;

use mysqli;
use Core\Components\FilterBuilder;

class InspectionRepository {
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * 獲取尚未指派檢驗員的預約清單 (Pending Inspection Assignments)
     * ∴ 透過 LEFT JOIN 排查 ins_id IS NULL 確保邏輯互斥性
     * 供 assign_inspector.php 使用
     */
    public function getPendingAssignments(FilterBuilder $filterBuilder) {
        $sql = "SELECT 
                    b.bid, b.date_booked, b.time_start, b.time_end, b.payment_status,
                    u.uid AS student_id, u.username AS student_name, u.phone_num, u.email,
                    v.vid AS venue_id, v.vname AS venue_name,
                    vc.category AS venue_category
                FROM booking b
                JOIN user u ON b.uid = u.uid
                JOIN venue v ON b.vid = v.vid
                JOIN vcategory vc ON v.vcid = vc.vcid
                LEFT JOIN inspection i ON b.bid = i.bid
                WHERE b.status IN ('approved', 'completed') 
                  AND b.payment_status = 'paid'
                  AND i.ins_id IS NULL ";

        $sql .= $filterBuilder->buildSqlWhere($this->conn);
        $sql .= " ORDER BY b.date_booked ASC, b.time_start ASC";
        
        return $this->conn->query($sql);
    }

    /**
     * 獲取尚待執行的檢驗任務矩陣 (Pending Inspections Queue)
     * ∴ 嚴格綁定 ins_status = 'pending'
     * 供 pending_inspections.php 使用
     */
    /**
     * 獲取檢驗歷史紀錄矩陣 (Inspection History Log)
     * ∴ 嚴格過濾狀態為 passed 或 failed 的已完成紀錄
     */
    public function getInspectionHistory(FilterBuilder $filterBuilder) {
        $sql = "SELECT 
                    i.ins_id, i.bid, i.ins_status, i.damage_desc, i.penalty, i.inspected_at,
                    b.date_booked,
                    u.uid AS student_id, u.username AS student_name,
                    v.vname AS venue_name, vc.category AS venue_category,
                    s.staff_name AS inspector_name
                FROM inspection i
                JOIN booking b ON i.bid = b.bid
                JOIN user u ON b.uid = u.uid
                JOIN venue v ON b.vid = v.vid
                JOIN vcategory vc ON v.vcid = vc.vcid
                JOIN staff s ON i.sid = s.sid
                WHERE i.ins_status IN ('passed', 'failed')";

        // ∴ 動態注入過濾器拓撲
        $sql .= $filterBuilder->buildSqlWhere($this->conn);

        // 依照檢驗時間遞減排序 (最新紀錄優先)
        $sql .= " ORDER BY i.inspected_at DESC, i.ins_id DESC";
        
        return $this->conn->query($sql);
    }
}
?>