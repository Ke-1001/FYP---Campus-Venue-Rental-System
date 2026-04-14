<?php
session_start();
require_once '../config/db.php';

// Security check: only logged-in users can access
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php?error=access_denied");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user information from database
$sql = "SELECT full_name, email, phone_number, role, created_at 
        FROM users 
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <style>
        .profile-box {
            width: 600px;
            margin: 60px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .profile-box h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #0056b3;
        }
        .profile-info p {
            font-size: 16px;
            margin: 10px 0;
        }
        .label {
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="profile-box">
    <h2>My Profile</h2>
    <div class="profile-info">
        <p><span class="label">Full Name:</span> <?php echo $user['full_name']; ?></p>
        <p><span class="label">Email:</span> <?php echo $user['email']; ?></p>
        <p><span class="label">Phone Number:</span> <?php echo $user['phone_number']; ?></p>
        <p><span class="label">Role:</span> <?php echo $user['role']; ?></p>
        <p><span class="label">Account Created:</span> <?php echo $user['created_at']; ?></p>
    </div>
</div>

</body>
</html>