<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'customer') {
    header("Location: /TechFixPHP/page/public_page/admin/login.php");
    exit();
}

include '../config/db.php'; // Đường dẫn chỉnh lại cho đúng theo project của bạn

$booking_id = $_GET['id'] ?? 0;
$customer_id = $_SESSION['user']['id']; // hoặc $_SESSION['customer_id'] nếu có

// Lấy thông tin chi tiết lịch đặt
$query = "
    SELECT 
        b.id, s.name AS service_name, s.price, s.unit, b.appointment_time, 
        b.status, b.created_at, b.note
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    WHERE b.id = ? AND b.customer_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    echo "<p style='text-align:center; color:red;'>Không tìm thấy lịch đặt này.</p>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết lịch đặt - TECHFIX</title>
    <link rel="stylesheet" href="/TechFixPHP/assets/css/booking_detail.css">
    <style>
        body { background-color: #f5f6fa; font-family: 'Poppins', sans-serif; }
        .container {
            max-width: 700px; margin: 40px auto; background: white;
            padding: 30px; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        h2 { text-align: center; color: #0099ff; margin-bottom: 20px; }
        .detail-row { margin: 15px 0; display: flex; justify-content: space-between; }
        .detail-row strong { color: #333; }
        .status {
            padding: 5px 10px; border-radius: 5px; font-weight: 600;
            text-transform: capitalize;
        }
        .pending { background-color: #ffeb3b; }
        .confirmed { background-color: #4caf50; color: white; }
        .completed { background-color: #2196f3; color: white; }
        .cancelled { background-color: #f44336; color: white; }
        .btn {
            display: inline-block; padding: 10px 20px; margin-top: 20px;
            border-radius: 8px; border: none; cursor: pointer; text-decoration: none;
        }
        .btn-back { background: #0099ff; color: white; }
        .btn-cancel { background: #f44336; color: white; margin-left: 10px; }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Chi tiết lịch đặt</h2>

        <div class="detail-row"><strong>Mã lịch:</strong> <span>#<?= $booking['id'] ?></span></div>
        <div class="detail-row"><strong>Dịch vụ:</strong> <span><?= htmlspecialchars($booking['service_name']) ?></span></div>
        <div class="detail-row"><strong>Giá:</strong> <span><?= number_format($booking['price']) ?>đ / <?= $booking['unit'] ?></span></div>
        <div class="detail-row"><strong>Ngày hẹn:</strong> <span><?= date('d/m/Y H:i', strtotime($booking['appointment_time'])) ?></span></div>
        <div class="detail-row"><strong>Ngày đặt:</strong> <span><?= date('d/m/Y H:i', strtotime($booking['created_at'])) ?></span></div>
        <div class="detail-row"><strong>Ghi chú:</strong> <span><?= $booking['note'] ?: 'Không có' ?></span></div>
        <div class="detail-row">
            <strong>Trạng thái:</strong> 
            <span class="status <?= $booking['status'] ?>"><?= ucfirst($booking['status']) ?></span>
        </div>

      <div style="text-align:center;">
    <a href="my_booking.php" class="btn btn-back">⬅ Quay lại</a>
    <?php if ($booking['status'] === 'pending'): ?>
        <a href="cancel_booking.php?id=<?= $booking['id'] ?>" class="btn btn-cancel" onclick="return confirm('Bạn chắc chắn muốn hủy lịch này?')">Hủy lịch</a>
    <?php endif; ?>
</div>
    </div>
</body>
</html>
