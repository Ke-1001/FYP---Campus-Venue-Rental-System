<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php?error=access_denied");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    // get current hash
    $sql = "SELECT password_hash FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // verify current password
    if (!password_verify($current, $user['password_hash'])) {
        $error = "Current password is incorrect!";
    }
    elseif ($new !== $confirm) {
        $error = "New passwords do not match!";
    }
    else {
        $new_hash = password_hash($new, PASSWORD_DEFAULT);

        $update = "UPDATE users SET password_hash=? WHERE user_id=?";
        $stmt = $conn->prepare($update);
        $stmt->bind_param("si", $new_hash, $user_id);

        if ($stmt->execute()) {
            $success = "Password updated successfully!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <style>
        body{
            font-family:Arial;
            background:#f4f6f9;
            display:flex;
            justify-content:center;
            align-items:center;
            height:100vh;
        }
        .box{
            background:white;
            padding:40px;
            border-radius:8px;
            width:400px;
            box-shadow:0 2px 10px rgba(0,0,0,0.2);
        }
        input{
            width:100%;
            padding:10px;
            margin:10px 0;
        }
        button{
            padding:10px;
            background:#007bff;
            color:white;
            border:none;
            width:100%;
        }
        .error{color:red;}
        .success{color:green;}
    </style>
</head>
<body>

<div class="box">
    <h2>Change Password</h2>

    <?php if($error) echo "<p class='error'>$error</p>"; ?>
    <?php if($success) echo "<p class='success'>$success</p>"; ?>

    <form method="POST">
        <input type="password" name="current_password" placeholder="Current Password" required>
        <input type="password" name="new_password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
        <button type="submit">Update Password</button>
    </form>
</div>

</body>
</html>