<?php
include("../includes/user_header.php");
include("../includes/user_navbar.php");
include("../config/db.php");

$result = $conn->query("SELECT * FROM venue WHERE status='Available'");
?>

<h2>Available Venues</h2>

<?php while($row = $result->fetch_assoc()): ?>
<div class="card">
    <h3><?php echo $row['vname']; ?></h3>
    <p>Capacity: <?php echo $row['max_cap']; ?></p>
    <p>Deposit: RM <?php echo $row['deposit']; ?></p>

    <a href="venue_details.php?vid=<?php echo $row['vid']; ?>">
        <button>View Details</button>
    </a>
</div>
<?php endwhile; ?>

</div></body></html>