<?php
// File: core/repositories/VenueRepository.php

namespace Core\Repositories;

use mysqli;
use Core\Components\FilterBuilder;

class VenueRepository {
    private mysqli $conn;

    /**
     * 依賴注入 (Dependency Injection)
     */
    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * 獲取所有場地分類的字典映射
     * ≡ 消除控制器中提取 Category 的 SQL
     */
    public function getCategoryOptions(): array {
        $sql = "SELECT DISTINCT UPPER(TRIM(category)) AS category_name FROM vcategory ORDER BY category_name ASC";
        $result = $this->conn->query($sql);
        
        $options = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $val = $row['category_name'];
                $options[$val] = $val; // Key-Value 映射，供 Select 使用
            }
        }
        return $options;
    }

    /**
     * 核心資料提取：結合 FilterBuilder 的動態條件
     */
    public function getAllWithFilters(FilterBuilder $filterBuilder) {
        // 1. 基礎關聯映射拓撲 (Base Relational Topology)
        $sql = "SELECT v.*, vc.category AS venue_category 
                FROM venue v 
                JOIN vcategory vc ON v.vcid = vc.vcid 
                WHERE 1=1";

        // 2. 注入狀態機之 WHERE 條件
        $sql .= $filterBuilder->buildSqlWhere($this->conn);

        // 3. 排序向量
        $sql .= " ORDER BY v.vname ASC";

        return $this->conn->query($sql);
    }
}
?>