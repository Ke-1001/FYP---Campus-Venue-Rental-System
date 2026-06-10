<?php
// File: core/repositories/SemesterRepository.php

namespace Core\Repositories;

use mysqli;
use Core\Components\FilterBuilder; // ∴ 引入過濾器核心

class SemesterRepository {
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * [業務場景 A: add_semester.php 使用]
     * 基於主鍵精確提取單一學期節點矩陣 (Single Node Extraction)
     */
    public function getSemesterById(int $sem_id): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM semester_config WHERE sem_id = ?");
        $stmt->bind_param("i", $sem_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    /**
     * [業務場景 B: semester_management.php 使用]
     * 獲取全量學期配置，並完美對接 FilterBuilder 的動態拓撲
     */
    public function getAllWithFilters(FilterBuilder $filterBuilder) {
        $sql = "SELECT * FROM semester_config WHERE 1=1";
        
        // ∴ 將手動拼接改為依賴注入，自動編譯 WHERE 條件
        $sql .= $filterBuilder->buildSqlWhere($this->conn);
        
        // 時間軸遞減排序
        $sql .= " ORDER BY start_date DESC";
        
        return $this->conn->query($sql);
    }
}
?>