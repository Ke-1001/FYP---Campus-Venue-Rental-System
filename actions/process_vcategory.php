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

// Check status and choose route
try {
    if ($action === 'create') {
        $cat = trim($_POST['category'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        
        if (empty($cat) || empty($desc)) throw new \Exception("Parameters missing.");
        
 // No need to pass vcid
        if ($categoryRepo->createCategory($cat, $desc)) {
            $_SESSION['success'] = "Category Identity successfully registered.";
        } else {
            throw new \Exception("Database execution fault. Name may already exist.");
        }

    } elseif ($action === 'update') {
        $vcid = (int)($_POST['vcid'] ?? 0);
        $cat = trim($_POST['category'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        
        if ($vcid <= 0 || empty($cat) || empty($desc)) throw new \Exception("Parameters missing or corrupted.");  if ($categoryRepo->updateCategory($vcid, $cat, $desc)) {
            $_SESSION['success'] = "Category Node [ID: {$vcid}] synchronized.";
        } else {
            throw new \Exception("Synchronization fault.");
        }

 // Replace the bulk_delete branch in process_vcategory.php
    } elseif ($action === 'bulk_delete') {
 // [Fix] Change selected_vids to the frontend standard key ids
        $vcids = $_POST['ids'] ?? [];
        if (empty($vcids)) throw new \Exception("No entities selected for purge.");  if ($categoryRepo->deleteCategories($vcids)) {
            $_SESSION['success'] = "Selected Category Nodes successfully purged.";
        } else {
 // Repository will throw the error; this generic error is the final fallback
            throw new \Exception("Purge execution fault. Relational constraints may exist.");
        }
    } else {
        throw new \Exception("Unknown execution vector.");
    }
} catch (\Exception $e) {
    $_SESSION['error'] = "System Fault: " . $e->getMessage();
}

// Redirect back after the action
header("Location: ../admin/manage_vcategory.php");
exit;
?>