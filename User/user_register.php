<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Registration - CVBMS</title>

    <style>
        /* 原有架构严格保持不变 */
        body { display: flex; justify-content: center; align-items: center; height: 100vh; background: #f4f6f9; margin: 0; font-family: Arial; }
        .register-box { background: white; padding: 40px; border-radius: 8px; width: 350px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .register-box h2 { text-align: center; }
        input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; transition: border 0.3s; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; transition: background 0.3s; }
        button:disabled { background: #a0cbfc; cursor: not-allowed; }
        .error { color: red; text-align: center; }
        
        /* 密码与验证反馈的微型样式 */
        .pwd-rules { font-size: 12px; margin-top: -5px; margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px; border: 1px solid #e9ecef; }
        .rule-item { display: flex; align-items: center; margin-bottom: 4px; transition: color 0.2s; }
        .rule-item .icon { margin-right: 8px; font-weight: bold; }
        .rule-invalid { color: #adb5bd; }
        .rule-invalid .icon::before { content: '✗'; }
        .rule-valid { color: #28a745; }
        .rule-valid .icon::before { content: '✓'; }
        
        /* 🔴 针对异步验证的微交互样式 */
        .input-error { border: 2px solid #dc3545 !important; background-color: #fdf5f6; }
        .async-feedback { display: block; font-size: 11px; font-weight: bold; margin-top: -8px; margin-bottom: 10px; height: 14px; text-align: right; transition: color 0.3s; }
        .text-danger { color: #dc3545; }
        .text-success { color: #28a745; }
    </style>
</head>
<body>

<div class="register-box">
    <h2>Create Account</h2>

    <?php
    if (isset($_GET['error'])) {
        if ($_GET['error'] == 'exists') echo "<p class='error'>Registration blocked: Verification failed.</p>";
        else echo "<p class='error'>Registration failed. Try again.</p>";
    }
    ?>

    <form action="../User/user_register_process.php" method="POST" id="regForm">
        <!-- 🔴 为 UID 输入框绑定异步事件与反馈容器 -->
        <input type="text" name="uid" id="uid" placeholder="Student ID (e.g. 242DT2430C)" required autocomplete="off">
        <span id="uid-feedback" class="async-feedback"></span>

        <input type="text" name="username" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="phone_num" placeholder="Phone Number" required>
        
        <input type="password" name="password" id="password" placeholder="Password" required oninput="evaluateEntropy()">
        
        <div class="pwd-rules">
            <div id="rule-length" class="rule-item rule-invalid"><span class="icon"></span> Minimum 8 Characters</div>
            <div id="rule-upper" class="rule-item rule-invalid"><span class="icon"></span> 1 Uppercase (A-Z)</div>
            <div id="rule-lower" class="rule-item rule-invalid"><span class="icon"></span> 1 Lowercase (a-z)</div>
            <div id="rule-number" class="rule-item rule-invalid"><span class="icon"></span> 1 Number (0-9)</div>
            <div id="rule-special" class="rule-item rule-invalid"><span class="icon"></span> 1 Special Character (@$!%*?&)</div>
        </div>

        <button type="submit" id="submitBtn" disabled>Register</button>
    </form>

    <p style="text-align:center;">
        Already have an account? <a href="../User/user_login.php">Login</a>
    </p>
</div>

<script>
    // 状态锁矩阵: 必须同时满足 ID可用 与 密码高强度
    const stateLocks = {
        uidValid: false,
        pwdValid: false
    };

    // 🔴 核心逻辑 1：防抖机制 (Debouncer)
    function debounce(func, delay) {
        let timeoutId;
        return function (...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    }

    // 🔴 核心逻辑 2：异步探测执行器
    const checkUidAvailability = async (uid) => {
        const feedbackEl = document.getElementById('uid-feedback');
        const inputEl = document.getElementById('uid');
        const btn = document.getElementById('submitBtn');

        if (uid.trim().length < 3) {
            feedbackEl.textContent = '';
            inputEl.classList.remove('input-error');
            stateLocks.uidValid = false;
            updateMasterLock();
            return;
        }

        feedbackEl.textContent = 'Verifying...';
        feedbackEl.className = 'async-feedback text-success';

        try {
            const response = await fetch(`../actions/api_check_uid.php?uid=${encodeURIComponent(uid)}`);
            const data = await response.json();

            if (data.exists) {
                // 冲突发生 (Collision Detected)
                feedbackEl.textContent = '✗ Student ID is already registered.';
                feedbackEl.className = 'async-feedback text-danger';
                inputEl.classList.add('input-error');
                stateLocks.uidValid = false;
            } else {
                // ID 可用 (ID Available)
                feedbackEl.textContent = '✓ ID Available';
                feedbackEl.className = 'async-feedback text-success';
                inputEl.classList.remove('input-error');
                stateLocks.uidValid = true;
            }
        } catch (error) {
            feedbackEl.textContent = '⚠️ Network Error';
            feedbackEl.className = 'async-feedback text-danger';
            stateLocks.uidValid = false;
        }
        updateMasterLock();
    };

    // 绑定事件: 使用 500ms 延迟防抖
    document.getElementById('uid').addEventListener('input', debounce(function(e) {
        checkUidAvailability(e.target.value);
    }, 500));

    // 密码强度评估逻辑 (保留之前重构的版本)
    function evaluateEntropy() {
        const pwd = document.getElementById('password').value;
        const vectors = {
            length: pwd.length >= 8,
            upper: /[A-Z]/.test(pwd),
            lower: /[a-z]/.test(pwd),
            number: /\d/.test(pwd),
            special: /[@$!%*?&]/.test(pwd)
        };

        const renderState = (id, isValid) => {
            const el = document.getElementById(id);
            el.className = isValid ? 'rule-item rule-valid' : 'rule-item rule-invalid';
        };

        renderState('rule-length', vectors.length);
        renderState('rule-upper', vectors.upper);
        renderState('rule-lower', vectors.lower);
        renderState('rule-number', vectors.number);
        renderState('rule-special', vectors.special);

        stateLocks.pwdValid = Object.values(vectors).every(val => val === true);
        updateMasterLock();
    }

    // 联合评估: 决定最终提交按钮的解锁状态
    function updateMasterLock() {
        // 只有当 ID 验证通过 且 密码符合强度时，才允许提交
        document.getElementById('submitBtn').disabled = !(stateLocks.uidValid && stateLocks.pwdValid);
    }
</script>

</body>
</html>