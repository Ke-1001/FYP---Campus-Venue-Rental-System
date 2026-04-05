<?php
// 檔案路徑：includes/super_admin_auth.php

// 1. 先載入基礎門鎖 (確保他至少是個管理員，且處理了 Timeout 邏輯)
require_once 'admin_auth.php'; 

// 2. 嚴格檢查：如果不是 Super_Admin，直接踢出去！
if ($_SESSION['role'] !== 'Super_Admin') {
    echo "<script>
            alert('⛔ 權限不足 (Access Denied)：只有 Super Admin 能執行此操作！');
            window.history.back(); // 把他退回上一頁
          </script>";
    exit();
}
// 通過檢查，放行！
?>