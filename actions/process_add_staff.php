<?php
// File path: actions/process_add_staff.php
session_start();
require_once '../includes/admin_auth.php'; // 確保管理員權限
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 💡 1. 接收 Payload
    $staff_name = htmlspecialchars(trim($_POST['staff_name']));
    $email = trim($_POST['email']);
    $phone_num = htmlspecialchars(trim($_POST['phone_num']));
    $position = $_POST['position']; // 'inspector' 或 'manager'
    $password = $_POST['password'];

    // 2. 密碼複雜度安全檢測
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
    if (!preg_match($pattern, $password)) {
        $_SESSION['toast'] = [
            'type' => 'error', 
            'msg' => 'Security Fault: Password does not meet complexity standards.'
        ];
        header("Location: ../admin/add_staff.php");
        exit;
    }

    // 💡 3. 碰撞檢測 (Collision Detection)
    // 確保該 Email 尚未被其他工作人員註冊
    $sql_check = "SELECT sid FROM staff WHERE email = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $_SESSION['toast'] = [
            'type' => 'error', 
            'msg' => 'Conflict: Email address is already assigned to an existing staff member.'
        ];
        $stmt_check->close();
        header("Location: ../admin/add_staff.php");
        exit;
    }
    $stmt_check->close();

    // 4. 密碼雜湊
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // 💡 5. 寫入資料庫 (交由 DB AUTO_INCREMENT 處理 sid)
    $sql = "INSERT INTO staff (staff_name, email, password, phone_num, position) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // "sssss" 代表 5 個 String 變數
        $stmt->bind_param("sssss", $staff_name, $email, $password_hash, $phone_num, $position);
        if ($stmt->execute()) {
            
            // 提取 DB 原生生成的 9000+ 整數 ID
            $new_sid = $conn->insert_id; 
            
            $_SESSION['toast'] = [
                'type' => 'success', 
                'msg' => "Success: Staff member [ID: {$new_sid}] has been registered successfully."
            ];
            
            // 返回人事管理儀表板
            header("Location: ../admin/manage_admins.php");
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database Error: ' . $stmt->error];
            header("Location: ../admin/add_staff.php");
        }
        $stmt->close();
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'SQL Prepare Error: ' . $conn->error];
        header("Location: ../admin/add_staff.php");
    }
    exit;
} else {
    $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Invalid Request Method.'];
    header("Location: ../admin/manage_admins.php");
    exit;
}
?>