<?php
session_start();
include '../../config/db.php';
require_once '../../libs/send_mail.php';

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? null) !== 'technical') {
    die("Bạn không có quyền.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = isset($_POST['booking_id']) ? (int) $_POST['booking_id'] : 0;
    $tech_id = (int) ($_SESSION['user']['id'] ?? 0);

    if ($booking_id && $tech_id) {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'completed' WHERE id = ? AND technician_id = ?");
        $stmt->bind_param("ii", $booking_id, $tech_id);
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

            $message_chuong = "Đơn hàng #{$booking_id} đã hoàn thành. Cảm ơn bạn!";
            $message_mail = "Chào bạn {$customer_name},\n\nĐơn hàng #{$booking_id} đã hoàn thành.\nCảm ơn bạn đã sử dụng dịch vụ của TECHFIX!";

            try {
                $stmtNotify = $conn->prepare("INSERT INTO notifications (customer_id, message) VALUES (?, ?)");
                $stmtNotify->bind_param("is", $customer_id, $message_chuong);
                $stmtNotify->execute();
                $stmtNotify->close();

                sendBookingEmail($customer_email, $customer_name, $booking_id, 'paid');
            } catch (Exception $e) {
                error_log('Error notifying customer: ' . $e->getMessage());
            }
        }
    }

    header("Location: tech_schedule.php");
    exit;
}
?>