<?php
// 檔案路徑：admin/add_admin.php
require_once '../includes/super_admin_auth.php'; // 🔒 裝上超級門鎖
require_once '../config/db.php';
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>人事管理 (Staff Management) - CVBMS</title>
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <style>
        /* 密碼強度提示字元的樣式 */
        .pwd-req { font-size: 13px; color: #dc3545; display: block; margin-top: 5px; }
        .pwd-req.valid { color: #28a745; } /* 達成條件會變綠色 */
    </style>
</head>
<body>

<div class="center-container" style="background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-top: 40px; max-width: 500px;">
    <h2>👥 新增管理員 (Add Administrator)</h2>
    <p>僅 Super Admin 可授權新管理員。請確保密碼符合企業級強度規範。</p>
    
    <form action="../actions/process_add_admin.php" method="POST" id="addAdminForm">
        <div class="form-group">
            <label>管理員全名 (Full Name):</label>
            <input type="text" name="full_name" required>
        </div>
        
        <div class="form-group">
            <label>電子郵件 (Email):</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>職級分配 (Role):</label>
            <select name="role" required>
                <option value="Normal_Admin">普通管理員 (Normal Admin)</option>
                <option value="Super_Admin">超級管理員 (Super Admin)</option>
            </select>
        </div>

        <div class="form-group">
            <label>設定密碼 (Password):</label>
            <input type="password" name="password" id="password" required onkeyup="checkPasswordStrength()">
            
            <div id="pwd-rules" style="margin-top: 10px; background: #f8f9fa; padding: 10px; border-radius: 4px;">
                <span class="pwd-req" id="rule-length">❌ 至少 8 個字元</span>
                <span class="pwd-req" id="rule-upper">❌ 包含大寫字母 (A-Z)</span>
                <span class="pwd-req" id="rule-lower">❌ 包含小寫字母 (a-z)</span>
                <span class="pwd-req" id="rule-number">❌ 包含數字 (0-9)</span>
                <span class="pwd-req" id="rule-special">❌ 包含特殊符號 (@$!%*?&)</span>
            </div>
        </div>

        <button type="submit" class="btn-submit" id="submitBtn" disabled style="background-color: #6c757d;">🔐 註冊帳號 (Register)</button>
    </form>
    
    <a href="manage_bookings.php" class="back-link">← 返回儀表板</a>
</div>

<script>
function checkPasswordStrength() {
    const pwd = document.getElementById('password').value;
    const btn = document.getElementById('submitBtn');
    
    // 正則表達式條件檢查
    const hasLength = pwd.length >= 8;
    const hasUpper = /[A-Z]/.test(pwd);
    const hasLower = /[a-z]/.test(pwd);
    const hasNumber = /\d/.test(pwd);
    const hasSpecial = /[@$!%*?&]/.test(pwd);

    // 更新 UI 狀態 (打叉變打勾，紅變綠)
    document.getElementById('rule-length').innerHTML = hasLength ? '✅ 至少 8 個字元' : '❌ 至少 8 個字元';
    document.getElementById('rule-length').className = hasLength ? 'pwd-req valid' : 'pwd-req';

    document.getElementById('rule-upper').innerHTML = hasUpper ? '✅ 包含大寫字母 (A-Z)' : '❌ 包含大寫字母 (A-Z)';
    document.getElementById('rule-upper').className = hasUpper ? 'pwd-req valid' : 'pwd-req';

    document.getElementById('rule-lower').innerHTML = hasLower ? '✅ 包含小寫字母 (a-z)' : '❌ 包含小寫字母 (a-z)';
    document.getElementById('rule-lower').className = hasLower ? 'pwd-req valid' : 'pwd-req';

    document.getElementById('rule-number').innerHTML = hasNumber ? '✅ 包含數字 (0-9)' : '❌ 包含數字 (0-9)';
    document.getElementById('rule-number').className = hasNumber ? 'pwd-req valid' : 'pwd-req';

    document.getElementById('rule-special').innerHTML = hasSpecial ? '✅ 包含特殊符號 (@$!%*?&)' : '❌ 包含特殊符號 (@$!%*?&)';
    document.getElementById('rule-special').className = hasSpecial ? 'pwd-req valid' : 'pwd-req';

    // 如果全部符合，才解鎖 Submit 按鈕
    if (hasLength && hasUpper && hasLower && hasNumber && hasSpecial) {
        btn.disabled = false;
        btn.style.backgroundColor = '#28a745'; // 變綠色
    } else {
        btn.disabled = true;
        btn.style.backgroundColor = '#6c757d'; // 變灰色
    }
}
</script>

</body>
</html>