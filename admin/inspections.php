<?php
require_once '../includes/admin_auth.php';
require_once '../config/db.php';

// Logic: Only fetch bookings with status 'Approved' (approved, meaning students can use) for inspection
// Logic: Status must be 'Approved', AND (AND) the venue's "end time" must be "earlier than the current time"
// SQL JOIN query: Supports "time expired" and "manual early return"
$sql = "SELECT 
            b.booking_id, 
            b.booking_date, 
            b.start_time, 
            b.end_time, 
            b.booking_status,
            u.full_name AS student_name, 
            v.venue_name, 
            p.deposit_paid
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN venues v ON b.venue_id = v.venue_id
        JOIN payments p ON b.booking_id = p.booking_id
        WHERE (b.booking_status = 'Returned') 
           OR (b.booking_status = 'Approved' AND CONCAT(b.booking_date, ' ', b.end_time) <= NOW())
        ORDER BY b.booking_date ASC, b.start_time ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard- Pending Inspections</title>
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

    <h2>Pending Inspections</h2>
    <p>Admin please click "Execute Inspection" after students have finished using the venue to assess damage and settle deposits.</p>

    <table>
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>Student Name</th>
                <th>Venue</th>
                <th>Booking Date</th>
                <th>Time Slot</th>
                <th>Deposit Paid</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                // Loop through each database result and output as a table row (<tr>)
                while($row = $result->fetch_assoc()) {
                    // Format the time, removing seconds for a cleaner display
                    $time_slot = date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time']));
                    $deposit = "RM " . number_format($row['deposit_paid'], 2);
                    
                    echo "<tr>";
                    echo "<td>#{$row['booking_id']}</td>";
                    echo "<td>{$row['student_name']}</td>";
                    echo "<td>{$row['venue_name']}</td>";
                    echo "<td>{$row['booking_date']}</td>";
                    echo "<td>{$time_slot}</td>";
                    echo "<td><strong style='color: #0056b3;'>{$deposit}</strong></td>";
                    // The button here will include the booking_id, redirecting to the actual fine-form page
                    echo "<td><a href='inspection_form.php?id={$row['booking_id']}' class='btn-inspect'>Execute Inspection</a></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7' class='empty-msg'>Currently, there are no pending inspections.</td></tr>";
            }
            ?>
        </tbody>
    </table>

</body>
</html>
<?php $conn->close(); ?>