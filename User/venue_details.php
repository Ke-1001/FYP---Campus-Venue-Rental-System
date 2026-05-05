<?php
include("../includes/user_header.php");
include("../includes/user_navbar.php");
include("../config/db.php");

if (!isset($_GET['vid']) || !is_numeric($_GET['vid'])) {
    die("Invalid venue");
}

$venue_id = $_GET['vid'];

$stmt = $conn->prepare("SELECT * FROM venue WHERE vid=?");
$stmt->bind_param("i", $venue_id);
$stmt->execute();
$result = $stmt->get_result();

$row = $result->fetch_assoc();
?>

<div class="card">
    <h2><?php echo $row['vname']; ?></h2>
    <p>Category: <?php echo $row['category']; ?></p>
    <p>Capacity: <?php echo $row['max_cap']; ?></p>
    <p>Deposit: RM <?php echo $row['deposit']; ?></p>

    <a href="booking_form.php?vid=<?php echo $venue_id; ?>">
        <button>Book Now</button>
    </a>
</div>

</div></body></html>