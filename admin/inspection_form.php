<?php
require_once '../includes/admin_auth.php';

require_once '../config/db.php';

// 1. Safely retrieve the booking_id from the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("<h2>Error: No booking specified for inspection!</h2><a href='inspections.php'>Return to List</a>");
}
$booking_id = intval($_GET['id']);

// 2. Query booking details to display to admin
$sql = "SELECT b.booking_date, b.start_time, b.end_time, u.full_name, v.venue_name, p.deposit_paid 
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN venues v ON b.venue_id = v.venue_id
        JOIN payments p ON b.booking_id = p.booking_id
        WHERE b.booking_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<h2>Error: Booking not found or data anomaly.</h2>");
}
$booking = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>Inspection Form</title>
    <link rel="stylesheet" href="../assets/css/admin_style.css">
</head>
<body>

<div class="container">
    <h2>Venue Inspection Form</h2>
    
    <div class="info-box">
        <p><strong>Student Name:</strong> <?= htmlspecialchars($booking['full_name']) ?></p>
        <p><strong>Venue:</strong> <?= htmlspecialchars($booking['venue_name']) ?></p>
        <p><strong>Booking Date & Time:</strong> <?= $booking['booking_date'] ?> (<?= date('H:i', strtotime($booking['start_time'])) ?> - <?= date('H:i', strtotime($booking['end_time'])) ?>)</p>
        <p><strong>Deposit Paid:</strong> RM <?= number_format($booking['deposit_paid'], 2) ?></p>
    </div>

    <form action="../actions/process_inspection.php" method="POST">
        <input type="hidden" name="booking_id" value="<?= $booking_id ?>">

        <div class="form-group">
            <label for="inspection_status">Venue Status: </label>
            <select name="inspection_status" id="inspection_status" required>
                <option value="Good">Good</option>
                <option value="Dirty">Dirty - Deduction Only</option>
                <option value="Minor_Damage">Minor Damage - Deduction, Venue Remains Open</option>
                <option value="Major_Damage">Major Damage - Deduction and Venue Locked</option>
            </select>
        </div>

        <div class="form-group">
            <label for="damage_description">Description: </label>
            <textarea name="damage_description" id="damage_description" rows="3" placeholder="If the venue is in good condition, this field can be left blank..."></textarea>
        </div>

        <div class="form-group">
            <label for="assessed_penalty">Assessed Penalty Amount (RM):</label>
            <input type="number" name="assessed_penalty" id="assessed_penalty" step="0.01" min="0" value="0.00" required>
            <small style="color: #666;">* If no damage is found, please enter 0, and the system will automatically refund the full deposit.</small>
        </div>

        <button type="submit" class="btn-submit">Confirm Submission & Settle</button>
    </form>
    
    <a href="inspections.php" class="back-link">Return to Pending Inspections List</a>
</div>

</body>
</html>
<?php $conn->close(); ?>