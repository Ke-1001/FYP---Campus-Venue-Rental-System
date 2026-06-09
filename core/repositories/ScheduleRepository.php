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
     * 獲取學期字典映射 (Semester Dictionary Matrix)
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
     * 獲取場地字典映射 (Venue Dictionary Matrix)
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
     * 核心排程獲取：動態注入 FilterBuilder 狀態
     */
    public function getAllWithFilters(FilterBuilder $filterBuilder) {
        $sql = "SELECT s.*, v.vname, sem.sem_name 
                FROM academic_schedule s 
                JOIN venue v ON s.vid = v.vid 
                JOIN semester_config sem ON s.sem_id = sem.sem_id
                WHERE 1=1";

        // ∴ 無縫對接 FilterBuilder 的 WHERE 拓撲
        $sql .= $filterBuilder->buildSqlWhere($this->conn);

        // 嚴格的時間軸與星期排序
        $sql .= " ORDER BY sem.start_date DESC, FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), s.start_time ASC";
        
        return $this->conn->query($sql);
    }
}
?>