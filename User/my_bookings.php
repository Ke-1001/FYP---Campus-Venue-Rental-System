<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT b.booking_id, v.venue_name, b.booking_date, 
               b.start_time, b.end_time, b.status, b.created_at
        FROM bookings b
        JOIN venues v ON b.venue_id = v.venue_id
        WHERE b.user_id = ?
        ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>My Bookings</title>
    <style>
        body {
            font-family: Arial;
            background-color: #f4f6f9;
            padding: 20px;
        }
        h2 {
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .status-approved { color: green; font-weight: bold; }
        .status-pending { color: orange; font-weight: bold; }
        .status-rejected { color: red; font-weight: bold; }
    </style>
</head>
<body>

<h2>My Bookings</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Venue</th>
        <th>Date</th>
        <th>Time</th>
        <th>Status</th>
        <th>Created At</th>
    </tr>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['booking_id'] ?></td>
                <td><?= $row['venue_name'] ?></td>
                <td><?= $row['booking_date'] ?></td>
                <td><?= $row['start_time'] ?> - <?= $row['end_time'] ?></td>
                <td class="
                    <?php 
                        if ($row['status'] == 'Approved') echo 'status-approved';
                        elseif ($row['status'] == 'Pending') echo 'status-pending';
                        else echo 'status-rejected';
                    ?>">
                    <?= $row['status'] ?>
                </td>
                <td><?= $row['created_at'] ?></td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="6">No bookings found.</td>
        </tr>
    <?php endif; ?>
</table>

</body>
</html>