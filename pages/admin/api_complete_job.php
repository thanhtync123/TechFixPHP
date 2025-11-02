<?php
session_start();
include '../../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'technical') {
    die("Bạn không có quyền.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? 0;
    $tech_id = $_SESSION['user']['id'];

    if ($booking_id && $tech_id) {
        
        $stmt = $conn->prepare("UPDATE bookings SET status = 'completed' WHERE id = ? AND technician_id = ?");
        $stmt->bind_param("ii", $booking_id, $tech_id);
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
                $message_chuong = "Đơn hàng #${booking_id} đã hoàn thành. Cảm ơn bạn!";
                $message_mail = "Chào bạn {$customer_name},\n\nĐơn hàng #${booking_id} đã hoàn thành.\nCảm ơn bạn đã sử dụng dịch vụ của TECHFIX!";
                $subject = "TechFix: Đơn hàng #${booking_id} đã hoàn thành";
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
    
    header("Location: tech_schedule.php");
}
?>