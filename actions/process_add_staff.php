<?php
// File path: actions/process_add_staff.php
session_start();
require_once '../includes/admin_auth.php'; // 確保管理員權限
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 💡 1. 嚴格擷取 Payload (對齊前端 name 屬性)
    $full_name = htmlspecialchars(trim($_POST['full_name'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $phone_num = htmlspecialchars(trim($_POST['phone_num'] ?? ''));
    $access_level = $_POST['access_level'] ?? '';
    $password = $_POST['password'] ?? '';

    // 基礎防呆
    if (empty($full_name) || empty($email) || empty($access_level) || empty($password)) {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Validation Fault: Essential data vectors cannot be null.'];
        header("Location: ../admin/add_staff.php");
        exit;
    }

    // 2. 密碼複雜度安全檢測
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
    if (!preg_match($pattern, $password)) {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Security Fault: Password does not meet complexity standards.'];
        header("Location: ../admin/add_staff.php");
        exit;
    }

    $conn->begin_transaction();

    try {
        // 💡 3. 全域碰撞檢測 (Global Collision Detection: 掃描 admin 與 staff 表)
        $sql_check_admin = "SELECT aid FROM admin WHERE email = ?";
        $stmt_chk_a = $conn->prepare($sql_check_admin);
        $stmt_chk_a->bind_param("s", $email);
        $stmt_chk_a->execute();
        if ($stmt_chk_a->get_result()->num_rows > 0) throw new Exception("Conflict: Email is already registered as an Admin.");
        $stmt_chk_a->close();

        $sql_check_staff = "SELECT sid FROM staff WHERE email = ?";
        $stmt_chk_s = $conn->prepare($sql_check_staff);
        $stmt_chk_s->bind_param("s", $email);
        $stmt_chk_s->execute();
        if ($stmt_chk_s->get_result()->num_rows > 0) throw new Exception("Conflict: Email is already assigned to a Staff member.");
        $stmt_chk_s->close();

        // 4. 密碼雜湊
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $new_id = 0;

        // 💡 5. 智慧雙軌路由寫入 (Dual-Track Routing)
        if (in_array($access_level, ['super_admin', 'admin'])) {
            $sql = "INSERT INTO admin (admin_name, email, password, phone_num, role) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $full_name, $email, $password_hash, $phone_num, $access_level);
            $stmt->execute();
            $new_id = $conn->insert_id;
            $stmt->close();
            $msg = "Success: Management personnel [AID: {$new_id}] initialized as {$access_level}.";

        } elseif ($access_level === 'inspector') {
            $sql = "INSERT INTO staff (staff_name, email, password, phone_num, position) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $full_name, $email, $password_hash, $phone_num, $access_level);
            $stmt->execute();
            $new_id = $conn->insert_id;
            $stmt->close();
            $msg = "Success: Field personnel [SID: {$new_id}] initialized as inspector.";
            
        } else {
            throw new Exception("Security Exception: Unknown authorization level parameter.");
        }

        $conn->commit();
        $_SESSION['toast'] = ['type' => 'success', 'msg' => $msg];
        header("Location: ../admin/staff_directory.php"); // 導向全域通訊錄

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast'] = ['type' => 'error', 'msg' => $e->getMessage()];
        header("Location: ../admin/add_staff.php");
    }
    exit;
} else {
    header("Location: ../admin/staff_directory.php");
    exit;
}