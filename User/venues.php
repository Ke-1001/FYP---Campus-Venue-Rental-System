<?php
// File path: user/venues.php
$page_title = "Venues";
include("../includes/user_header.php");
include("../includes/user_navbar.php");
require_once("../config/db.php");

// 💡 适配新架构：使用 venue 表，以及小写的 status
$sql = "SELECT vid, vname, category, max_cap, deposit, status
        FROM venue
        WHERE status IN ('available', 'maintenance')
        ORDER BY vname ASC";

$result = $conn->query($sql);
?>

<div class="card">
    <h2>Available Venues</h2>
    <p>Select a venue to view details. Venues currently under maintenance are visible but locked from booking.</p>
</div>

<div class="grid">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($venue = $result->fetch_assoc()): ?>
            <div class="card" <?php echo $venue['status'] === 'maintenance' ? 'style="border: 2px solid #f59e0b; background-color: #fffbeb; opacity: 0.85;"' : ''; ?>>
                
                <h3>
                    <?php echo htmlspecialchars($venue["vname"]); ?>
                    <?php if ($venue['status'] === 'maintenance'): ?>
                        <span style="font-size: 11px; color: #d97706; background: #fef3c7; border: 1px solid #fcd34d; padding: 2px 6px; border-radius: 4px; vertical-align: middle; margin-left: 8px; font-weight: bold; text-transform: uppercase;">
                            🔧 Maintenance
                        </span>
                    <?php endif; ?>
                </h3>

                <div class="info-row">
                    <strong>Category:</strong> <?php echo htmlspecialchars($venue["category"]); ?>
                </div>

                <div class="info-row">
                    <strong>Capacity:</strong> <?php echo (int)$venue["max_cap"]; ?> people
                </div>

                <div class="info-row">
                    <strong>Deposit:</strong> RM <?php echo number_format((float)$venue["deposit"], 2); ?>
                </div>

                <a href="venue_details.php?vid=<?php echo urlencode($venue["vid"]); ?>" class="btn" <?php echo $venue['status'] === 'maintenance' ? 'style="background-color: #d97706; border-color: #d97706;"' : ''; ?>>
                    View Details
                </a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="card">
            <h3>No available venues</h3>
            <p>There are currently no venues available for viewing.</p>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
include("../includes/user_footer.php");
?>