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
 * [Use case A: track_bookings.php use]
 * Function: getAllWithFilters
 * Logic: Get all booking history records. 
 * Rule: Do not add SLA or status filtering; FilterBuilder handles it. 
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

 // Build filters and sort rules
        $sql .= $filterBuilder->buildSqlWhere($this->conn);
        $sql .= " ORDER BY b.created_at DESC";
        
        $result = $this->conn->query($sql);

 // [Check A: stop runtime crash] check whether H_1 is true
        if (!$result) {
            die("<div style='background:#fee2e2; padding:20px; border:1px solid #ef4444; color:#991b1b; margin:20px; border-radius:8px; font-family:monospace;'>
                <b>[Diagnostic Probe A] SQL Execution Fault!</b><br><br>
                <b>MySQL Error:</b> " . $this->conn->error . "<br><br>
                <b>Generated Matrix:</b> " . htmlspecialchars($sql) . "
                </div>");
        }

 // [ B: Logic] check whether H_2 is true
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
 * [Use case B: pending_requests.php use]
 * Function: getPendingRequests
 * Logic: Get pending booking requests. 
 * Rule: Filter pending and paid records and use time_end for SLA. 
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
 -- Core safety: use terminal boundary (time_end)
                  AND (
                      b.date_booked > CURDATE() 
                      OR 
                      (b.date_booked = CURDATE() AND b.time_end > CURTIME())
                  )";

 // Add filters dynamically
        $sql .= $filterBuilder->buildSqlWhere($this->conn);

 // Sort by request creation time ascending (FIFO Logic)
        $sql .= " ORDER BY b.created_at ASC";
        
        return $this->conn->query($sql);
    }

    /**
     * ==========================================
 * [Use case C: assign_inspector_detail.php use]
 * Function: getDetailedBookingById
 * Logic: Based on primary key (Booking ID) Get exact booking details. 
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