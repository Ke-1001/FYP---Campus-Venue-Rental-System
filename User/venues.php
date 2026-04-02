<?php
$page_title = "Venues";
include("../includes/user_header.php");
include("../includes/user_navbar.php");
require_once("../config/db.php");

$sql = "SELECT venue_id, venue_name, category, capacity, base_deposit, status
        FROM venues
        WHERE status = 'Available'
        ORDER BY venue_name ASC";

$result = $conn->query($sql);
?>

<div class="card">
    <h2>Available Venues</h2>
    <p>Select a venue to view details and make booking.</p>
</div>

<div class="grid">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($venue = $result->fetch_assoc()): ?>
            <div class="card">
                <h3><?php echo htmlspecialchars($venue["venue_name"]); ?></h3>

                <div class="info-row">
                    <strong>Category:</strong> <?php echo htmlspecialchars($venue["category"]); ?>
                </div>

                <div class="info-row">
                    <strong>Capacity:</strong> <?php echo (int)$venue["capacity"]; ?> people
                </div>

                <div class="info-row">
                    <strong>Deposit:</strong> RM <?php echo number_format((float)$venue["base_deposit"], 2); ?>
                </div>

                <a href="venue_details.php?venue_id=<?php echo (int)$venue["venue_id"]; ?>" class="btn">
                    View Details
                </a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="card">
            <h3>No available venues</h3>
            <p>There are currently no venues available for booking.</p>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
include("../includes/user_footer.php");
?>