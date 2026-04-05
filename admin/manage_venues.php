<?php
require_once '../includes/admin_auth.php';

require_once '../config/db.php';

// get all venues for display
$sql = "SELECT * FROM venues ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Venue Management Center</title>
    <link rel="stylesheet" href="../assets/css/admin_style.css">
</head>
<body>

    <div class="nav-bar">
        <div class="nav-links">
            <a href="manage_bookings.php">Bookings</a>
            <a href="inspections.php">Inspections</a>
            <a href="manage_venues.php">Manage Venues</a>
            <a href="reports.php">Reports</a>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Super_Admin'): ?>
                <a href="add_admin.php" style="color: #dc3545;">Staff Management</a>
            <?php endif; ?>
        </div>
        <a href="../actions/logout.php" class="btn-logout" onclick="return confirm('Are you sure you want to log out?');">Logout</a>
    </div>

    <h2>Venue Management</h2>

    <div class="container">
        <div class="box form-section">
            <h3>Add New Venue</h3>
            <form action="../actions/process_venue.php" method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Venue Name:</label>
                    <input type="text" name="venue_name" required placeholder="e.g., Main Hall B">
                </div>
                
                <div class="form-group">
                    <label>Category:</label>
                    <select name="category" required>
                        <option value="Discussion Room">Discussion Room</option>
                        <option value="Sports Court">Sports Court</option>
                        <option value="Event Hall">Event Hall</option>
                        <option value="Meeting Room">Meeting Room</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Capacity:</label>
                    <input type="number" name="capacity" min="1" required>
                </div>

                <div class="form-group">
                    <label>Base Deposit (RM):</label>
                    <input type="number" name="base_deposit" step="0.01" min="0" required placeholder="0.00">
                </div>

                <button type="submit" class="btn btn-add">Confirm Addition</button>
            </form>
        </div>

        <div class="box table-section">
            <h3>Existing Venues List</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Capacity</th>
                        <th>Deposit (RM)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $status_class = ($row['status'] == 'Available') ? 'status-available' : 'status-maintenance';
                            echo "<tr>";
                            echo "<td>{$row['venue_id']}</td>";
                            echo "<td>{$row['venue_name']}</td>";
                            echo "<td>{$row['category']}</td>";
                            echo "<td>{$row['capacity']} people</td>";
                            echo "<td>" . number_format($row['base_deposit'], 2) . "</td>";
                            echo "<td class='{$status_class}'>{$row['status']}</td>";
                            // Delete button: pass venue_id and action to backend via GET
                            echo "<td>
                                 <a href='edit_venue.php?id={$row['venue_id']}' class='btn btn-approve' style='background-color:#17a2b8; margin-right:5px;'>Edit</a>
                                <a href='../actions/process_venue.php?action=delete&id={$row['venue_id']}' class='btn btn-delete' onclick=\"return confirm('Warning: Deleting this venue will also delete all associated booking records! Are you sure you want to delete it?');\">Delete</a>
                            </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align:center;'>No venues available at the moment.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
<?php $conn->close(); ?>