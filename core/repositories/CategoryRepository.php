<?php
// File: core/repositories/CategoryRepository.php

namespace Core\Repositories;

use mysqli;
use Core\Components\FilterBuilder;

class CategoryRepository {
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
 * Read: get one category status
     */
    public function getCategoryById(int $vcid): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM vcategory WHERE vcid = ?");
        $stmt->bind_param("i", $vcid);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result ?: null;
    }

    /**
 * Read: get all categories (for DataGrid)
     */
    public function getAllCategories(FilterBuilder $filterBuilder) {
        $sql = "SELECT vcid, category, description FROM vcategory WHERE 1=1";
        $sql .= $filterBuilder->buildSqlWhere($this->conn);
        $sql .= " ORDER BY vcid DESC";
        return $this->conn->query($sql);
    }

    /**
 * Write: create new category (do not pass vcid; database AUTO_INCREMENT handles it)
     */
    public function createCategory(string $category, string $description): bool {
        $stmt = $this->conn->prepare("INSERT INTO vcategory (category, description) VALUES (?, ?)");
        if (!$stmt) return false;
        $stmt->bind_param("ss", $category, $description);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
 * Write: update category
     */
    public function updateCategory(int $vcid, string $category, string $description): bool {
        $stmt = $this->conn->prepare("UPDATE vcategory SET category = ?, description = ? WHERE vcid = ?");
        if (!$stmt) return false;
        $stmt->bind_param("ssi", $category, $description, $vcid);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
 * Write: delete category (supports batch delete array)
     */
    /**
 * Write: delete category ( Venue relatedProtectionRule)
     * @throws \Exception
     */
    public function deleteCategories(array $vcids): bool {
        if (empty($vcids)) return false;
        $ids = implode(',', array_map('intval', $vcids));
        
 // CoreProtection: inRunHard delete, (Intersection Validation)
 // Check whether any venue exists (v) with vcid in the delete list (C)
 // If related venues exist, trigger protection
        $check_sql = "SELECT COUNT(*) as dependency_count FROM venue WHERE vcid IN ($ids)";
        $result = $this->conn->query($check_sql);
        $row = $result->fetch_assoc();
        
        if ($row && $row['dependency_count'] > 0) {
 // Throw a clear error and stop delete
            throw new \Exception("Failed to Delete: One or more selected caegories are currently tied to existing venues. Please reassign those venues before deleting.");
        }

 // ifnorelated, thensafeRunHard delete
        $sql = "DELETE FROM vcategory WHERE vcid IN ($ids)";
        return $this->conn->query($sql);
    }
}
?>