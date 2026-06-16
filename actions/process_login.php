<?php

// This section checks admin or staff login details and starts the session.
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '')
    {
        header("Location: ../admin/login.php?error=invalid");
        exit();
    }

    $stmt = $conn->prepare("SELECT aid, admin_name, password, role FROM admin WHERE email = ? AND status = 'active' LIMIT 1");
    if (!$stmt)
    {
        die("SQL Prepare Fault: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1)
    {
        $admin = $result->fetch_assoc();

        if (password_verify($password, $admin['password']))
        {
            session_regenerate_id(true);

            $_SESSION['aid'] = $admin['aid'];
            $_SESSION['full_name'] = $admin['admin_name'];
            $_SESSION['role'] = $admin['role'];

            header("Location: ../admin/dashboard.php");
            exit();
        }
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT sid, staff_name, password, position FROM staff WHERE email = ? AND status = 'active' LIMIT 1");
    if (!$stmt)
    {
        die("SQL Prepare Fault: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1)
    {
        $staff = $result->fetch_assoc();

        if (password_verify($password, $staff['password']))
        {
            session_regenerate_id(true);

            $_SESSION['sid'] = $staff['sid'];
            $_SESSION['full_name'] = $staff['staff_name'];
            $_SESSION['role'] = 'staff';
            $_SESSION['position'] = $staff['position'];

            header("Location: ../admin/inspections.php");
            exit();
        }
    }
    $stmt->close();

    header("Location: ../admin/login.php?error=invalid");
    exit();
}

$conn->close();
?>
