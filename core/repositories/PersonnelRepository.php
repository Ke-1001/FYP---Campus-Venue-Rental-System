<?php

// This section provides database access for PersonnelRepository data.
namespace Core\Repositories;

use mysqli;
use Core\Components\FilterBuilder;

class PersonnelRepository
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getUnifiedDirectory(FilterBuilder $filterBuilder)
    {
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

        $base_sql .= $filterBuilder->buildSqlWhere($this->conn);

        $base_sql .= " ORDER BY entity_type ASC, access_level ASC, name ASC";

        return $this->conn->query($base_sql);
    }

    public function getInspectors(): array
    {
        $sql = "SELECT sid, staff_name FROM staff WHERE position = 'inspector' ORDER BY staff_name ASC";
        $result = $this->conn->query($sql);

        $inspectors = [];
        if ($result && $result->num_rows > 0)
        {
            while ($row = $result->fetch_assoc())
            {
                $inspectors[] = $row;
            }
        }
        return $inspectors;
    }
}
?>
