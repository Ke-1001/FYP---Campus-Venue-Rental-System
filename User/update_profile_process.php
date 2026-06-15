<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['uid'])) {
    $uid = $_SESSION['uid'];
    $phone = $_POST['phone_num'];

    // Update phone
    $stmt = $conn->prepare("UPDATE user SET phone_num = ? WHERE uid = ?");
    $stmt->bind_param("ss", $phone, $uid);
    $stmt->execute();

    // Handle File Upload
    if (!empty($_FILES['profile_pic']['name'])) {
        // Ensure folder exists
        $uploadDir = 'uploads/'; 
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $filename = time() . "_" . basename($_FILES['profile_pic']['name']);
        $target = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target)) {
            // Update database with the path
            $stmt = $conn->prepare("UPDATE user SET profile_pic = ? WHERE uid = ?");
            $stmt->bind_param("ss", $target, $uid);
            $stmt->execute();
        }
    }
    http_response_code(200);
}
?>