<?php
$page_title = "Booking Form";
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
        <h2>Booking Form</h2>
        <p><strong>Selected Venue:</strong> <?php echo htmlspecialchars($venue["venue_name"]); ?></p>
        <p><strong>Category:</strong> <?php echo htmlspecialchars($venue["category"]); ?></p>
        <p><strong>Capacity:</strong> <?php echo (int)$venue["capacity"]; ?> people</p>
        <p><strong>Deposit:</strong> RM <?php echo number_format((float)$venue["base_deposit"], 2); ?></p>

        <form action="../actions/process_booking.php" method="POST">
            <input type="hidden" name="user_id" value="1">
            <input type="hidden" name="venue_id" value="<?php echo (int)$venue["venue_id"]; ?>">
            <input type="hidden" name="deposit_paid" value="<?php echo (float)$venue["base_deposit"]; ?>">

            <div class="info-row">
                <label for="booking_date"><strong>Booking Date</strong></label>
                <input type="date" name="booking_date" id="booking_date" required>
            </div>

            <div class="info-row">
                <label for="start_time"><strong>Start Time</strong></label>
                <input type="time" name="start_time" id="start_time" required>
            </div>

            <div class="info-row">
                <label for="end_time"><strong>End Time</strong></label>
                <input type="time" name="end_time" id="end_time" required>
            </div>

            <button type="submit" class="btn">Submit Booking</button>
            <a href="venue_details.php?venue_id=<?php echo (int)$venue["venue_id"]; ?>" class="btn">Back</a>
        </form>
    </div>
<?php else: ?>
    <div class="card">
        <h2>Invalid Venue</h2>
        <p>Venue not found or not available.</p>
        <a href="venues.php" class="btn">Back to Venues</a>
    </div>
<?php endif; ?>

<?php
$stmt->close();
$conn->close();
include("../includes/user_footer.php");
?>