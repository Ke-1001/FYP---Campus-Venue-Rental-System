<?php
// File: core/repositories/ScheduleRepository.php

namespace Core\Repositories;

use mysqli;
use Core\Components\FilterBuilder;

class ScheduleRepository {
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
 * Get semester options (Semester Dictionary Matrix)
     */
    public function getSemesterOptions(): array {
        $sql = "SELECT sem_id, sem_name FROM semester_config ORDER BY start_date DESC";
        $result = $this->conn->query($sql);
        
        $options = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $options[$row['sem_id']] = $row['sem_name'];
            }
        }
        return $options;
    }

    /**
 * Get venue options (Venue Dictionary Matrix)
     */
    public function getVenueOptions(): array {
        $sql = "SELECT vid, vname FROM venue ORDER BY vid ASC";
        $result = $this->conn->query($sql);
        
        $options = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $options[$row['vid']] = '[' . $row['vid'] . '] ' . $row['vname'];
            }
        }
        return $options;
    }

    /**
 * Main schedule fetch: add FilterBuilder conditions
     */
    public function getAllWithFilters(FilterBuilder $filterBuilder) {
        $sql = "SELECT s.*, v.vname, sem.sem_name 
                FROM academic_schedule s 
                JOIN venue v ON s.vid = v.vid 
                JOIN semester_config sem ON s.sem_id = sem.sem_id
                WHERE 1=1";

 // Connect to FilterBuilder WHERE logic
        $sql .= $filterBuilder->buildSqlWhere($this->conn);

 // Strict date and weekday sorting
        $sql .= " ORDER BY sem.start_date DESC, FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), s.start_time ASC";
        
        return $this->conn->query($sql);
    }

    /**
 * [Use case: add_exclusion.php use]
 * Based on primary keyexactGetentity (Single Entity Extraction)
     *
     * @param int $sch_id
     * @return array|null
     */
    public function getScheduleById(int $sch_id): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM academic_schedule WHERE sch_id = ?");
        $stmt->bind_param("i", $sch_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    /**
 * [Use case: add_exclusion.php use]
 * Getvenuematrix (return a 2D array for the view layer)
 * separate from getVenueOptions() for compatibility
     *
     * @return array
     */
    public function getVenuesForDropdown(): array {
        $sql = "SELECT vid, vname FROM venue ORDER BY vid ASC";
        $result = $this->conn->query($sql);
        $venues = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $venues[] = $row;
            }
        }
        return $venues;
    }

    /**
 * [Use case: add_exclusion.php use]
 * Getsemestermatrix (must include is_active for UI default selection)
 * separate from getSemesterOptions() for compatibility
     *
     * @return array
     */
    public function getSemestersForDropdown(): array {
        $sql = "SELECT sem_id, sem_name, is_active FROM semester_config ORDER BY start_date DESC";
        $result = $this->conn->query($sql);
        $semesters = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $semesters[] = $row;
            }
        }
        return $semesters;
    }
}
?>