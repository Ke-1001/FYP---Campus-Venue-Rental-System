<?php
include("../includes/auth.php");
include("../config/db.php");

$result = $conn->query("SELECT * FROM venue WHERE status='Available'");
?>

<h2>Available Venues</h2>

<?php while($row = $result->fetch_assoc()): ?>
    <p>
        <?php echo $row['vname']; ?>
        <a href="venue_details.php?vid=<?php echo $row['vid']; ?>">View</a>
    </p>
<?php endwhile; ?>