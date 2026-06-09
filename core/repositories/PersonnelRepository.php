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
}
?>