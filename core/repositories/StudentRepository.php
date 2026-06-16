<?php
// This section provides database access for StudentRepository data.
namespace Core\Repositories;

use mysqli;
use Core\Components\FilterBuilder;

class StudentRepository
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }


    public function getAllStudents(FilterBuilder $filterBuilder, string $sortOption)
    {

        $sql = "SELECT uid, username, email, phone_num, account_status AS status, created_at FROM user WHERE 1=1";

        $sql .= $filterBuilder->buildSqlWhere($this->conn);

        switch ($sortOption)
        {
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


    public function getStudentById(string $uid): ?array
    {

        $sql = "SELECT uid, username, email, phone_num, account_status AS status, created_at FROM user WHERE uid = ? LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt)
        {
            error_log("Statement Preparation Fault in StudentRepository: " . $this->conn->error);
            return null;
        }

        $stmt->bind_param("s", $uid);
        $stmt->execute();

        $result = $stmt->get_result();
        $entity = $result->fetch_assoc();

        $stmt->close();

        return $entity ?: null;
    }
}
?>
