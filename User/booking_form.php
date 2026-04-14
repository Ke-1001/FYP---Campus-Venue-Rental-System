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
$min_date = date('Y-m-d'); 
?>

<?php if ($venue): ?>
    <div class="card">
        <h2>Booking Form</h2>
        <p><strong>Selected Venue:</strong> <?php echo htmlspecialchars($venue["venue_name"]); ?></p>
        <p><strong>Category:</strong> <?php echo htmlspecialchars($venue["category"]); ?></p>
        <p><strong>Capacity:</strong> <?php echo (int)$venue["capacity"]; ?> people</p>
        <p><strong>Deposit:</strong> RM <?php echo number_format((float)$venue["base_deposit"], 2); ?></p>

          <form action="../actions/process_booking.php" method="POST" id="bookingForm">
            <input type="hidden" name="venue_id" value="<?php echo (int)$venue["venue_id"]; ?>">

            <div class="info-row">
                <label for="purpose"><strong>Booking Purpose</strong></label>
                <input type="text" name="purpose" id="purpose" placeholder="e.g., Final Year Project Presentation" required>
            </div>

            <div class="info-row">
                <label for="booking_date"><strong>Booking Date</strong></label>
                <input type="date" name="booking_date" id="booking_date" min="<?php echo $min_date; ?>" required>
            </div>

            <div class="info-row">
                <label for="start_time"><strong>Start Time</strong></label>
                <input type="time" name="start_time" id="start_time" required>
            </div>

            <div class="info-row">
                <label for="end_time"><strong>End Time</strong></label>
                <input type="time" name="end_time" id="end_time" required>
            </div>

            <button type="submit" class="btn">Proceed to Payment Sandbox</button>
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

 <script>
            document.getElementById('bookingForm').addEventListener('submit', function(e) {
                const startTime = document.getElementById('start_time').value;
                const endTime = document.getElementById('end_time').value;

                // 執行布林邏輯校驗：結束時間必須大於開始時間
                if (startTime >= endTime) {
                    e.preventDefault(); // 阻斷提交
                    alert("🚨 Logical Error: End Time must be strictly greater than Start Time ($Time_{end} > Time_{start}$).");
                }
            });
        </script>