<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Registration - CVBMS</title>

    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #f4f6f9;
            margin: 0;
            font-family: Arial;
        }

        .register-box {
            background: white;
            padding: 40px;
            border-radius: 8px;
            width: 350px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .register-box h2 {
            text-align: center;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }

        .error {
            color: red;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="register-box">
    <h2>Create Account</h2>

    <?php
    if (isset($_GET['error'])) {
        echo "<p class='error'>Registration failed. Try again.</p>";
    }
    ?>

    <form action="../actions/process_register.php" method="POST">
        <input type="text" name="full_name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Register</button>
    </form>

    <p style="text-align:center;">
        Already have an account? <a href="login.php">Login</a>
    </p>
</div>

</body>
</html>