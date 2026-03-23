<?php
require_once '../includes/admin_auth.php';
require_once '../config/db.php';

// --- Query 1: System Overview (Summary Statistics) ---
// Query for total bookings and completed bookings
$sql_summary = "SELECT 
                    COUNT(*) AS total_bookings,
                    SUM(CASE WHEN booking_status = 'Completed' THEN 1 ELSE 0 END) AS completed_bookings
                FROM bookings";
$res_summary = $conn->query($sql_summary);
$summary = $res_summary->fetch_assoc();

// Query for total fine revenue (from all 'Damaged' or 'Dirty' penalties in the inspections table)
$sql_finance = "SELECT SUM(assessed_penalty) AS total_penalties FROM inspections";
$res_finance = $conn->query($sql_finance);
$finance = $res_finance->fetch_assoc();
$total_penalties = $finance['total_penalties'] ? $finance['total_penalties'] : 0.00;

// --- Data Query 2: Venue Popularity Ranking ---
// Use LEFT JOIN and GROUP BY to calculate how many times each venue has been borrowed
$sql_popular = "SELECT 
                    v.venue_name, 
                    COUNT(b.booking_id) AS usage_count 
                FROM venues v
                LEFT JOIN bookings b ON v.venue_id = b.venue_id
                GROUP BY v.venue_id
                ORDER BY usage_count DESC";
$res_popular = $conn->query($sql_popular);

// --- Data Query 3: Recent Damage and Penalty Records ---
$sql_penalties = "SELECT 
                    i.inspected_at, 
                    v.venue_name, 
                    u.full_name, 
                    i.inspection_status, 
                    i.assessed_penalty 
                  FROM inspections i
                  JOIN bookings b ON i.booking_id = b.booking_id
                  JOIN venues v ON b.venue_id = v.venue_id
                  JOIN users u ON b.user_id = u.user_id
                  WHERE i.assessed_penalty > 0
                  ORDER BY i.inspected_at DESC LIMIT 10";
$res_penalties = $conn->query($sql_penalties);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Operational Report System</title>
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <style>
        /* Additional styles for the report */
        .dashboard-cards { display: flex; gap: 20px; margin-bottom: 30px; }
        .card { 
            flex: 1; background-color: #fff; padding: 20px; border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; border-top: 4px solid #007bff;
        }
        .card h3 { margin: 0; color: #666; font-size: 16px; }
        .card .number { font-size: 32px; font-weight: bold; color: #333; margin: 10px 0 0 0; }
        .text-danger { color: #dc3545; font-weight: bold; }
        
        /* Print mode settings: When pressing ctrl+p to export PDF, hide the navigation bar and buttons */
        @media print {
            .nav-bar, .btn-print { display: none !important; }
            body { background-color: #fff; margin: 0; }
            .container { box-shadow: none; }
        }
    </style>
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
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>System Operations and Financial Settlement Report</h2>
            <button class="btn btn-print" onclick="window.print()" style="background-color: #6c757d; color: white;">Print / Export to PDF</button>
        </div>
        <p>Data statistics time: <?= date('Y-m-d H:i:s') ?></p>

        <div class="dashboard-cards">
            <div class="card">
                <h3>Total Bookings</h3>
                <p class="number"><?= $summary['total_bookings'] ?> <span style="font-size:14px; color:#888;">times</span></p>
            </div>
            <div class="card">
                <h3>Completed (Inspection finished)</h3>
                <p class="number"><?= $summary['completed_bookings'] ?> <span style-="font-size:14px; color:#888;">records</span></p>
            </div>
            <div class="card" style="border-top-color: #dc3545;">
                <h3>Cumulative Fine Revenue</h3>
                <p class="number text-danger">RM <?= number_format($total_penalties, 2) ?></p>
            </div>
        </div>

        <div class="flex-container">
            <div class="box form-section">
                <h3>Venue Usage Ranking</h3>
                <table>
                    <thead>
                        <tr><th>Venue Name</th><th>Times Borrowed</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($res_popular->num_rows > 0) {
                            while($row = $res_popular->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>{$row['venue_name']}</td>";
                                echo "<td><strong>{$row['usage_count']}</strong> times</td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="box table-section">
                <h3>Recent Damage and Fine Records</h3>
                <table>
                    <thead>
                        <tr><th>Inspection Date</th><th>Venue</th><th>Borrowing Student</th><th>Status</th><th>Assessed Fine</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($res_penalties->num_rows > 0) {
                            while($row = $res_penalties->fetch_assoc()) {
                                $date = date('Y-m-d', strtotime($row['inspected_at']));
                                echo "<tr>";
                                echo "<td>{$date}</td>";
                                echo "<td>{$row['venue_name']}</td>";
                                echo "<td>{$row['full_name']}</td>";
                                echo "<td class='text-danger'>{$row['inspection_status']}</td>";
                                echo "<td>RM " . number_format($row['assessed_penalty'], 2) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='empty-msg'>No recent damage or fine records.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>
<?php $conn->close(); ?>