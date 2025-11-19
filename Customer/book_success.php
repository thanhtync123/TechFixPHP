<?php
// booking_success.php
include '../../config/db.php';
$id = intval($_GET['id'] ?? 0);
$booking = null;
if ($id > 0) {
    $stmt = $conn->prepare("SELECT b.*, s.name AS service_name FROM bookings b LEFT JOIN services s ON b.service_id = s.id WHERE b.id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $booking = $res ? $res->fetch_assoc() : null;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="utf-8"><title>Đặt lịch thành công</title></head>
<body>
<?php if ($booking): ?>
    <h2>Đặt lịch thành công</h2>
    <p>Mã đơn: <?= htmlspecialchars($booking['id']) ?></p>
    <p>Khách hàng: <?= htmlspecialchars($booking['customer_name']) ?></p>
    <p>Dịch vụ: <?= htmlspecialchars($booking['service_name']) ?></p>
    <p>Thời gian hẹn: <?= htmlspecialchars($booking['appointment_time']) ?></p>
    <p>Status: <?= htmlspecialchars($booking['status']) ?></p>
    <p><a href="/TechFixPHP/index.php">Quay về trang chủ</a></p>
<?php else: ?>
    <p>Không tìm thấy đơn hàng.</p>
<?php endif; ?>
</body>
</html>
