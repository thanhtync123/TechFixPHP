<?php
session_start();
include '../../config/db.php';

// (Code kiểm tra Admin của bạn ở đây...)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? 0;
    $technician_id = $_POST['technician_id'] ?? 0;

    if ($booking_id && $technician_id) {
        
        // 1. Cập nhật booking (Đổi status thành 'confirmed')
        $stmt = $conn->prepare("UPDATE bookings SET technician_id = ?, status = 'confirmed' WHERE id = ?");
        $stmt->bind_param("ii", $technician_id, $booking_id);
        $stmt->execute();
        $stmt->close(); 

        // 2. Lấy customer_id từ booking
        $result = $conn->query("SELECT customer_id FROM bookings WHERE id = $booking_id");
        $customer_id = $result->fetch_assoc()['customer_id'];
        
        // 3. (ĐÃ SỬA) Chỉ tạo thông báo nếu customer_id hợp lệ
        if ($customer_id && $customer_id > 0) 
        {
            // Chúng ta sẽ "thử" tạo thông báo.
            // Nếu thất bại (do khách hàng đã bị xóa), chúng ta sẽ bỏ qua lỗi.
            try {
                $message = "Đơn hàng #${booking_id} của bạn đã được xác nhận!"; 
                $stmt_notify = $conn->prepare("INSERT INTO notifications (customer_id, message) VALUES (?, ?)");
                $stmt_notify->bind_param("is", $customer_id, $message);
                $stmt_notify->execute(); // <-- Dòng 32 (lỗi) của bạn giờ đã an toàn
            } catch (mysqli_sql_exception $e) {
                // Bỏ qua lỗi Foreign Key.
                // (Không làm gì cả, chỉ là không gửi được thông báo)
            }
        }
    }
    
    // Quay lại trang điều phối
    header("Location: admin_dispatch.php");
}
?>