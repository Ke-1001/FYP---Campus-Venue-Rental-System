<?php

// This section provides database access for InspectionRepository data.
namespace Core\Repositories;

use mysqli;
use Core\Components\FilterBuilder;

class InspectionRepository
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getPendingAssignments(FilterBuilder $filterBuilder)
    {
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

    public function getPendingInspections(FilterBuilder $filterBuilder)
    {
        $sql = "SELECT
                    i.ins_id, i.bid, i.ins_status,
                    b.date_booked, b.time_start, b.time_end, b.status AS booking_status,
                    u.uid AS student_id, u.username AS student_name,
                    v.vname AS venue_name, vc.category AS venue_category,
                    s.staff_name AS inspector_name
                FROM inspection i
                JOIN booking b ON i.bid = b.bid
                JOIN user u ON b.uid = u.uid
                JOIN venue v ON b.vid = v.vid
                JOIN vcategory vc ON v.vcid = vc.vcid
                JOIN staff s ON i.sid = s.sid
                WHERE i.ins_status = 'pending'";

        $sql .= $filterBuilder->buildSqlWhere($this->conn);

        $sql .= " ORDER BY b.date_booked ASC, b.time_start ASC";

        return $this->conn->query($sql);
    }

    public function getInspectionHistory(FilterBuilder $filterBuilder)
    {
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
 -- Fix: include 'overdue' in inspection history
                WHERE i.ins_status IN ('passed', 'failed', 'overdue')";

        $sql .= $filterBuilder->buildSqlWhere($this->conn);

        $sql .= " ORDER BY COALESCE(i.inspected_at, CONCAT(b.date_booked, ' ', b.time_end)) DESC, i.ins_id DESC";

        return $this->conn->query($sql);
    }

    public function getPendingInspectionDetailById(int $bid): ?array
    {
        $sql = "SELECT
                    b.*, u.username, v.vname, v.deposit, vc.category AS venue_category,
                    i.ins_id, s.staff_name AS inspector_name
                FROM inspection i
                JOIN booking b ON i.bid = b.bid
                JOIN user u ON b.uid = u.uid
                JOIN venue v ON b.vid = v.vid
                JOIN vcategory vc ON v.vcid = vc.vcid
                JOIN staff s ON i.sid = s.sid
                WHERE b.bid = ? AND i.ins_status = 'pending'";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $bid);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }
}
?>
