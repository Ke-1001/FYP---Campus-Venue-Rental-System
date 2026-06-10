<?php
// File: core/repositories/PersonnelRepository.php

namespace Core\Repositories;

use mysqli;
use Core\Components\FilterBuilder;

class PersonnelRepository {
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * 獲取統一身分目錄矩陣 (Unified Identity Directory)
     * ∴ 利用 UNION ALL 垂直合併 Admin 與 Staff 實體空間
     */
    public function getUnifiedDirectory(FilterBuilder $filterBuilder) {
        $base_sql = "
            SELECT * FROM (
                SELECT 
                    'Admin' AS entity_type,
                    aid AS entity_id,
                    admin_name AS name,
                    email,
                    phone_num,
                    role AS access_level,
                    created_at,
                    status
                FROM admin
                
                UNION ALL
                
                SELECT 
                    'Staff' AS entity_type,
                    sid AS entity_id,
                    staff_name AS name,
                    email,
                    phone_num,
                    position AS access_level,
                    created_at,
                    status
                FROM staff
            ) AS unified_directory
            WHERE 1=1
        ";

        // ∴ 無縫對接 FilterBuilder 的 WHERE 拓撲
        $base_sql .= $filterBuilder->buildSqlWhere($this->conn);

        // 依照實體類型、權限與名稱排序
        $base_sql .= " ORDER BY entity_type ASC, access_level ASC, name ASC";
        
        return $this->conn->query($base_sql);
    }

    /**
     * [業務場景: assign_inspector_detail.php 使用]
     * 專用數據提取：獲取職位為 inspector 的工作人員子集合
     * ∴ 繞過 Unified Directory，直接對 staff 表進行輕量化提取
     *
     * @return array 包含 sid 與 staff_name 的關聯陣列
     */
    public function getInspectors(): array {
        $sql = "SELECT sid, staff_name FROM staff WHERE position = 'inspector' ORDER BY staff_name ASC";
        $result = $this->conn->query($sql);
        
        $inspectors = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $inspectors[] = $row;
            }
        }
        return $inspectors;
    }
}
?>