<?php
// File path: user/venue_details.php
session_start();
$page_title = "Venue Details";
include("../includes/user_header.php");
include("../includes/user_navbar.php");
require_once("../config/db.php");

// 💡 1. 適配新架構：接收字串型態的 vid
$vid = $_GET["vid"] ?? '';

// 💡 2. 適配新架構：使用 venue 表，並允許檢視 maintenance 狀態的場地
$sql = "SELECT vid, vname, category, max_cap, deposit, status
        FROM venue
        WHERE vid = ? AND status IN ('available', 'maintenance')
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $vid); // "s" for string
$stmt->execute();
$result = $stmt->get_result();
$venue = $result->fetch_assoc();
?>

<?php if ($venue): ?>
    <div class="card">
        <h2>
            <?php echo htmlspecialchars($venue["vname"]); ?>
            <?php if ($venue['status'] === 'maintenance'): ?>
                <span style="font-size: 12px; color: #d97706; background: #fef3c7; border: 1px solid #fcd34d; padding: 2px 8px; border-radius: 4px; vertical-align: middle; margin-left: 8px; font-weight: bold; text-transform: uppercase;">
                    Under Maintenance
                </span>
            <?php endif; ?>
        </h2>

        <div class="info-row">
            <strong>Category:</strong> <?php echo htmlspecialchars($venue["category"]); ?>
        </div>

        <div class="info-row">
            <strong>Capacity:</strong> <?php echo (int)$venue["max_cap"]; ?> people
        </div>

        <div class="info-row">
            <strong>Deposit Required:</strong> RM <?php echo number_format((float)$venue["deposit"], 2); ?>
        </div>

        <div class="info-row">
            <strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($venue["status"])); ?>
        </div>

        <div style="margin-top: 20px;">
            <?php if ($venue['status'] === 'available'): ?>
                <a href="booking_form.php?vid=<?php echo urlencode($venue["vid"]); ?>" class="btn" style="margin-right: 10px;">Book Now</a>
            <?php else: ?>
                <button class="btn" disabled style="background-color: #94a3b8; border-color: #94a3b8; cursor: not-allowed; margin-right: 10px;">Currently Unavailable</button>
            <?php endif; ?>
            
            <a href="venues.php" class="btn" style="background-color: #64748b; border-color: #64748b;">Back to List</a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <h2>Venue Not Found</h2>
        <p>The selected venue does not exist or has been removed from the system.</p>
        <a href="venues.php" class="btn" style="background-color: #64748b; border-color: #64748b;">Back to Venues</a>
    </div>
<?php endif; ?>

<?php
$stmt->close();
$conn->close();
include("../includes/user_footer.php");
?>