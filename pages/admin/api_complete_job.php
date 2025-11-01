<?php
session_start();
include '../../config/db.php';

// (Code kiểm tra Technical của bạn ở đây...)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? 0;
    $tech_id = $_SESSION['user']['id'];

    if ($booking_id && $tech_id) {
        // Cập nhật booking (Code cũ của bạn)
        $stmt = $conn->prepare("UPDATE bookings SET status = 'completed' WHERE id = ? AND technician_id = ?");
        $stmt->bind_param("ii", $booking_id, $tech_id);
        $stmt->execute();
        $stmt->close();

        // === 🔔 THÊM CODE MỚI TẠI ĐÂY ===
        // 1. Lấy customer_id từ booking
        $result = $conn->query("SELECT customer_id FROM bookings WHERE id = $booking_id");
        $customer_id = $result->fetch_assoc()['customer_id'];
        
        // 2. Tạo thông báo
     $message = "Đơn hàng #${booking_id} đã hoàn thành. Cảm ơn bạn!";
$stmt_notify = $conn->prepare("INSERT INTO notifications (customer_id, message) VALUES (?, ?)"); // <- Xóa booking_id
$stmt_notify->bind_param("is", $customer_id, $message); // <- Xóa 'i' và $booking_id
$stmt_notify->execute();
        // === HẾT CODE MỚI ===
    }
    
    header("Location: tech_schedule.php"); // (Sửa lại tên file nếu cần)
}
?>