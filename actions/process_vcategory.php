<?php
// File: actions/process_vcategory.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../core/repositories/CategoryRepository.php';

use Core\Repositories\CategoryRepository;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin/manage_vcategory.php");
    exit;
}

$action = strtolower($_POST['action'] ?? '');
$categoryRepo = new CategoryRepository($conn);

// ∴ 狀態機判定與路由分配
try {
    if ($action === 'create') {
        $cat = trim($_POST['category'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        
        if (empty($cat) || empty($desc)) throw new \Exception("Parameters missing.");
        
        // ∴ 無需傳入 vcid
        if ($categoryRepo->createCategory($cat, $desc)) {
            $_SESSION['success'] = "Category Identity successfully registered.";
        } else {
            throw new \Exception("Database execution fault. Name may already exist.");
        }

    } elseif ($action === 'update') {
        $vcid = (int)($_POST['vcid'] ?? 0);
        $cat = trim($_POST['category'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        
        if ($vcid <= 0 || empty($cat) || empty($desc)) throw new \Exception("Parameters missing or corrupted.");
        
        if ($categoryRepo->updateCategory($vcid, $cat, $desc)) {
            $_SESSION['success'] = "Category Node [ID: {$vcid}] synchronized.";
        } else {
            throw new \Exception("Synchronization fault.");
        }

    // 替換 process_vcategory.php 中的 bulk_delete 分支
    } elseif ($action === 'bulk_delete') {
        // ∴ [修正] 將 selected_vids 對齊為前端標準的 ids 鍵名
        $vcids = $_POST['ids'] ?? [];
        if (empty($vcids)) throw new \Exception("No entities selected for purge.");
        
        if ($categoryRepo->deleteCategories($vcids)) {
            $_SESSION['success'] = "Selected Category Nodes successfully purged.";
        } else {
            // 異常將由 Repository 拋出，此處的 generic error 可保留作為最後防線
            throw new \Exception("Purge execution fault. Relational constraints may exist.");
        }
    } else {
        throw new \Exception("Unknown execution vector.");
    }
} catch (\Exception $e) {
    $_SESSION['error'] = "System Fault: " . $e->getMessage();
}

// 完成操作後重定向回混合介面
header("Location: ../admin/manage_vcategory.php");
exit;
?>