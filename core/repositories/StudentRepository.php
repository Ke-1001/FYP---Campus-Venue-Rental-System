<?php
// File: core/repositories/StudentRepository.php

namespace Core\Repositories;

use mysqli;
use Core\Components\FilterBuilder;

class StudentRepository {
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * 獲取學生目錄矩陣 (Student Directory Matrix)
     * ∴ 處理多維度聚合搜尋與動態排序
     */
    public function getAllStudents(FilterBuilder $filterBuilder, string $sortOption) {
        $sql = "SELECT uid, username, email, phone_num, created_at FROM user WHERE 1=1";

        // ∴ 無縫對接 FilterBuilder 的 WHERE 拓撲 (支援 CONCAT 多欄位檢索)
        $sql .= $filterBuilder->buildSqlWhere($this->conn);

        // ∴ 嚴格的排序推演邏輯 (Sorting Delegation)
        switch ($sortOption) {
            case 'oldest':
                $sql .= " ORDER BY created_at ASC";
                break;
            case 'name_asc':
                $sql .= " ORDER BY username ASC";
                break;
            case 'newest':
            default:
                $sql .= " ORDER BY created_at DESC";
                break;
        }
        
        return $this->conn->query($sql);
    }
}
?>