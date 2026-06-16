<?php

// This section provides database access for CategoryRepository data.
namespace Core\Repositories;

use mysqli;
use Core\Components\FilterBuilder;

class CategoryRepository
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getCategoryById(int $vcid): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM vcategory WHERE vcid = ?");
        $stmt->bind_param("i", $vcid);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result ?: null;
    }

    public function getAllCategories(FilterBuilder $filterBuilder)
    {
        $sql = "SELECT vcid, category, description FROM vcategory WHERE 1=1";
        $sql .= $filterBuilder->buildSqlWhere($this->conn);
        $sql .= " ORDER BY vcid DESC";
        return $this->conn->query($sql);
    }

    public function createCategory(string $category, string $description): bool
    {
        $stmt = $this->conn->prepare("INSERT INTO vcategory (category, description) VALUES (?, ?)");
        if (!$stmt) return false;
        $stmt->bind_param("ss", $category, $description);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function updateCategory(int $vcid, string $category, string $description): bool
    {
        $stmt = $this->conn->prepare("UPDATE vcategory SET category = ?, description = ? WHERE vcid = ?");
        if (!$stmt) return false;
        $stmt->bind_param("ssi", $category, $description, $vcid);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function deleteCategories(array $vcids): bool
    {
        if (empty($vcids)) return false;
        $ids = implode(',', array_map('intval', $vcids));

        $check_sql = "SELECT COUNT(*) as dependency_count FROM venue WHERE vcid IN ($ids)";
        $result = $this->conn->query($check_sql);
        $row = $result->fetch_assoc();

        if ($row && $row['dependency_count'] > 0)
        {
            throw new \Exception("Failed to Delete: One or more selected caegories are currently tied to existing venues. Please reassign those venues before deleting.");
        }

        $sql = "DELETE FROM vcategory WHERE vcid IN ($ids)";
        return $this->conn->query($sql);
    }
}
?>
