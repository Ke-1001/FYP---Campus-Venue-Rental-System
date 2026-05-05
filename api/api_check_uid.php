<?php
// File: User/api_check_uid.php
header('Content-Type: application/json');
require_once '../config/db.php';

// 接收 JSON payload 或 GET 参数
$raw_data = json_decode(file_get_contents('php://input'), true);
$uid = $raw_data['uid'] ?? $_GET['uid'] ?? '';

// 边界条件防御
if (empty(trim($uid))) {
    echo json_encode(['exists' => false, 'error' => 'empty_input']);
    exit();
}

// 执行精确匹配探测
$stmt = $conn->prepare("SELECT 1 FROM user WHERE uid = ? LIMIT 1");
$stmt->bind_param("s", $uid);
$stmt->execute();
$stmt->store_result();

// 逻辑同构映射: 若行数 > 0，则 exists ≡ True
$exists = ($stmt->num_rows > 0);

echo json_encode(['exists' => $exists]);

$stmt->close();
$conn->close();
exit();
?>