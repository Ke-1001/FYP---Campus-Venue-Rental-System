<?php
session_start();

// if admin is already logged in, redirect to manage_bookings.php
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'Admin') {
    header("Location: manage_bookings.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - CVBMS</title>
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <style>
        /* exclusive styles for login page*/
        body { display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background-color: #e9ecef; }
        .login-box { background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .login-box h2 { text-align: center; color: #0056b3; margin-bottom: 20px; }
        .error-msg { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; text-align: center; margin-bottom: 15px; font-weight: bold; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>Admin Login</h2>
    
    <?php
    if (isset($_GET['error'])) {
        if ($_GET['error'] == 'invalid') {
            echo "<div class='error-msg'>Account or password is incorrect!</div>";
        } elseif ($_GET['error'] == 'access_denied') {
            echo "<div class='error-msg'>Please log in to your admin account first!</div>";
        } elseif ($_GET['error'] == 'timeout') {
            echo "<div class='error-msg'>The system has automatically logged you out due to prolonged inactivity and for security reasons. Please log in again.</div>";
        }
    }

    
    ?>

    <form action="../actions/process_login.php" method="POST">
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required placeholder="admin@mmu.edu.my">
        </div>
        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required placeholder="Please enter your password">
        </div>
        <button type="submit" class="btn-submit">Login</button>
    </form>
</div>

</body>
</html>