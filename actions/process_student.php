<?php
// File path: actions/process_student.php
session_start();
require_once '../includes/admin_auth.php'; 
require_once '../config/db.php';

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // === ENTITY REGISTRATION ===
    $full_name = htmlspecialchars(trim($_POST['full_name']));
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = 'User';

    // Invariant Check: Email Collision Detection
    $sql_check = "SELECT user_id FROM users WHERE email = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows > 0) {
        $_SESSION['toast'] = [
            'type' => 'error', 
            'msg' => 'Conflict: Email identifier already exists within the system.'
        ];
        $stmt_check->close();
        header("Location: ../admin/manage_students.php");
        exit;
    }
    $stmt_check->close();

    // Cryptographic sequence implementation
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssss", $full_name, $email, $password_hash, $role);
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
    $user_id = intval($_POST['user_id']);
    $full_name = htmlspecialchars(trim($_POST['full_name']));
    $email = trim($_POST['email']);

    // Invariant Check: Email Collision Detection (Excluding self)
    $sql_check = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("si", $email, $user_id);
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

    // Enforce role condition to prevent unauthorized modification of admin nodes
    $sql = "UPDATE users SET full_name = ?, email = ? WHERE user_id = ? AND (role = 'User' OR role = '')";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssi", $full_name, $email, $user_id);
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

} elseif ($action === 'delete' && isset($_GET['id'])) {
    // === REVOCATION PROTOCOL ===
    $user_id = intval($_GET['id']);

    // Pre-execution Logic Gate: Verify target is strictly a 'User'
    $sql_check = "SELECT role FROM users WHERE user_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        $target = $result->fetch_assoc();
        if ($target['role'] === 'Normal_Admin' || $target['role'] === 'Super_Admin') {
            $_SESSION['toast'] = [
                'type' => 'error', 
                'msg' => 'Violation: Administrative nodes cannot be terminated via the student directory.'
            ];
            $stmt_check->close();
            header("Location: ../admin/manage_students.php");
            exit;
        }
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Entity not found.'];
        header("Location: ../admin/manage_students.php");
        exit;
    }
    $stmt_check->close();

    // Execute deletion (Database ON DELETE CASCADE handles dependent bookings)
    $sql = "DELETE FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
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