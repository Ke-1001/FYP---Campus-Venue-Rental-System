<?php
// This section provides database access for ScheduleRepository data.
namespace Core\Repositories;

use mysqli;
use Core\Components\FilterBuilder;

class ScheduleRepository
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }


    public function getSemesterOptions(): array
    {
        $sql = "SELECT sem_id, sem_name FROM semester_config ORDER BY start_date DESC";
        $result = $this->conn->query($sql);

        $options = [];
        if ($result && $result->num_rows > 0)
        {
            while ($row = $result->fetch_assoc())
            {
                $options[$row['sem_id']] = $row['sem_name'];
            }
        }
        return $options;
    }


    public function getVenueOptions(): array
    {
        $sql = "SELECT vid, vname FROM venue ORDER BY vid ASC";
        $result = $this->conn->query($sql);

        $options = [];
        if ($result && $result->num_rows > 0)
        {
            while ($row = $result->fetch_assoc())
            {
                $options[$row['vid']] = '[' . $row['vid'] . '] ' . $row['vname'];
            }
        }
        return $options;
    }


    public function getAllWithFilters(FilterBuilder $filterBuilder)
    {
        $sql = "SELECT s.*, v.vname, sem.sem_name
                FROM academic_schedule s
                JOIN venue v ON s.vid = v.vid
                JOIN semester_config sem ON s.sem_id = sem.sem_id
                WHERE 1=1";


        $sql .= $filterBuilder->buildSqlWhere($this->conn);


        $sql .= " ORDER BY sem.start_date DESC, FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), s.start_time ASC";

        return $this->conn->query($sql);
    }


    public function getScheduleById(int $sch_id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM academic_schedule WHERE sch_id = ?");
        $stmt->bind_param("i", $sch_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }


    public function getVenuesForDropdown(): array
    {
        $sql = "SELECT vid, vname FROM venue ORDER BY vid ASC";
        $result = $this->conn->query($sql);
        $venues = [];
        if ($result && $result->num_rows > 0)
        {
            while ($row = $result->fetch_assoc())
            {
                $venues[] = $row;
            }
        }
        return $venues;
    }


    public function getSemestersForDropdown(): array
    {
        $sql = "SELECT sem_id, sem_name, is_active FROM semester_config ORDER BY start_date DESC";
        $result = $this->conn->query($sql);
        $semesters = [];
        if ($result && $result->num_rows > 0)
        {
            while ($row = $result->fetch_assoc())
            {
                $semesters[] = $row;
            }
        }
        return $semesters;
    }
}
?>
