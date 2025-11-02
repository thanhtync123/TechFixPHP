<?php
session_start();
include '../../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    die("Bạn không có quyền.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? 0;
    $technician_id = $_POST['technician_id'] ?? 0;

    if ($booking_id && $technician_id) {
        
        $stmt = $conn->prepare("UPDATE bookings SET technician_id = ?, status = 'confirmed' WHERE id = ?");
        $stmt->bind_param("ii", $technician_id, $booking_id);
        $stmt->execute();
        $stmt->close(); 

        // 2. Lấy thông tin khách hàng (ĐÃ SỬA: LẤY EMAIL THẬT)
        $result = $conn->query("
            SELECT b.customer_id, u.email, u.name 
            FROM bookings b
            JOIN users u ON b.customer_id = u.id
            WHERE b.id = $booking_id
        ");
        
        if ($result && $result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            $customer_id = $customer['customer_id'];
            $customer_email = $customer['email']; // <-- Lấy email thật
            $customer_name = $customer['name'];

            if ($customer_id && $customer_id > 0 && !empty($customer_email)) 
            {
                $message_chuong = "Đơn hàng #${booking_id} của bạn đã được xác nhận!";
                $message_mail = "Chào bạn {$customer_name},\n\nĐơn hàng #${booking_id} của bạn đã được xác nhận.\nKỹ thuật viên sẽ sớm liên hệ với bạn.\n\nCảm ơn,\nTECHFIX";
                $subject = "TechFix: Đơn hàng #${booking_id} đã được xác nhận";
                $headers = 'From: support@techfix.com';

                try {
                    $stmt_notify = $conn->prepare("INSERT INTO notifications (customer_id, message) VALUES (?, ?)");
                    $stmt_notify->bind_param("is", $customer_id, $message_chuong);
                    $stmt_notify->execute();

                    // Gửi Email (Dùng email thật)
                    @mail($customer_email, $subject, $message_mail, $headers);

                } catch (Exception $e) { /* Bỏ qua lỗi */ }
            }
        }
    }
    
    header("Location: admin_dispatch.php");
}
?>