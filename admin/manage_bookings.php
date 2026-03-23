<?php
require_once '../includes/admin_auth.php';
require_once '../config/db.php';

// get all "Pending" bookings
$sql = "SELECT 
            b.booking_id, 
            b.booking_date, 
            b.start_time, 
            b.end_time, 
            u.full_name, 
            v.venue_name
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN venues v ON b.venue_id = v.venue_id
        WHERE b.booking_status = 'Pending'
        ORDER BY b.booking_date ASC, b.start_time ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Booking Review Center</title>
    <link rel="stylesheet" href="../assets/css/admin_style.css">
</head>
<body>

    <div class="nav-bar">
        <div class="nav-links">
            <a href="manage_bookings.php">Bookings</a>
            <a href="inspections.php">Inspections</a>
            <a href="manage_venues.php">Manage Venues</a>
            <a href="reports.php">Reports</a>
        </div>
        <a href="../actions/logout.php" class="btn-logout" onclick="return confirm('Are you sure you want to log out?');">Logout</a>
    </div>

    <div class="container">
        <h2>Booking Review Center (Pending Requests)</h2>
        <p>Please review the following student venue booking requests:</p>

        <table>
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>Student Name</th>
                    <th>Venue</th>
                    <th>Booking Date</th>
                    <th>Time Slot</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $time_slot = date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time']));
                        echo "<tr>";
                        echo "<td>#{$row['booking_id']}</td>";
                        echo "<td>{$row['full_name']}</td>";
                        echo "<td>{$row['venue_name']}</td>";
                        echo "<td>{$row['booking_date']}</td>";
                        echo "<td>{$time_slot}</td>";
                        echo "<td>
                                <a href='../actions/process_approval.php?id={$row['booking_id']}&action=approve' class='btn btn-approve' onclick=\"return confirm('are you sure you want to approve this booking request?');\">Accept</a>
                                <a href='../actions/process_approval.php?id={$row['booking_id']}&action=reject' class='btn btn-reject' onclick=\"return confirm('are you sure you want to reject this booking request?');\">Reject</a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='empty-msg'>Currently, there are no pending booking requests.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</body>
</html>
<?php $conn->close(); ?>