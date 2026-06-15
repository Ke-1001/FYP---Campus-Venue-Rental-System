<?php
// File: actions/process_forgot_password.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/mailer.php';

$source_node = $_SERVER['HTTP_REFERER'] ?? '../login.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

    if (empty($email)) {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Security Fault: Invalid email vector.'];
        // 💡 升級：動態重定向至來源節點
        header("Location: " . $source_node);
        exit;
    }

    $conn->begin_transaction();

    try {
        $entity_name = null;
        $entity_found = false;
        $account_type = null;

        // A. Check Admin table
        $stmt = $conn->prepare("SELECT admin_name FROM admin WHERE email = ? AND status = 'active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $entity_name = $res->fetch_assoc()['admin_name'];
            $entity_found = true;
            $account_type = 'admin';
        }
        $stmt->close();

        // B. Check Staff table
        if (!$entity_found) {
            $stmt = $conn->prepare("SELECT staff_name FROM staff WHERE email = ? AND status = 'active'");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $entity_name = $res->fetch_assoc()['staff_name'];
                $entity_found = true;
                $account_type = 'staff';
            }
            $stmt->close();
        }

        // 💡 2. 條件式權杖發行 (Conditional Token Issuance)
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
            $reset_link = getAppBaseUrl() . "/admin/setup_password.php?token=" . $token;

            $subject = "Security Alert: CVBMS System Credential Recovery";
            $message = "Hello {$entity_name},\n\n" .
                       "A request to reconfigure the cryptographic credentials for your account has been initiated.\n\n" .
                       "To execute the reset protocol, please access the following secure link:\n\n" .
                       $reset_link . "\n\n" .
                       "Note: This authorization vector will expire in 1 hour. If you did not initiate this request, you may safely ignore this email.\n\n" .
                       "CVBMS Automated Security System";

            dispatchSystemEmail($email, $entity_name, $subject, $message);
        }

        $conn->commit();

        // 💡 4. 終端路由移轉 (Terminal Routing Shift)
        $_SESSION['recovery_email'] = $email;
        // 正常發送成功後，統一導向檢查信箱頁面
        header("Location: ../check_email.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'System Execution Fault. Please try again later.'];
        // 💡 升級：發生資料庫或 SMTP 例外時，動態重定向
        header("Location: " . $source_node);
        exit;
    }
} else {
    // 💡 升級：阻斷非 POST 請求時的動態重定向
    header("Location: " . $source_node);
    exit;
}