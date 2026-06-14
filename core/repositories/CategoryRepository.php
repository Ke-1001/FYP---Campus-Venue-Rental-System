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
     * 讀取：獲取單一類別實體狀態
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
     * 讀取：獲取所有類別實體 (供 DataGrid 使用)
     */
    public function getAllCategories(FilterBuilder $filterBuilder) {
        $sql = "SELECT vcid, category, description FROM vcategory WHERE 1=1";
        $sql .= $filterBuilder->buildSqlWhere($this->conn);
        $sql .= " ORDER BY vcid DESC";
        return $this->conn->query($sql);
    }

    /**
     * 寫入：新建類別節點 (∴ 不傳入 vcid，由資料庫 AUTO_INCREMENT 接管)
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
     * 寫入：更新類別節點
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
     * 寫入：刪除類別節點 (支援批次刪除陣列)
     */
    /**
     * 寫入：刪除類別節點 (包含 Venue 關聯性防禦約束)
     * @throws \Exception
     */
    public function deleteCategories(array $vcids): bool {
        if (empty($vcids)) return false;
        $ids = implode(',', array_map('intval', $vcids));
        
        // ∴ 核心防禦：在執行物理刪除前，驗證集合交集 (Intersection Validation)
        // 檢查是否存在任何 Venue (v) 其 vcid 屬於待刪除集合 (C)
        // 若 v ∩ C ≠ ∅，則觸發防禦機制
        $check_sql = "SELECT COUNT(*) as dependency_count FROM venue WHERE vcid IN ($ids)";
        $result = $this->conn->query($check_sql);
        $row = $result->fetch_assoc();
        
        if ($row && $row['dependency_count'] > 0) {
            // 拋出明確的異常，阻斷刪除程序
            throw new \Exception("Constraint Violation: One or more selected caegories are currently tied to existing venues. Please reassign those venues before deleting.");
        }

        // 若無關聯，則安全執行物理刪除
        $sql = "DELETE FROM vcategory WHERE vcid IN ($ids)";
        return $this->conn->query($sql);
    }
}
?>