<?php
// File: user/user_login.php
session_start();

// 💡 检查 uid 是否存在，若已登录则导向首页
if (isset($_SESSION['uid']) && isset($_SESSION['role']) && $_SESSION['role'] === 'user') {
    header("Location: homepage.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Login - MMU Venue Booking</title>
    <style>
        /* Base styles maintained exactly for layout consistency */
        body { display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background-color: #f8fafc; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        .login-box { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); width: 100%; max-width: 400px; border: 1px solid #e2e8f0; }
        .login-box h2 { text-align: center; color: #0f172a; margin-bottom: 24px; font-weight: 800; font-size: 1.5rem; }
        .error-msg { color: #b91c1c; background: #fef2f2; padding: 12px; border-radius: 6px; text-align: center; margin-bottom: 20px; font-size: 0.875rem; font-weight: 600; border: 1px solid #f87171; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; }
        .form-group input { width: 100%; padding: 0.75rem 1rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.875rem; outline: none; transition: border-color 0.2s, box-shadow 0.2s; box-sizing: border-box; }
        .form-group input:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        .btn-submit { width: 100%; padding: 0.75rem; background-color: #4f46e5; color: #ffffff; border: none; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 700; cursor: pointer; transition: background-color 0.2s; margin-top: 1rem; }
        .btn-submit:hover { background-color: #4338ca; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>Student Login</h2>
    
    <?php
    if (isset($_GET['error'])) {
        if ($_GET['error'] == 'invalid') {
            echo "<div class='error-msg'>Invalid ID/Email or password.</div>";
        } elseif ($_GET['error'] == 'access_denied') {
            echo "<div class='error-msg'>Please log in to continue.</div>";
        } elseif ($_GET['error'] == 'timeout') {
            echo "<div class='error-msg'>Session expired. Please log in again.</div>";
        }
    }
    ?>

    <form action="user_login_process.php" method="POST">
        <div class="form-group">
            <label>Student ID / Email</label>
            <input type="text" name="login_identifier" required placeholder="e.g. 242DT2430C or email">
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="••••••••">
        </div>

        <button type="submit" class="btn-submit">Login</button>
    </form>

    <p style="text-align:center;">
        Don't have an account? <a href="../User/user_register.php">Register</a>
    </p>
</div>

</body>
</html>