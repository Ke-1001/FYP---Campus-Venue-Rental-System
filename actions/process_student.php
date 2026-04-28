<?php
// File path: actions/process_student.php
session_start();
require_once '../includes/admin_auth.php'; 
require_once '../config/db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // === ENTITY REGISTRATION (Natural Key Parsing) ===
    $uid = htmlspecialchars(trim($_POST['uid'])); // 💡 必須接收真實學號
    $username = htmlspecialchars(trim($_POST['username'] ?? $_POST['full_name'] ?? ''));
    $email = trim($_POST['email']);
    $phone_num = htmlspecialchars(trim($_POST['phone_num'] ?? ''));
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    if (empty($uid)) {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Registration Halted: Student ID (Natural Key) is missing.'];
        header("Location: ../admin/manage_students.php");
        exit;
    }

    // Invariant Check: Double Collision Detection
    $sql_check = "SELECT uid FROM user WHERE email = ? OR uid = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ss", $email, $uid);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows > 0) {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Conflict: Student ID or Email already exists.'];
        $stmt_check->close();
        header("Location: ../admin/manage_students.php");
        exit;
    }
    $stmt_check->close();

    // 💡 寫入 user 表 (顯式指派 uid)
    $sql = "INSERT INTO user (uid, username, email, password, phone_num) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("sssss", $uid, $username, $email, $password_hash, $phone_num);
        if ($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Student entity registered successfully.'];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Fault: ' . $stmt->error];
        }
        $stmt->close();
    }
    header("Location: ../admin/manage_students.php");
    exit;

} elseif ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // === CONFIGURATION UPDATE ===
    $uid = htmlspecialchars(trim($_POST['uid'] ?? $_POST['user_id'] ?? '')); // VARCHAR
    $username = htmlspecialchars(trim($_POST['username'] ?? $_POST['full_name'] ?? ''));
    $email = trim($_POST['email']);
    $phone_num = htmlspecialchars(trim($_POST['phone_num'] ?? ''));

    // Email Collision Detection (Excluding self)
    $sql_check = "SELECT uid FROM user WHERE email = ? AND uid != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ss", $email, $uid);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows > 0) {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Update Blocked: Email overlaps with an existing entity.'];
        $stmt_check->close();
        header("Location: ../admin/manage_students.php");
        exit;
    }
    $stmt_check->close();

    // 💡 更新 user 表 (無需 Role Check，實體已隔離)
    $sql = "UPDATE user SET username = ?, email = ?, phone_num = ? WHERE uid = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssss", $username, $email, $phone_num, $uid);
        if ($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Student profile updated.'];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Fault: ' . $stmt->error];
        }
        $stmt->close();
    }
    header("Location: ../admin/manage_students.php");
    exit;

} elseif ($action === 'delete') {
    // === REVOCATION PROTOCOL ===
    $uid = htmlspecialchars(trim($_GET['uid'] ?? $_GET['id'] ?? '')); // VARCHAR

    $sql_check = "SELECT uid FROM user WHERE uid = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $uid);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Entity not found.'];
        $stmt_check->close();
        header("Location: ../admin/manage_students.php");
        exit;
    }
    $stmt_check->close();

    $sql = "DELETE FROM user WHERE uid = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $uid); // "s"
        if ($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Student record eradicated.'];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Deletion Fault: ' . $stmt->error];
        }
        $stmt->close();
    }
    header("Location: ../admin/manage_students.php");
    exit;

} else {
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Malformed request vector.'];
    header("Location: ../admin/manage_students.php");
    exit;
}
?>