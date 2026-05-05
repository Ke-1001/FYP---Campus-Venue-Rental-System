<?php
// File: User/api_check_email.php
header('Content-Type: application/json');
require_once '../config/db.php';

$raw_data = json_decode(file_get_contents('php://input'), true);
$email = $raw_data['email'] ?? $_GET['email'] ?? '';

// 预处理: 服务器端词法校验
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['exists' => false, 'error' => 'invalid_format']);
    exit();
}

// 探测执行
$stmt = $conn->prepare("SELECT 1 FROM user WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

$exists = ($stmt->num_rows > 0);

echo json_encode(['exists' => $exists]);

$stmt->close();
$conn->close();
exit();
?>