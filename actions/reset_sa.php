<?php
// File path: actions/reset_sa.php
require_once '../config/db.php';

// 1. 定義符合企業級安全矩陣的明文密碼 (包含大小寫、數字與特殊符號)
$raw_password = 'SuperAdmin@123';

// 2. 執行原生的密碼學雜湊運算
$hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

// 3. 強制部署至 Root 節點 (super_admin)
$sql = "UPDATE admin SET password = ? WHERE role = 'super_admin'";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("s", $hashed_password);
    if ($stmt->execute()) {
        echo "<div style='font-family: monospace; background: #0f172a; color: #10b981; padding: 20px; border-radius: 8px;'>";
        echo "<h3>[ SYSTEM OVERRIDE SUCCESSFUL ]</h3>";
        echo "<p>Root cryptographic key has been reset.</p>";
        echo "<p>Email: <b>SA@mmu.edu.my</b></p>";
        echo "<p>Password: <b>" . htmlspecialchars($raw_password) . "</b></p>";
        echo "<p style='color: #ef4444; margin-top: 20px;'>CRITICAL DIRECTIVE: Delete this file (reset_sa.php) immediately after logging in to prevent security breaches.</p>";
        echo "</div>";
    } else {
        echo "Database Fault: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "SQL Syntax Error: " . $conn->error;
}
$conn->close();
?>