<?php
include("../includes/user_auth.php");
include("../includes/user_header.php");
include("../includes/user_navbar.php");
include("../config/db.php");

$user_id = $_SESSION['uid'];

$stmt = $conn->prepare("
    SELECT b.bid, v.vname, b.date_booked, b.status
    FROM booking b
    JOIN venue v ON b.vid = v.vid
    WHERE b.uid = ?
    ORDER BY b.date_booked DESC
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<h2 class="text-3xl font-bold mb-6">My Bookings</h2>

<div class="grid gap-4">
<?php while($row = $result->fetch_assoc()): ?>
    
    <div class="glass p-5">

        <div class="flex justify-between items-center mb-2">
            <h3 class="text-lg font-semibold">
                <?php echo htmlspecialchars($row['vname']); ?>
            </h3>

            <span class="px-3 py-1 text-xs rounded-full 
                <?php echo $row['status'] == 'Pending' 
                ? 'bg-yellow-400/20 text-yellow-300' 
                : 'bg-green-400/20 text-green-300'; ?>">
                <?php echo htmlspecialchars($row['status']); ?>
            </span>
        </div>

        <p class="text-gray-300 text-sm">
            Date: <?php echo htmlspecialchars($row['date_booked']); ?>
        </p>

    </div>

<?php endwhile; ?>
</div>
</body>
</html>