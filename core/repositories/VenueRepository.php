<?php
// File: core/repositories/VenueRepository.php

namespace Core\Repositories;

use mysqli;
use Core\Components\FilterBuilder;

class VenueRepository {
    private mysqli $conn;

    /**
 * Dependency injection (Dependency Injection)
     */
    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
 * Get all venue category options
 * ≡ Remove category SQL from controller
     */
    public function getCategoryOptions(): array {
        $sql = "SELECT DISTINCT UPPER(TRIM(category)) AS category_name FROM vcategory ORDER BY category_name ASC";
        $result = $this->conn->query($sql);
        
        $options = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $val = $row['category_name'];
 $options[$val] = $val; // Key-value mapping for select input
            }
        }
        return $options;
    }

    /**
 * Main data fetch: use FilterBuilder conditions
     */
    public function getAllWithFilters(FilterBuilder $filterBuilder) {
 // 1. Basic relation mapping (Base Relational Topology)
        $sql = "SELECT v.*, vc.category AS venue_category 
                FROM venue v 
                JOIN vcategory vc ON v.vcid = vc.vcid 
                WHERE 1=1";

 // 2. Add status WHERE condition
        $sql .= $filterBuilder->buildSqlWhere($this->conn);

 // 3. Sort rule
        $sql .= " ORDER BY v.vname ASC";

        return $this->conn->query($sql);
    }

    /**
 * Get one venue record (Fetch Entity State for Form Binding)
 * Use prepared statement to prevent SQL injection and return record array
     */
    public function getVenueById(string $vid): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM venue WHERE vid = ?");
        $stmt->bind_param("s", $vid);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result ?: null;
    }

    /**
 * Get category options for form (Form Category Dictionary)
 * for Register/Edit select mapping (BindentityForeign key vcid)
     */
    public function getCategoryDictionary(): array {
        $sql = "SELECT vcid, category FROM vcategory ORDER BY category ASC";
        $result = $this->conn->query($sql);
        
        $options = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
 $options[] = $row; // output format is ['vcid' => X, 'category' => Y]
            }
        }
        return $options;
    }
}
?>