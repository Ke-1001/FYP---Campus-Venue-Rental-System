<?php
// file path: actions/process_venue.php

require_once '../config/db.php';

// Determine if it's a POST request (add) or GET request (delete)
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Handle adding a new venue (Create)
    $venue_name = htmlspecialchars(trim($_POST['venue_name']));
    $category = $_POST['category'];
    $capacity = intval($_POST['capacity']);
    $base_deposit = floatval($_POST['base_deposit']);
    $status = 'Available'; // Newly added venues are available by default

    $sql = "INSERT INTO venues (venue_name, category, capacity, base_deposit, status) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssids", $venue_name, $category, $capacity, $base_deposit, $status);
        if ($stmt->execute()) {
            // Venue added successfully, use JavaScript to display alert and redirect to management page
            echo "<script>
                    alert('Venue added successfully！');
                    window.location.href = '../admin/manage_venues.php';
                  </script>";
        } else {
            echo "Database insertion failed: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "SQL Syntax Error: " . $conn->error;
    }

} elseif ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle editing a venue (Update)
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
            echo "<script>
                    alert('Venue updated successfully!');
                    window.location.href = '../admin/manage_venues.php';
                  </script>";
        } else {
            echo "Database update failed: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "SQL Syntax Error: " . $conn->error;
    }

} elseif ($action === 'delete' && isset($_GET['id'])) {
    // 2. Handle deleting a venue (Delete) with safety checks
    $venue_id = intval($_GET['id']);

    // Check if there are any pending bookings (Pending or Approved status)
    $sql_check = "SELECT COUNT(*) AS active_count FROM bookings 
                  WHERE venue_id = ? AND booking_status IN ('Pending', 'Approved')";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $venue_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();
    $stmt_check->close();

    // If there are any active bookings, prevent deletion immediately!
    if ($row_check['active_count'] > 0) {
        echo "<script>
                alert('Cannot delete: This venue has ' + {$row_check['active_count']} + ' active bookings!\\nPlease cancel these bookings first or wait until all bookings are completed before deleting.');
                window.location.href = '../admin/manage_venues.php';
              </script>";
        exit; // Stop execution, absolutely do not execute the DELETE below
    }

    // If safe (no active bookings), execute the actual deletion
    $sql = "DELETE FROM venues WHERE venue_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $venue_id);
        if ($stmt->execute()) {
            echo "<script>
                    alert('Venue deleted successfully!');
                    window.location.href = '../admin/manage_venues.php';
                  </script>";
        } else {
            echo "Database deletion failed: " . $stmt->error;
        }
        $stmt->close();
    }
} else {
    die("Invalid request.");
}

$conn->close();
?>