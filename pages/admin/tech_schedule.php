<?php
session_start();
// (Đảm bảo đường dẫn config CSDL đúng)
include '../../config/db.php';
include __DIR__ . '/template/sidebar.php'; 
// 🔒 1. KIỂM TRA VAI TRÒ (CHỈ DÀNH CHO KỸ THUẬT VIÊN)
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'technical') {
    header("Location: /TechFixPHP/pages/public_page/login.php");
    exit();
}

// 2. LẤY ID CỦA KỸ THUẬT VIÊN ĐANG ĐĂNG NHẬP
$tech_id = $_SESSION['user']['id']; 

// 3. TRUY VẤN CÁC ĐƠN HÀNG ĐANG CHỜ LÀM (status = 'confirmed')
$bookings_query = $conn->prepare("
    SELECT 
        b.id, 
        b.customer_name, 
        b.phone, 
        b.address, 
        b.appointment_time, 
        b.status,
        b.district,
        s.name AS service_name
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    WHERE 
        b.technician_id = ? 
        AND b.status = 'confirmed'  -- Chỉ lấy việc 'Đã xác nhận'
    ORDER BY b.appointment_time ASC
");
$bookings_query->bind_param("i", $tech_id);
$bookings_query->execute();
$result = $bookings_query->get_result();
$bookings = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch làm việc của tôi - TECHFIX</title>
    <link rel="stylesheet" href="../../assets/css/admin_dispatch.css">
    
    <style>
        /* CSS bổ sung (nếu cần) */
        body { background: #f8f9fa; }
        .container { max-width: 1200px; margin: 30px auto; }
        .widget {
            background: #ffffff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        th { background: #007bff; color: #fff; } /* Màu xanh cho việc sắp tới */
        .action-btn {
            background: #28a745; /* Màu xanh lá */
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .action-btn:hover {
             background: #218838;
        }
    </style>
</head>
<body>
    <div class="container widget">
        <h2>Lịch làm việc (Việc mới)</h2>
        <p style="text-align: center; font-size: 1.1rem;">
            Kỹ thuật viên: <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>
        </p>

        <?php if (!empty($bookings)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Mã Đơn</th>
                        <th>Khách hàng</th>
                        <th>SĐT / Địa chỉ</th>
                        <th>Dịch vụ / Quận</th>
                        <th>Ngày hẹn</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td>#<?= $booking['id'] ?></td>
                            <td><?= htmlspecialchars($booking['customer_name']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($booking['phone']) ?></strong><br>
                                <small><?= htmlspecialchars($booking['address']) ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($booking['service_name']) ?></strong><br>
                                <small>Quận: <?= htmlspecialchars($booking['district']) ?></small>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($booking['appointment_time'])) ?></td>
                            <td>
                                <form action="api_complete_job.php" method="POST" onsubmit="return confirm('Xác nhận hoàn thành công việc này?')">
                                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                    <button type="submit" class="action-btn">✅ Hoàn thành</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align:center; color: #777; font-size: 1.1rem; padding: 20px;">
                Bạn không có công việc mới nào (ở trạng thái 'Confirmed').
            </p>
        <?php endif; ?>
    </div>
</body>
</html>