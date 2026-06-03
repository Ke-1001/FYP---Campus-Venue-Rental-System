<?php
// File: actions/process_forgot_password.php
session_start();
require_once '../config/db.php';
require_once '../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

    if (empty($email)) {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Security Fault: Invalid email vector.'];
        header("Location: ../admin/forgot_password.php");
        exit;
    }

    $conn->begin_transaction();

    try {
        // 💡 1. 跨表實體解析 (Polymorphic Resolution)
        $entity_name = null;
        $entity_found = false;

        // A. 檢查 Admin 表
        $stmt = $conn->prepare("SELECT admin_name FROM admin WHERE email = ? AND status = 'active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $entity_name = $res->fetch_assoc()['admin_name'];
            $entity_found = true;
        }
        $stmt->close();

        // B. 檢查 Staff 表
        if (!$entity_found) {
            $stmt = $conn->prepare("SELECT staff_name FROM staff WHERE email = ? AND status = 'active'");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $entity_name = $res->fetch_assoc()['staff_name'];
                $entity_found = true;
            }
            $stmt->close();
        }

        // C. 檢查 Student (User) 表
        if (!$entity_found) {
            $stmt = $conn->prepare("SELECT username FROM user WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $entity_name = $res->fetch_assoc()['username'];
                $entity_found = true;
            }
            $stmt->close();
        }

        // 💡 2. 條件式權杖發行 (Conditional Token Issuance)
        // 為了防禦信箱枚舉攻擊，若找不到實體，我們依然跳轉回傳「成功」訊息，但不執行實質 SQL 與 SMTP。
        if ($entity_found) {
            // 發行非對稱時效性權杖
            $token = bin2hex(random_bytes(32)); 
            $token_hash = hash('sha256', $token); 

            // 清除該信箱舊有的失效權杖，防止資料庫膨脹
            $stmt_clean = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt_clean->bind_param("s", $email);
            $stmt_clean->execute();
            $stmt_clean->close();

            // 寫入新權杖，設定 1 小時 TTL (採用 MySQL DATE_ADD 對齊時區)
            $stmt_token = $conn->prepare("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
            $stmt_token->bind_param("ss", $email, $token_hash);
            $stmt_token->execute();
            $stmt_token->close();

            // 💡 3. SMTP 派發協議
            $app_domain = "http://localhost/FYP---Campus-Venue-Rental-System"; 
            $reset_link = $app_domain . "/admin/setup_password.php?token=" . $token;

            $subject = "Security Alert: MMU System Credential Recovery";
            $message = "Hello {$entity_name},\n\n" .
                       "A request to reconfigure the cryptographic credentials for your account has been initiated.\n\n" .
                       "To execute the reset protocol, please access the following secure link:\n\n" .
                       $reset_link . "\n\n" .
                       "Note: This authorization vector will expire in 1 hour. If you did not initiate this request, you may safely ignore this email.\n\n" .
                       "MMU Automated Security System";

            dispatchSystemEmail($email, $entity_name, $subject, $message);
        }

        $conn->commit();

        // 💡 4. 終端路由移轉 (Terminal Routing Shift)
        // 廢除 Toast，將目標信箱注入短暫的 Session，並引導至明確的信箱檢查視圖
        $_SESSION['recovery_email'] = $email;
        header("Location: ../check_email.php");

    } catch (Exception $e) {
        $conn->rollback();
        // 將底層異常轉換為模糊的系統警告，防止架構暴露
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'System Execution Fault. Please try again later.'];
        header("Location: ../forgot_password.php");
    }
    exit;
} else {
    header("Location: ../forgot_password.php");
    exit;
}