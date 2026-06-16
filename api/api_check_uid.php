<?php
// This section returns check uid data for page requests.
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
$raw_data = json_decode(file_get_contents('php://input'), true);
$uid = $raw_data['uid'] ?? $_GET['uid'] ?? '';
if (empty(trim($uid)))
{
    echo json_encode(['exists' => false, 'error' => 'empty_input']);
    exit();
}
$stmt = $conn->prepare("SELECT 1 FROM user WHERE uid = ? LIMIT 1");
$stmt->bind_param("s", $uid);
$stmt->execute();
$stmt->store_result();
$exists = ($stmt->num_rows > 0);
echo json_encode(['exists' => $exists]);
$stmt->close();
$conn->close();
exit();
?>
