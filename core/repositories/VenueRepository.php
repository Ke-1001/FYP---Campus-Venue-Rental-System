<?php

// This section provides database access for VenueRepository data.
namespace Core\Repositories;

use mysqli;
use Core\Components\FilterBuilder;

class VenueRepository
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getCategoryOptions(): array
    {
        $sql = "SELECT DISTINCT UPPER(TRIM(category)) AS category_name FROM vcategory ORDER BY category_name ASC";
        $result = $this->conn->query($sql);

        $options = [];
        if ($result && $result->num_rows > 0)
        {
            while ($row = $result->fetch_assoc())
            {
                $val = $row['category_name'];
 $options[$val] = $val;
            }
        }
        return $options;
    }

    public function getAllWithFilters(FilterBuilder $filterBuilder)
    {
        $sql = "SELECT v.*, vc.category AS venue_category
                FROM venue v
                JOIN vcategory vc ON v.vcid = vc.vcid
                WHERE 1=1";

        $sql .= $filterBuilder->buildSqlWhere($this->conn);

        $sql .= " ORDER BY v.vname ASC";

        return $this->conn->query($sql);
    }

    public function getVenueById(string $vid): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM venue WHERE vid = ?");
        $stmt->bind_param("s", $vid);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result ?: null;
    }

    public function getCategoryDictionary(): array
    {
        $sql = "SELECT vcid, category FROM vcategory ORDER BY category ASC";
        $result = $this->conn->query($sql);

        $options = [];
        if ($result && $result->num_rows > 0)
        {
            while ($row = $result->fetch_assoc())
            {
 $options[] = $row;
            }
        }
        return $options;
    }
}
?>
