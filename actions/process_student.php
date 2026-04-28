<?php
// File path: actions/process_student.php
session_start();
require_once '../includes/admin_auth.php'; 
require_once '../config/db.php';

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // === ENTITY REGISTRATION ===
    // 💡 適配新架構：接收 uid, username 與 phone_num
    $uid = htmlspecialchars(trim($_POST['uid']));
    $username = htmlspecialchars(trim($_POST['username']));
    $email = trim($_POST['email']);
    $phone_num = htmlspecialchars(trim($_POST['phone_num']));
    $password = $_POST['password'];

    // Invariant Check: Double Collision Detection (uid & email)
    // 💡 適配新架構：使用 user 表
    $sql_check = "SELECT uid FROM user WHERE email = ? OR uid = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ss", $email, $uid);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows > 0) {
        $_SESSION['toast'] = [
            'type' => 'error', 
            'msg' => 'Conflict: Student ID or Email identifier already exists within the system.'
        ];
        $stmt_check->close();
        header("Location: ../admin/manage_students.php");
        exit;
    }
    $stmt_check->close();

    // Cryptographic sequence implementation
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // 💡 適配新架構：寫入 user 表的 5 個欄位
    $sql = "INSERT INTO user (uid, username, email, password, phone_num) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("sssss", $uid, $username, $email, $password_hash, $phone_num);
        if ($stmt->execute()) {
            $_SESSION['toast'] = [
                'type' => 'success', 
                'msg' => 'Node Registered: Student entity successfully provisioned.'
            ];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Fault: ' . $stmt->error];
        }
        $stmt->close();
    }
    header("Location: ../admin/manage_students.php");
    exit;

} elseif ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // === CONFIGURATION UPDATE ===
    // 💡 適配新架構：主鍵改為字串 uid
    $uid = htmlspecialchars(trim($_POST['uid']));
    $username = htmlspecialchars(trim($_POST['username']));
    $email = trim($_POST['email']);
    $phone_num = htmlspecialchars(trim($_POST['phone_num']));

    // Invariant Check: Email Collision Detection (Excluding self)
    $sql_check = "SELECT uid FROM user WHERE email = ? AND uid != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ss", $email, $uid);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows > 0) {
        $_SESSION['toast'] = [
            'type' => 'error', 
            'msg' => 'Update Blocked: Email identifier overlaps with an existing entity.'
        ];
        $stmt_check->close();
        header("Location: ../admin/manage_students.php");
        exit;
    }
    $stmt_check->close();

    // 💡 適配新架構：更新 user 表，無需再檢查 role
    $sql = "UPDATE user SET username = ?, email = ?, phone_num = ? WHERE uid = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssss", $username, $email, $phone_num, $uid);
        if ($stmt->execute()) {
            $_SESSION['toast'] = [
                'type' => 'success', 
                'msg' => 'Configuration Deployed: Student profile updated.'
            ];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Fault: ' . $stmt->error];
        }
        $stmt->close();
    }
    header("Location: ../admin/manage_students.php");
    exit;

} elseif ($action === 'delete' && isset($_GET['uid'])) {
    // === REVOCATION PROTOCOL ===
    // 💡 適配新架構：GET 參數改為 uid
    $uid = htmlspecialchars(trim($_GET['uid']));

    // Verify entity exists
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

    // Execute deletion (Database ON DELETE CASCADE handles dependent bookings)
    // 💡 適配新架構：刪除 user 表紀錄
    $sql = "DELETE FROM user WHERE uid = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $uid);
        if ($stmt->execute()) {
            $_SESSION['toast'] = [
                'type' => 'success', 
                'msg' => 'Node Terminated: Student record and dependent datasets eradicated.'
            ];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Deletion Fault: ' . $stmt->error];
        }
        $stmt->close();
    }
    header("Location: ../admin/manage_students.php");
    exit;

} else {
    // Exception Fallback
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Malformed request vector.'];
    header("Location: ../admin/manage_students.php");
    exit;
}
?>