<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'customer') {
    header("Location: /TechFixPHP/page/public_page/admin/login.php");
    exit();
}
include '../config/db.php';

$booking_id = $_GET['id'] ?? 0;
$customer_id = $_SESSION['user']['id'];

// Cập nhật trạng thái
$query = "UPDATE bookings SET status = 'cancelled' WHERE id = ? AND customer_id = ? AND status = 'pending'";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $customer_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "<script>alert('Hủy lịch thành công!'); window.location.href='my_booking.php';</script>";
} else {
    echo "<script>alert('Không thể hủy lịch này. Có thể đã được xác nhận hoặc không tồn tại.'); window.location.href='my_booking.php';</script>";
}
?>
