<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php?error=access_denied");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT full_name, email, role FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body{
            margin:0;
            font-family:'Poppins', sans-serif;
            background: linear-gradient(120deg,#4e73df,#1cc88a);
            height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
        }

        .profile-container{
            width:850px;
            height:500px;
            background:white;
            border-radius:15px;
            display:flex;
            overflow:hidden;
            box-shadow:0 15px 40px rgba(0,0,0,0.2);
        }

        .left-panel{
            width:35%;
            background:#2e59d9;
            color:white;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            padding:30px;
        }

        .avatar{
            width:120px;
            height:120px;
            border-radius:50%;
            background:white;
            margin-bottom:20px;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:40px;
            color:#2e59d9;
            font-weight:600;
        }

        .left-panel h3{
            margin:10px 0 5px;
        }

        .left-panel p{
            opacity:0.8;
            font-size:14px;
        }

        .right-panel{
            width:65%;
            padding:40px;
        }

        .right-panel h2{
            margin-bottom:25px;
            color:#333;
        }

        .info-group{
            margin-bottom:20px;
        }

        .info-group label{
            font-weight:600;
            color:#555;
            font-size:14px;
        }

        .info-box{
            margin-top:5px;
            padding:10px;
            border-radius:6px;
            background:#f8f9fc;
            border:1px solid #ddd;
        }

        .edit-btn{
            margin-top:25px;
            padding:10px 18px;
            background:#4e73df;
            color:white;
            border:none;
            border-radius:6px;
            cursor:pointer;
            font-weight:600;
        }

        .edit-btn:hover{
            background:#2e59d9;
        }
    </style>
</head>
<body>

<div class="profile-container">

    <!-- LEFT PANEL -->
    <div class="left-panel">
        <div class="avatar">
            <?php echo strtoupper(substr($user['full_name'],0,1)); ?>
        </div>
        <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
        <p><?php echo htmlspecialchars($user['role']); ?></p>
    </div>

    <!-- RIGHT PANEL -->
    <div class="right-panel">
        <h2>Profile Information</h2>

        <div class="info-group">
            <label>Full Name</label>
            <div class="info-box">
                <?php echo htmlspecialchars($user['full_name']); ?>
            </div>
        </div>

        <div class="info-group">
            <label>Email Address</label>
            <div class="info-box">
                <?php echo htmlspecialchars($user['email']); ?>
            </div>
        </div>

        <a href="edit_profile.php">
    <button class="edit-btn">Edit Profile</button>
</a>
        <a href="change_password.php">
    <button class="edit-btn" style="background:#e74a3b;margin-left:10px;">
        Change Password
    </button>
</a>
    </div>

</div>

</body>
</html>