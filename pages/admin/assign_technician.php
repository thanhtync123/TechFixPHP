<?php
session_start();
include '../../config/db.php';
require_once '../../libs/send_mail.php';

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? null) !== 'admin') {
    die("Bạn không có quyền.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = isset($_POST['booking_id']) ? (int) $_POST['booking_id'] : 0;
    $technician_id = isset($_POST['technician_id']) ? (int) $_POST['technician_id'] : 0;

    if ($booking_id && $technician_id) {
        $stmt = $conn->prepare("UPDATE bookings SET technician_id = ?, status = 'confirmed' WHERE id = ?");
        $stmt->bind_param("ii", $technician_id, $booking_id);
        $stmt->execute();
        $stmt->close();

        $stmtInfo = $conn->prepare("
            SELECT b.customer_id, u.email, u.name 
            FROM bookings b
            JOIN users u ON b.customer_id = u.id
            WHERE b.id = ?
            LIMIT 1
        ");
        $stmtInfo->bind_param("i", $booking_id);
        $stmtInfo->execute();
        $result = $stmtInfo->get_result();
        $customer = $result->fetch_assoc();
        $stmtInfo->close();

        if ($customer && !empty($customer['email'])) {
            $customer_id = (int) $customer['customer_id'];
            $customer_email = $customer['email'];
            $customer_name = $customer['name'] ?? 'Khách hàng';

            $message_chuong = "Đơn hàng #{$booking_id} của bạn đã được xác nhận!";
            $message_mail = "Chào bạn {$customer_name},\n\nĐơn hàng #{$booking_id} của bạn đã được xác nhận.\nKỹ thuật viên sẽ sớm liên hệ với bạn.\n\nCảm ơn,\nTECHFIX";
            $subject = "TechFix: Đơn hàng #{$booking_id} đã được xác nhận";

            try {
                $stmtNotify = $conn->prepare("INSERT INTO notifications (customer_id, message) VALUES (?, ?)");
                $stmtNotify->bind_param("is", $customer_id, $message_chuong);
                $stmtNotify->execute();
                $stmtNotify->close();

                sendBookingEmail($customer_email, $customer_name, $booking_id, 'new');
            } catch (Exception $e) {
                error_log('Error notifying customer: ' . $e->getMessage());
            }
        }
    }

    header("Location: admin_dispatch.php");
    exit;
}
?>