<?php
session_start();
include '../../config/db.php';
include __DIR__ . '/template/sidebar.php'; 
// 🔒 Kiểm tra Kỹ thuật viên
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'technical') {
    header("Location: /TechFixPHP/pages/public_page/login.php");
    exit();
}

$tech_id = $_SESSION['user']['id']; // Lấy ID của thợ đang đăng nhập

// === SỬA ĐỔI CHÍNH (Đổi 'confirmed' thành 'completed') ===
$bookings_query = $conn->prepare("
    SELECT 
        b.id, b.customer_name, b.phone, b.address, b.appointment_time, b.status, b.final_price,
        s.name AS service_name
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    WHERE b.technician_id = ? AND b.status = 'completed'  -- <-- ĐÃ SỬA
    ORDER BY b.appointment_time DESC
");
// === HẾT SỬA ĐỔI ===

$bookings_query->bind_param("i", $tech_id);
$bookings_query->execute();
$result = $bookings_query->get_result();
$bookings = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// (Tính năng thêm) Tính tổng tiền thợ đã làm
$total_revenue = 0;
foreach ($bookings as $b) {
    $total_revenue += $b['final_price'];
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch sử công việc - TECHFIX</title>
    <link rel="stylesheet" href="../../assets/css/admin.css"> 
    <style>
        body { background: #f5f6fa; font-family: 'Arial', sans-serif; }
        .container { max-width: 1200px; margin: 30px auto; background: #fff; border-radius: 10px; padding: 30px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        table { width: 100%; border-collapse: collapse; text-align: left; margin-top: 20px;}
        th, td { padding: 12px 15px; border-bottom: 1px solid #ddd; }
        th { background: #6c757d; color: #fff; } /* Màu xám cho lịch sử */
        td { vertical-align: middle; }
        .total-bar { 
            font-size: 1.2rem; 
            font-weight: bold; 
            text-align: right; 
            margin-top: 20px; 
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Lịch sử công việc (Đã Hoàn thành)</h2>
        <p><strong>Kỹ thuật viên:</strong> <?php echo htmlspecialchars($_SESSION['name']); ?></p>

        <?php if (!empty($bookings)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Mã Đơn</th>
                        <th>Khách hàng</th>
                        <th>Địa chỉ</th>
                        <th>Dịch vụ</th>
                        <th>Ngày hoàn thành</th>
                        <th>Giá tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td>#<?= $booking['id'] ?></td>
                            <td><?= htmlspecialchars($booking['customer_name']) ?></td>
                            <td><?= htmlspecialchars($booking['address']) ?></td>
                            <td><?= htmlspecialchars($booking['service_name']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($booking['appointment_time'])) ?></td>
                            <td style="font-weight: bold;"><?= number_format($booking['final_price']) ?>đ</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="total-bar">
                Tổng doanh thu (từ các việc đã hoàn thành): <?= number_format($total_revenue) ?>đ
            </div>

        <?php else: ?>
            <p style="text-align:center; color: #777;">Bạn chưa hoàn thành công việc nào.</p>
        <?php endif; ?>
    </div>
</body>
</html>