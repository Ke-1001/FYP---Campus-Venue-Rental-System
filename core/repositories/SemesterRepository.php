<?php

// This section provides database access for SemesterRepository data.
namespace Core\Repositories;

use mysqli;
use Core\Components\FilterBuilder;

class SemesterRepository
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getSemesterById(int $sem_id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM semester_config WHERE sem_id = ?");
        $stmt->bind_param("i", $sem_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    public function getAllWithFilters(FilterBuilder $filterBuilder)
    {
        $sql = "SELECT * FROM semester_config WHERE 1=1";

        $sql .= $filterBuilder->buildSqlWhere($this->conn);

        $sql .= " ORDER BY start_date DESC";

        return $this->conn->query($sql);
    }
}
?>
