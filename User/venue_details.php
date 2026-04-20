<?php
session_start();
$page_title = "Venue Details";
include("../includes/user_header.php");
include("../includes/user_navbar.php");
require_once("../config/db.php");

$venue_id = isset($_GET["venue_id"]) ? (int)$_GET["venue_id"] : 0;

$sql = "SELECT venue_id, venue_name, category, capacity, base_deposit, status
        FROM venues
        WHERE venue_id = ? AND status = 'Available'
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $venue_id);
$stmt->execute();
$result = $stmt->get_result();
$venue = $result->fetch_assoc();
?>

<?php if ($venue): ?>
    <div class="card">
        <h2><?php echo htmlspecialchars($venue["venue_name"]); ?></h2>

        <div class="info-row">
            <strong>Category:</strong> <?php echo htmlspecialchars($venue["category"]); ?>
        </div>

        <div class="info-row">
            <strong>Capacity:</strong> <?php echo (int)$venue["capacity"]; ?> people
        </div>

        <div class="info-row">
            <strong>Deposit:</strong> RM <?php echo number_format((float)$venue["base_deposit"], 2); ?>
        </div>

        <div class="info-row">
            <strong>Status:</strong> <?php echo htmlspecialchars($venue["status"]); ?>
        </div>

        <a href="booking_form.php?venue_id=<?php echo (int)$venue["venue_id"]; ?>" class="btn">Book Now</a>
        <a href="venues.php" class="btn">Back</a>
    </div>
<?php else: ?>
    <div class="card">
        <h2>Venue not found</h2>
        <p>The selected venue does not exist or is not available.</p>
        <a href="venues.php" class="btn">Back to Venues</a>
    </div>
<?php endif; ?>

<?php
$stmt->close();
$conn->close();
include("../includes/user_footer.php");
?>