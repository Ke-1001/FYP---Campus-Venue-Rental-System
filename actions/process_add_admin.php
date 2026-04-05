<?php
// 檔案路徑：actions/process_add_admin.php
require_once '../includes/super_admin_auth.php'; // 🔒 雙重保險：API 接口也鎖上
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = htmlspecialchars(trim($_POST['full_name']));
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];

    // 💡 1. 企業級安全：後端密碼強度正規表達式檢查 (Regex)
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
    
    if (!preg_match($pattern, $password)) {
        die("<script>
                alert('🚨 資安警告：密碼未達企業強度規範，拒絕寫入資料庫！');
                window.history.back();
             </script>");
    }

    // 2. 檢查 Email 是否已經被註冊過了
    $sql_check = "SELECT user_id FROM users WHERE email = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
         die("<script>alert('❌ 此 Email 已被註冊！'); window.history.back();</script>");
    }
    $stmt_check->close();

    // 💡 3. 密碼加密 (Hashing) - 絕對不存明文！
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // 4. 寫入資料庫
    $sql = "INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssss", $full_name, $email, $password_hash, $role);
        if ($stmt->execute()) {
            echo "<script>
                    alert('✅ 成功新增管理員！');
                    window.location.href = '../admin/add_admin.php';
                  </script>";
        } else {
            echo "資料庫寫入失敗：" . $stmt->error;
        }
        $stmt->close();
    }
}
$conn->close();
?>