<?php
$page_title = "User Dashboard";
include("../includes/user_header.php");
include("../includes/user_navbar.php");
?>

<div class="card">
    <h1>Welcome to Campus Venue Rental System</h1>
    <p>This is the user interface for students to browse venues, make bookings, and view booking history.</p>
    <a href="venues.php" class="btn">Browse Venues</a>
</div>

<div class="card">
    <h2>User Functions</h2>
    <p>You can use this system to:</p>
    <ul>
        <li>View available venues</li>
        <li>Check venue details</li>
        <li>Submit booking request</li>
        <li>View your bookings</li>
        <li>Return venue</li>
    </ul>
</div>

<?php include("../includes/user_footer.php"); ?>