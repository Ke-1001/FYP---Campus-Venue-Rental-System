<?php
// This section provides database access for MetricsRepository data.
namespace Core\Repositories;
use mysqli;
class MetricsRepository
{
    private mysqli $conn;
    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }
    public function getBookingKPIs(): array
    {
        return [
            'pending_requests' => $this->queryCount("SELECT COUNT(*) FROM booking WHERE status = 'pending' AND payment_status = 'paid'"),
            'ongoing_bookings' => $this->queryCount("SELECT COUNT(*) FROM booking WHERE status = 'approved'"),
            'completed_bookings' => $this->queryCount("SELECT COUNT(*) FROM booking WHERE status = 'completed'"),
            'pending_assignments' => $this->queryCount("SELECT COUNT(*) FROM booking b LEFT JOIN inspection i ON b.bid = i.bid WHERE b.status IN ('approved', 'completed') AND b.payment_status = 'paid' AND i.ins_id IS NULL"),
            'damage_reports' => $this->queryCount("SELECT COUNT(*) FROM damage_report WHERE report_status = 'submitted'")
        ];
    }
    public function getInspectionKPIs(): array
    {
        return [
            'pending_inspections' => $this->queryCount("SELECT COUNT(*) FROM inspection i JOIN booking b ON i.bid = b.bid WHERE i.ins_status = 'pending' AND b.status = 'completed'"),
            'tracked_inspections' => $this->queryCount("SELECT COUNT(*) FROM inspection WHERE ins_status IN ('passed', 'failed')")
        ];
    }
    public function getVenueKPIs(): array
    {
        return [
            'total_venues' => $this->queryCount("SELECT COUNT(*) FROM venue"),
            'available_venues' => $this->queryCount("SELECT COUNT(*) FROM venue WHERE status = 'available'"),
            'total_categories' => $this->queryCount("SELECT COUNT(*) FROM vcategory")
        ];
    }
    public function getAcademicKPIs(): array
    {
        return [
            'active_semesters' => $this->queryCount("SELECT COUNT(*) FROM semester_config WHERE is_active = 1"),
            'total_schedules' => $this->queryCount("SELECT COUNT(*) FROM academic_schedule")
        ];
    }
    public function getPersonnelKPIs(): array
    {
        return [
            'combined_personnel' => $this->queryCount("SELECT COUNT(*) FROM (SELECT aid FROM admin UNION ALL SELECT sid FROM staff) AS combined"),
            'total_students' => $this->queryCount("SELECT COUNT(*) FROM user")
        ];
    }
    public function getDashboardKPIs(): array
    {
        return array_merge(
            $this->getBookingKPIs(),
            $this->getInspectionKPIs(),
            $this->getVenueKPIs(),
            $this->getAcademicKPIs(),
            $this->getPersonnelKPIs()
        );
    }
    private function queryCount(string $sql): int
    {
        $result = $this->conn->query($sql);
        return $result ? (int)($result->fetch_row()[0] ?? 0) : 0;
    }
}
?>
