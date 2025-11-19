<?php
session_start();
include '../config/db.php'; // Đường dẫn tới file db.php

// Chỉ khách hàng mới có thông báo
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'customer') {
    echo json_encode([]); // Trả về mảng rỗng
    exit();
}

$customer_id = $_SESSION['user']['id'] ?? 0;

if ($customer_id === 0) {
    echo json_encode([]);
    exit;
}

// 1. Lấy tất cả thông báo của khách hàng này
$stmt = $conn->prepare("
    SELECT message, status, created_at 
    FROM notifications 
    WHERE customer_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);

// 2. Tự động "đánh dấu đã đọc" các thông báo chưa đọc
// (Chúng ta làm việc này SAU KHI lấy dữ liệu ở trên)
$stmtUpdate = $conn->prepare("UPDATE notifications SET status = 'read' WHERE customer_id = ? AND status = 'unread'");
$stmtUpdate->bind_param("i", $customer_id);
$stmtUpdate->execute();
$stmtUpdate->close();

// 3. Trả về dữ liệu JSON cho JavaScript
echo json_encode($notifications);
?>