<?php
// File: core/repositories/SemesterRepository.php

namespace Core\Repositories;

use mysqli;
use Core\Components\FilterBuilder; // Load filter core

class SemesterRepository {
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
 * [Use case A: add_semester.php use]
 * Based on primary keyexactGetsemesternodematrix (Single Node Extraction)
     */
    public function getSemesterById(int $sem_id): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM semester_config WHERE sem_id = ?");
        $stmt->bind_param("i", $sem_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    /**
 * [Use case B: semester_management.php use]
 * Get all semesters and connect to FilterBuilder
     */
    public function getAllWithFilters(FilterBuilder $filterBuilder) {
        $sql = "SELECT * FROM semester_config WHERE 1=1";
        
 // Use dependency injection to build WHERE automatically
        $sql .= $filterBuilder->buildSqlWhere($this->conn);
        
 // Sort by date descending
        $sql .= " ORDER BY start_date DESC";
        
        return $this->conn->query($sql);
    }
}
?>