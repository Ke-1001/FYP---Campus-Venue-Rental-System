<?php
include("../includes/auth.php");
include("../includes/header.php");
include("../config/db.php");

$uid = $_SESSION['uid'];
$message = "";

// 处理更新
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone_num']);

    if (empty($username) || empty($email)) {
        $message = "<p class='text-red-500 mb-3'>Username and Email are required</p>";
    } else {

        $stmt = $conn->prepare("
            UPDATE users 
            SET username = ?, email = ?, phone_num = ?
            WHERE uid = ?
        ");
        $stmt->bind_param("sssi", $username, $email, $phone, $uid);
        $stmt->execute();

        // 更新 session
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['phone_num'] = $phone;

        $message = "<p class='text-green-500 mb-3'>Profile updated successfully</p>";
    }
}

// 读取当前数据
$stmt = $conn->prepare("SELECT username, email, phone_num FROM users WHERE uid = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<h2 class="text-2xl font-bold mb-6">Edit Profile</h2>

<div class="bg-white rounded-xl shadow p-6 max-w-lg">

    <?php echo $message; ?>

    <form method="POST" class="space-y-4">

        <div>
            <label class="block text-sm text-gray-600 mb-1">Username</label>
            <input type="text" name="username"
                value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring focus:ring-blue-200"
                required>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Email</label>
            <input type="email" name="email"
                value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring focus:ring-blue-200"
                required>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Phone</label>
            <input type="text" name="phone_num"
                value="<?php echo htmlspecialchars($user['phone_num'] ?? ''); ?>"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring focus:ring-blue-200">
        </div>

        <button type="submit"
            class="w-full py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
            Update Profile
        </button>

    </form>

</div>

</div></body></html>