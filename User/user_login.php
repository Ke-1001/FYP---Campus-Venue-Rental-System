<?php
session_start();

// If user is already logged in, redirect to user homepage
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'User') {
    header("Location: home.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Login - CVBMS</title>
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <style>
        /* Exclusive styles for login page */
        body { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
            background-color: #e9ecef; 
        }

        .login-box { 
            background: #fff; 
            padding: 40px; 
            border-radius: 8px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); 
            width: 100%; 
            max-width: 400px; 
        }

        .login-box h2 { 
            text-align: center; 
            color: #0056b3; 
            margin-bottom: 20px; 
        }

        .error-msg { 
            color: #dc3545; 
            background: #f8d7da; 
            padding: 10px; 
            border-radius: 4px; 
            text-align: center; 
            margin-bottom: 15px; 
            font-weight: bold; 
        }
    </style>
</head>
<body>

<div class="login-box">
    <h2>User Login</h2>
    
    <?php
    if (isset($_GET['error'])) {
        if ($_GET['error'] == 'invalid') {
            echo "<div class='error-msg'>Email or password is incorrect!</div>";
        } elseif ($_GET['error'] == 'access_denied') {
            echo "<div class='error-msg'>Please log in to your account first!</div>";
        } elseif ($_GET['error'] == 'timeout') {
            echo "<div class='error-msg'>You have been logged out due to inactivity. Please log in again.</div>";
        }
    }
    ?>

    <form action="../User/user_login_process.php" method="POST">
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required placeholder="user@example.com">
        </div>

        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required placeholder="Enter your password">
        </div>

        <button type="submit" class="btn-submit">Login</button>
    </form>
</div>

</body>
</html>