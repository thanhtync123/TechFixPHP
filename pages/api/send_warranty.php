<?php
header('Content-Type: application/json');
require_once '../../config/db.php';

$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

if ($booking_id <= 0 || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập lý do bảo hành.']);
    exit;
}

// 1. Kiểm tra xem đơn hàng có tồn tại và còn hạn bảo hành không
$sqlCheck = "SELECT appointment_time FROM bookings WHERE id = ? AND status = 'completed'";
$stmt = $conn->prepare($sqlCheck);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) {
    echo json_encode(['success' => false, 'message' => 'Đơn hàng không hợp lệ.']);
    exit;
}

// Kiểm tra hạn 90 ngày
$expiry = date('Y-m-d', strtotime($res['appointment_time'] . ' + 90 days'));
if (date('Y-m-d') > $expiry) {
    echo json_encode(['success' => false, 'message' => 'Đơn hàng đã hết hạn bảo hành.']);
    exit;
}

// 2. Kiểm tra xem đã gửi yêu cầu chưa (tránh spam)
$checkExist = $conn->query("SELECT id FROM warranty_requests WHERE booking_id = $booking_id AND status = 'pending'");
if ($checkExist->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Bạn đã gửi yêu cầu rồi, vui lòng đợi nhân viên liên hệ.']);
    exit;
}

// 3. Lưu yêu cầu
$insert = $conn->prepare("INSERT INTO warranty_requests (booking_id, reason) VALUES (?, ?)");
$insert->bind_param("is", $booking_id, $reason);

if ($insert->execute()) {
    echo json_encode(['success' => true, 'message' => 'Gửi yêu cầu thành công! Nhân viên sẽ liên hệ sớm.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống.']);
}
?>