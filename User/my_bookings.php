<?php
include("../includes/auth.php");
include("../config/db.php");

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT b.booking_id, v.venue_name, b.booking_date, b.booking_status
    FROM bookings b
    JOIN venues v ON b.venue_id = v.venue_id
    WHERE b.user_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<h2>My Bookings</h2>

<?php while($row = $result->fetch_assoc()): ?>
    <p>
        <?php echo $row['venue_name']; ?> -
        <?php echo $row['booking_date']; ?> -
        <?php echo $row['booking_status']; ?>
    </p>
<?php endwhile; ?>