<?php
require_once '../includes/admin_auth.php';

require_once '../config/db.php';

// Security check: Verify that the venue ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("<h2>Error: No venue specified!</h2><a href='manage_venues.php'>Return to List</a>");
}
$venue_id = intval($_GET['id']);

// get current venue details to pre-fill the form
$sql = "SELECT * FROM venues WHERE venue_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $venue_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<h2>Error: Venue not found!</h2>");
}
$venue = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>Edit Venue</title>
    <link rel="stylesheet" href="../assets/css/admin_style.css">
</head>
<body>

<div class="center-container" style="background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-top: 40px;">
    <h2>Edit Venue</h2>
    <p>You can modify the venue information or set the status to "Closed" to permanently disable reservations.</p>

    <form action="../actions/process_venue.php" method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="venue_id" value="<?= $venue_id ?>">
        
        <div class="form-group">
            <label>Venue Name:</label>
            <input type="text" name="venue_name" required value="<?= htmlspecialchars($venue['venue_name']) ?>">
        </div>
        
        <div class="form-group">
            <label>Category:</label>
            <select name="category" required>
                <option value="Discussion Room" <?= ($venue['category'] == 'Discussion Room') ? 'selected' : '' ?>>Discussion Room</option>
                <option value="Sports Court" <?= ($venue['category'] == 'Sports Court') ? 'selected' : '' ?>>Sports Court</option>
                <option value="Event Hall" <?= ($venue['category'] == 'Event Hall') ? 'selected' : '' ?>>Event Hall</option>
                <option value="Meeting Room" <?= ($venue['category'] == 'Meeting Room') ? 'selected' : '' ?>>Meeting Room</option>
            </select>
        </div>

        <div class="form-group">
            <label>Capacity:</label>
            <input type="number" name="capacity" min="1" required value="<?= $venue['capacity'] ?>">
        </div>

        <div class="form-group">
            <label>Base Deposit (RM):</label>
            <input type="number" name="base_deposit" step="0.01" min="0" required value="<?= $venue['base_deposit'] ?>">
        </div>

        <div class="form-group" style="background-color: #fff3cd; padding: 10px; border-radius: 4px; border-left: 4px solid #ffc107;">
            <label>Status:</label>
            <select name="status" required>
                <option value="Available" <?= ($venue['status'] == 'Available') ? 'selected' : '' ?>>Available</option>
                <option value="Maintenance" <?= ($venue['status'] == 'Maintenance') ? 'selected' : '' ?>>Maintenance</option>
                <option value="Closed" <?= ($venue['status'] == 'Closed') ? 'selected' : '' ?>>Closed</option>
            </select>
        </div>

        <button type="submit" class="btn-submit">Save Changes</button>
    </form>
    
    <a href="manage_venues.php" class="back-link">Cancel and Return</a>
</div>

</body>
</html>
<?php $conn->close(); ?>