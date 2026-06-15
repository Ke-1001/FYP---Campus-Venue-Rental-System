<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['uid'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$uid = $_SESSION['uid'];
$phone = $_POST['phone_num'] ?? '';
$phone = preg_replace('/[^0-9]/', '', $phone);

$stmt = $conn->prepare("UPDATE user SET phone_num = ? WHERE uid = ?");
$stmt->bind_param("ss", $phone, $uid);
$stmt->execute();
$stmt->close();

if (!empty($_FILES['profile_pic']['name']) && is_uploaded_file($_FILES['profile_pic']['tmp_name'])) {
    $uploadDir = __DIR__ . '/../uploads/user/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $originalName = basename($_FILES['profile_pic']['name']);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($extension, $allowed, true)) {
        http_response_code(400);
        exit('Invalid image type');
    }

    $filename = 'user_' . preg_replace('/[^A-Za-z0-9_-]/', '', $uid) . '_' . time() . '.' . $extension;
    $target = $uploadDir . $filename;

    if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target)) {
        http_response_code(500);
        exit('Upload failed');
    }

    // Store only the filename. profile.php will resolve it from uploads/user/.
    $stmt = $conn->prepare("UPDATE user SET profile_pic = ? WHERE uid = ?");
    $stmt->bind_param("ss", $filename, $uid);
    $stmt->execute();
    $stmt->close();
}

http_response_code(200);
echo 'OK';
?>
