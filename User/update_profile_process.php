<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$uid = $_SESSION['uid'];
$phone = trim($_POST['phone_num'] ?? '');

$stmt = $conn->prepare("UPDATE user SET phone_num = ? WHERE uid = ?");
$stmt->bind_param("ss", $phone, $uid);
$stmt->execute();

if (!empty($_FILES['profile_pic']['name']) && is_uploaded_file($_FILES['profile_pic']['tmp_name'])) {
    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $original_name = $_FILES['profile_pic']['name'];
    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_ext, true)) {
        http_response_code(400);
        exit('Invalid image type');
    }

    $uploadDir = __DIR__ . '/../uploads/profile_pic/user/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $safe_uid = preg_replace('/[^A-Za-z0-9_-]/', '_', $uid);
    $filename = $safe_uid . '_' . time() . '.' . $ext;
    $target = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target)) {
        // Store project-root relative path. Profile page will convert it to ../uploads/... for browser display.
        $db_path = 'uploads/profile_pic/user/' . $filename;
        $stmt = $conn->prepare("UPDATE user SET profile_pic = ? WHERE uid = ?");
        $stmt->bind_param("ss", $db_path, $uid);
        $stmt->execute();
    } else {
        http_response_code(500);
        exit('Upload failed');
    }
}

http_response_code(200);
?>

