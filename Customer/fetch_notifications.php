<?php
session_start();
header('Content-Type: application/json');

include '../config/db.php'; // chỉnh lại nếu db.php ở nơi khác

if (!isset($_SESSION['user'])) {
    echo json_encode([]);
    exit();
}

$customer_id = $_SESSION['user']['id'] ?? 0;

$query = "
    SELECT id, message, status, 
           DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') AS created_at
    FROM notifications
    WHERE customer_id = ?
    ORDER BY created_at DESC
    LIMIT 10
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode($notifications);
