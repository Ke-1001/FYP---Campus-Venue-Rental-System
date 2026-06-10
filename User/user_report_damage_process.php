<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit;
}

$uid = $_SESSION['uid'];
$bid = intval($_POST['bid'] ?? 0);
$vid = trim($_POST['vid'] ?? '');
$damage_description = trim($_POST['damage_description'] ?? '');

if ($bid <= 0 || $vid === '' || $damage_description === '') {
    $_SESSION['error'] = "Please fill in all required fields.";
    header("Location: booking_details.php?bid=" . urlencode($bid));
    exit;
}

// Verify booking belongs to this user and is approved
$stmt = $conn->prepare("
    SELECT bid, uid, vid, status
    FROM booking
    WHERE bid = ? AND uid = ? AND vid = ?
    LIMIT 1
");
$stmt->bind_param("iis", $bid, $uid, $vid);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $_SESSION['error'] = "Booking not found.";
    header("Location: my_bookings.php");
    exit;
}

$booking = $result->fetch_assoc();
$stmt->close();

if (strtolower($booking['status']) !== 'approved') {
    $_SESSION['error'] = "Damage report is only available for approved bookings.";
    header("Location: booking_details.php?bid=" . urlencode($bid));
    exit;
}

// Prevent duplicate report
$check = $conn->prepare("
    SELECT report_id
    FROM damage_report
    WHERE bid = ? AND uid = ?
    LIMIT 1
");
$check->bind_param("ii", $bid, $uid);
$check->execute();
$check_result = $check->get_result();

if ($check_result && $check_result->num_rows > 0) {
    $_SESSION['error'] = "You have already submitted a damage report for this booking.";
    header("Location: booking_details.php?bid=" . urlencode($bid));
    exit;
}

$check->close();

$damage_photo = null;

// Handle image upload
if (isset($_FILES['damage_photo']) && $_FILES['damage_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['damage_photo']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "Photo upload failed.";
        header("Location: user_report_damage.php?bid=" . urlencode($bid));
        exit;
    }

    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
    $original_name = $_FILES['damage_photo']['name'];
    $tmp_name = $_FILES['damage_photo']['tmp_name'];
    $file_size = $_FILES['damage_photo']['size'];

    $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowed_extensions)) {
        $_SESSION['error'] = "Invalid photo format. Please upload JPG, PNG, or WEBP.";
        header("Location: user_report_damage.php?bid=" . urlencode($bid));
        exit;
    }

    if ($file_size > 5 * 1024 * 1024) {
        $_SESSION['error'] = "Photo is too large. Maximum size is 5MB.";
        header("Location: user_report_damage.php?bid=" . urlencode($bid));
        exit;
    }

    $upload_dir = "../uploads/damage_reports/";

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $new_filename = "damage_" . $bid . "_" . time() . "." . $extension;
    $target_path = $upload_dir . $new_filename;

    if (!move_uploaded_file($tmp_name, $target_path)) {
        $_SESSION['error'] = "Failed to save uploaded photo.";
        header("Location: user_report_damage.php?bid=" . urlencode($bid));
        exit;
    }

    $damage_photo = $new_filename;
}

$insert = $conn->prepare("
    INSERT INTO damage_report 
        (bid, uid, vid, damage_description, damage_photo, report_status)
    VALUES 
        (?, ?, ?, ?, ?, 'submitted')
");
$insert->bind_param("iisss", $bid, $uid, $vid, $damage_description, $damage_photo);

if ($insert->execute()) {
    $_SESSION['success'] = "Damage report submitted successfully.";
    header("Location: booking_details.php?bid=" . urlencode($bid));
    exit;
} else {
    $_SESSION['error'] = "Failed to submit damage report.";
    header("Location: user_report_damage.php?bid=" . urlencode($bid));
    exit;
}