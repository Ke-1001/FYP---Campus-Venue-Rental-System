<?php
// File path: actions/process_venue.php
session_start();
require_once '../config/db.php';

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $venue_name = htmlspecialchars(trim($_POST['venue_name']));
    $category = $_POST['category'];
    $capacity = intval($_POST['capacity']);
    $base_deposit = floatval($_POST['base_deposit']);
    $status = 'Available';

    $sql = "INSERT INTO venues (venue_name, category, capacity, base_deposit, status) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssids", $venue_name, $category, $capacity, $base_deposit, $status);
        if ($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Node Registered: Venue added successfully.'];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database insertion failed: ' . $stmt->error];
        }
        $stmt->close();
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'SQL Syntax Error: ' . $conn->error];
    }
    header("Location: ../admin/manage_venues.php");
    exit;

} elseif ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $venue_id = intval($_POST['venue_id']);
    $venue_name = htmlspecialchars(trim($_POST['venue_name']));
    $category = $_POST['category'];
    $capacity = intval($_POST['capacity']);
    $base_deposit = floatval($_POST['base_deposit']);
    $status = $_POST['status'];

    $sql = "UPDATE venues SET venue_name = ?, category = ?, capacity = ?, base_deposit = ?, status = ? WHERE venue_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssidsi", $venue_name, $category, $capacity, $base_deposit, $status, $venue_id);
        if ($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Configuration Deployed: Venue updated successfully.'];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database update failed: ' . $stmt->error];
        }
        $stmt->close();
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'msg' => 'SQL Syntax Error: ' . $conn->error];
    }
    header("Location: ../admin/manage_venues.php");
    exit;

} elseif ($action === 'delete' && isset($_GET['id'])) {
    $venue_id = intval($_GET['id']);

    $sql_check = "SELECT COUNT(*) AS active_count FROM bookings WHERE venue_id = ? AND booking_status IN ('Pending', 'Approved')";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $venue_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();
    $stmt_check->close();

    if ($row_check['active_count'] > 0) {
        $_SESSION['toast'] = [
            'type' => 'error', 
            'msg' => "Termination Blocked: Node has {$row_check['active_count']} active bookings."
        ];
        header("Location: ../admin/manage_venues.php");
        exit;
    }

    $sql = "DELETE FROM venues WHERE venue_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $venue_id);
        if ($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Node Terminated: Venue deleted successfully.'];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Database deletion failed: ' . $stmt->error];
        }
        $stmt->close();
    }
    header("Location: ../admin/manage_venues.php");
    exit;
} else {
    die("Invalid request vector.");
}
?>