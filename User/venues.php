<?php
include("../includes/auth.php");
include("../config/db.php");

$result = $conn->query("SELECT * FROM venues WHERE status='Available'");
?>

<h2>Available Venues</h2>

<?php while($row = $result->fetch_assoc()): ?>
    <p>
        <?php echo $row['venue_name']; ?>
        <a href="venue_details.php?venue_id=<?php echo $row['venue_id']; ?>">View</a>
    </p>
<?php endwhile; ?>