<?php
// This section prepares the admin route personnel page.
session_start();
require_once __DIR__ . '/../includes/admin_auth.php';

$ref = $_GET['ref'] ?? '';

$parts = explode('|', $ref);
$type = strtolower(trim($parts[0] ?? ''));
$id = intval($parts[1] ?? 0);

if ($id <= 0)
{
    die("Invalid personnel reference.");
}

if ($type === 'admin')
{
    header("Location: edit_admin.php?aid=" . $id);
    exit;
}

if ($type === 'staff')
{
    header("Location: edit_staff.php?sid=" . $id);
    exit;
}

die("Unknown personnel type.");
