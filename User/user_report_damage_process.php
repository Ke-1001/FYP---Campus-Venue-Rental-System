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
$severity = trim($_POST['severity'] ?? 'Level 1');
$user_action = trim($_POST['user_action'] ?? 'continue');

if ($bid <= 0 || $vid === '' || $damage_description === '') {
    $_SESSION['error'] = "Please fill in all required fields.";
    header("Location: booking_details.php?bid=" . urlencode($bid));
    exit;
}

// 防篡改校验：如果不是 Level 3，绝对不允许触发 cancel 逻辑
if ($severity !== 'Level 3') {
    $user_action = 'continue';
}

$stmt = $conn->prepare("SELECT bid, uid, vid, status FROM booking WHERE bid = ? AND uid = ? AND vid = ? LIMIT 1");
$stmt->bind_param("iss", $bid, $uid, $vid);
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

$check = $conn->prepare("SELECT report_id FROM damage_report WHERE bid = ? AND uid = ? LIMIT 1");
$check->bind_param("is", $bid, $uid);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $_SESSION['error'] = "You have already submitted a damage report for this booking.";
    header("Location: booking_details.php?bid=" . urlencode($bid));
    exit;
}
$check->close();

$damage_photo = null;
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

    if (!in_array($extension, $allowed_extensions) || $file_size > 5 * 1024 * 1024) {
        $_SESSION['error'] = "Invalid photo format or size exceeded 5MB.";
        header("Location: user_report_damage.php?bid=" . urlencode($bid));
        exit;
    }

    $upload_dir = "../uploads/damage_reports/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $new_filename = "damage_" . $bid . "_" . time() . "." . $extension;
    if (move_uploaded_file($tmp_name, $upload_dir . $new_filename)) {
        $damage_photo = $new_filename;
    }
}

// 【关键设计】字符串拼接基准线：规避 Alter Table，同时向 Admin 传递分类信息
$final_description = "[" . $severity . "] " . $damage_description;

$insert = $conn->prepare("INSERT INTO damage_report (bid, uid, vid, damage_description, damage_photo, report_status) VALUES (?, ?, ?, ?, ?, 'submitted')");
$insert->bind_param("issss", $bid, $uid, $vid, $final_description, $damage_photo);

if ($insert->execute()) {
    
    // 状态分支机 (Bifurcated State Logic)
    if ($severity === 'Level 3' && $user_action === 'cancel') {
        // 分支 A: 阻碍性损坏且要求退款
        $update_booking = $conn->prepare("
            UPDATE booking
            SET status = 'cancelled', 
                cancel_reason = 'USER_REPORTED_CRITICAL_DAMAGE',
                cancelled_at = NOW()
            WHERE bid = ? AND uid = ? AND status = 'approved'
        ");
        $update_booking->bind_param("is", $bid, $uid);
        $update_booking->execute();
        $update_booking->close();
        
        $_SESSION['success'] = "Critical damage reported. Your booking has been cancelled and flagged for Admin review/refund.";
    } else {
        // 分支 B: 非阻碍性损坏，或阻碍性损坏但用户选择继续使用
        // 核心修复：移除原代码中的 UPDATE booking SET status = 'completed'，维持 'approved' 状态不变
        $_SESSION['success'] = "Damage report recorded as a baseline waiver. You may continue using the venue.";
    }

    header("Location: booking_details.php?bid=" . urlencode($bid));
    exit;
} else {
    $_SESSION['error'] = "Failed to submit damage report.";
    header("Location: user_report_damage.php?bid=" . urlencode($bid));
    exit;
}