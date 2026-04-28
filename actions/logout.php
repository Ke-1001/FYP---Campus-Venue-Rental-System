<?php
// File path: actions/logout.php
session_start();
$_SESSION = array(); // 清空記憶體向量
session_destroy();   // 銷毀 Session 實體
header("Location: ../admin/login.php");
exit;
?>