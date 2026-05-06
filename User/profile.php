<?php
include("../includes/auth.php");
include("../includes/header.php");
include("../config/db.php");

$uid = $_SESSION['uid'];

$stmt = $conn->prepare("SELECT username, email, phone_num FROM users WHERE uid = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<h2 class="text-2xl font-bold mb-6">My Profile</h2>

<div class="bg-white rounded-xl shadow p-6 max-w-lg">

    <div class="space-y-3">

        <div class="flex justify-between">
            <span class="text-gray-500">Username</span>
            <span class="font-medium"><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></span>
        </div>

        <div class="flex justify-between">
            <span class="text-gray-500">Student ID</span>
            <span class="font-medium"><?php echo htmlspecialchars($uid); ?></span>
        </div>

        <div class="flex justify-between">
            <span class="text-gray-500">Email</span>
            <span class="font-medium"><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></span>
        </div>

        <div class="flex justify-between">
            <span class="text-gray-500">Phone</span>
            <span class="font-medium"><?php echo htmlspecialchars($user['phone_num'] ?? 'N/A'); ?></span>
        </div>

    </div>

    <div class="mt-6">
        <a href="edit_profile.php">
            <button class="px-5 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                Edit Profile
            </button>
        </a>
    </div>

</div>

</div></body></html>