<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php?error=access_denied");
    exit();
}

$user_id = $_SESSION['user_id'];

// FETCH CURRENT DATA
$sql = "SELECT full_name, email FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// UPDATE PROFILE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);

    $update = "UPDATE users SET full_name = ?, email = ? WHERE user_id = ?";
    $stmt = $conn->prepare($update);
    $stmt->bind_param("ssi", $full_name, $email, $user_id);

    if ($stmt->execute()) {
        header("Location: profile.php?updated=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
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

        .edit-box{
            background:white;
            width:450px;
            padding:40px;
            border-radius:12px;
            box-shadow:0 10px 30px rgba(0,0,0,0.2);
        }

        h2{
            margin-bottom:25px;
        }

        input{
            width:100%;
            padding:12px;
            margin:10px 0 20px;
            border-radius:6px;
            border:1px solid #ccc;
        }

        button{
            padding:10px 18px;
            background:#4e73df;
            color:white;
            border:none;
            border-radius:6px;
            cursor:pointer;
            font-weight:600;
        }
    </style>
</head>
<body>

<div class="edit-box">
    <h2>Edit Profile</h2>

    <form method="POST">
        <label>Full Name</label>
        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

        <button type="submit">Save Changes</button>
    </form>
</div>

</body>
</html>