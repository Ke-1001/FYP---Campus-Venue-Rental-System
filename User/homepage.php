<?php
session_start();
require_once '../config/db.php';

$sql = "SELECT venue_id, venue_name FROM venues LIMIT 3";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CVBMS - Venue Booking System</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
        }

        /* Navbar */
        .navbar {
            background-color: #0056b3;
            padding: 15px 30px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }

        /* Hero */
        .hero {
            background: #007bff;
            height: 300px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .hero h1 {
            margin: 0;
        }

        .search-box {
            margin-top: 20px;
        }

        .search-box input {
            padding: 10px;
            width: 250px;
        }

        .search-box button {
            padding: 10px;
            background: #ffc107;
            border: none;
            cursor: pointer;
        }

        /* Features */
        .features {
            display: flex;
            justify-content: space-around;
            padding: 40px;
        }

        .feature-box {
            background: white;
            padding: 20px;
            width: 25%;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }

        /* Recommended */
        .recommended {
            padding: 40px;
        }

        .venue-card {
            background: white;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
        }

        /* Footer */
        .footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 15px;
        }
    </style>
</head>

<body>

<!-- Navbar -->
<div class="navbar">
    <div><strong>CVBMS</strong></div>

    <div>
        <a href="homepage.php">Home</a>
        <a href="venues.php">Venues</a>
        <a href="my_bookings.php">My Bookings</a>

        <?php if (isset($_SESSION['user_id'])): ?>
            <span>Welcome, <?php echo $_SESSION['full_name']; ?> 👋</span>
            <a href="../user/user_logout.php">Logout</a>
        <?php else: ?>
            <a href="../user/user_login.php">Login</a>
        <?php endif; ?>
    </div>
</div>

<!-- Hero -->
<div class="hero">
    <h1>Book Your Perfect Venue</h1>
    <p>Fast, easy, and secure booking system</p>

    <!-- 🔍 Search Feature -->
    <form action="venues.php" method="GET" class="search-box">
        <input type="text" name="search" placeholder="Search venue...">
        <button type="submit">Search</button>
    </form>
</div>

<!-- Features -->
<div class="features">
    <div class="feature-box">
        <h3>Easy Booking</h3>
        <p>Reserve your venue in just a few clicks.</p>
    </div>

    <div class="feature-box">
        <h3>Multiple Locations</h3>
        <p>Choose from various venues.</p>
    </div>

    <div class="feature-box">
        <h3>Real-Time Availability</h3>
        <p>Check availability instantly.</p>
    </div>
</div>

<!-- ⭐ Recommended Venues -->
<div class="recommended">
    <h2>Recommended Venues</h2>

    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="venue-card">
                <h3><?php echo $row['venue_name']; ?></h3>
                <a href="venue_details.php?id=<?php echo $row['venue_id']; ?>">
                    View Details
                </a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No venues available.</p>
    <?php endif; ?>
</div>

<!-- Footer -->
<div class="footer">
    <p>&copy; 2026 CVBMS | Venue Booking Management System</p>
</div>

</body>
</html>

<?php $conn->close(); ?>