<?php
// /TechFixPHP/Customer/booking_detail.php
session_start();

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'customer') {
    header("Location: /TechFixPHP/page/public_page/admin/login.php");
    exit();
}

include '../config/db.php'; // Kiểm tra kỹ đường dẫn file kết nối này

$booking_id = $_GET['id'] ?? 0;
$customer_id = $_SESSION['user']['id']; 

// 2. LẤY THÔNG TIN CHI TIẾT (ĐÃ CẬP NHẬT ĐẦY ĐỦ)
$query = "
    SELECT 
        b.id, 
        s.name AS service_name, 
        b.final_price,      -- Lấy giá chốt cuối cùng
        b.appointment_time, 
        b.status, 
        b.payment_status,   -- Lấy trạng thái thanh toán
        b.created_at, 
        b.note,
        b.photo_before,     -- Ảnh trước
        b.photo_after       -- Ảnh sau
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
    echo "<p style='text-align:center; color:red; margin-top:50px;'>Không tìm thấy lịch đặt này.</p>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?= $booking['id'] ?> - TECHFIX</title>
    <style>
        body { background-color: #f5f7fa; font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .container {
            max-width: 800px; margin: 0 auto; background: white;
            padding: 40px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        h2 { text-align: center; color: #2c3e50; margin-bottom: 30px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; }
        
        .detail-row { margin: 15px 0; display: flex; justify-content: space-between; border-bottom: 1px dashed #eee; padding-bottom: 10px;}
        .detail-row:last-child { border-bottom: none; }
        .detail-row strong { color: #555; }
        .detail-row span { color: #333; font-weight: 500; }

        /* Trạng thái */
        .status { padding: 6px 12px; border-radius: 20px; font-size: 14px; font-weight: bold; text-transform: capitalize; }
        .pending { background-color: #fff3cd; color: #856404; }
        .confirmed { background-color: #d1ecf1; color: #0c5460; }
        .completed { background-color: #d4edda; color: #155724; }
        .cancelled { background-color: #f8d7da; color: #721c24; }

        /* Nút bấm */
        .btn-group { margin-top: 30px; text-align: center; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 10px 20px; border-radius: 6px; border: none; cursor: pointer; text-decoration: none; font-size: 14px; font-weight: 600; transition: 0.3s; }
        .btn-back { background: #6c757d; color: white; }
        .btn-pay { background: #ffc107; color: #333; }
        .btn-invoice { background: #17a2b8; color: white; }
        .btn-cancel { background: #dc3545; color: white; }
        .btn:hover { opacity: 0.9; transform: translateY(-2px); }

        /* Hồ sơ bệnh án */
        .service-report { margin-top: 30px; background: #fafafa; padding: 20px; border-radius: 10px; border: 1px solid #eee; }
        .report-title { color: #007bff; text-align: center; margin-bottom: 20px; font-size: 18px; }
        .photos-container { display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; align-items: center; }
        .photo-card { background: #fff; padding: 10px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
        .photo-label { display: block; font-weight: bold; margin-bottom: 8px; font-size: 12px; letter-spacing: 1px; }
        .photo-card img { max-width: 100%; width: 250px; height: 180px; object-fit: cover; border-radius: 4px; cursor: pointer; }
        .arrow-icon { font-size: 30px; color: #ccc; }

    </style>
</head>
<body>
    <div class="container">
        <h2>Chi tiết lịch đặt #<?= $booking['id'] ?></h2>

        <div class="detail-row"><strong>Dịch vụ:</strong> <span><?= htmlspecialchars($booking['service_name']) ?></span></div>
        
        <div class="detail-row">
            <strong>Tổng chi phí:</strong> 
            <span style="color: #d9534f; font-weight: bold; font-size: 1.2em;">
                <?= number_format($booking['final_price']) ?>đ
            </span>
        </div>

        <div class="detail-row"><strong>Ngày hẹn:</strong> <span><?= date('d/m/Y H:i', strtotime($booking['appointment_time'])) ?></span></div>
        <div class="detail-row"><strong>Ngày đặt:</strong> <span><?= date('d/m/Y H:i', strtotime($booking['created_at'])) ?></span></div>
        <div class="detail-row"><strong>Ghi chú:</strong> <span><?= $booking['note'] ?: 'Không có' ?></span></div>
        
        <div class="detail-row">
            <strong>Trạng thái đơn hàng:</strong> 
            <span class="status <?= $booking['status'] ?>"><?= ucfirst($booking['status']) ?></span>
        </div>

        <div class="detail-row" style="border:none;">
            <strong>Trạng thái thanh toán:</strong> 
            <?php if($booking['payment_status'] == 'paid'): ?>
                <span style="color:green; font-weight:bold;">✅ Đã thanh toán</span>
            <?php else: ?>
                <span style="color:orange; font-weight:bold;">⏳ Chưa thanh toán</span>
            <?php endif; ?>
        </div>

        <?php if (!empty($booking['photo_before']) || !empty($booking['photo_after'])): ?>
            <div class="service-report">
                <h3 class="report-title">📋 Hồ sơ Bệnh án Thiết bị</h3>
                <div class="photos-container">
                    
                    <?php if (!empty($booking['photo_before'])): ?>
                    <div class="photo-card">
                        <span class="photo-label" style="color: #d9534f;">TRƯỚC KHI SỬA</span>
                        <img src="../assets/uploads/<?= htmlspecialchars($booking['photo_before']) ?>" onclick="window.open(this.src)">
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($booking['photo_before']) && !empty($booking['photo_after'])): ?>
                        <div class="arrow-icon">➜</div>
                    <?php endif; ?>

                    <?php if (!empty($booking['photo_after'])): ?>
                    <div class="photo-card">
                        <span class="photo-label" style="color: #28a745;">SAU KHI SỬA</span>
                        <img src="../assets/uploads/<?= htmlspecialchars($booking['photo_after']) ?>" onclick="window.open(this.src)">
                    </div>
                    <?php endif; ?>

                </div>
                <p style="text-align: center; margin-top: 15px; color: #888; font-size: 0.9em;">
                    * Hình ảnh thực tế tại nhà khách hàng.
                </p>
            </div>
        <?php endif; ?>

        <div class="btn-group">
            <a href="my_booking.php" class="btn btn-back">⬅ Quay lại</a>

            <?php if ($booking['payment_status'] !== 'paid' && $booking['status'] !== 'cancelled'): ?>
                <a href="vnpay_create_payment.php?id=<?= $booking['id'] ?>" class="btn btn-pay">💳 Thanh toán Online</a>
            <?php endif; ?>

            <?php if ($booking['status'] === 'completed'): ?>
                <a href="export_invoice.php?id=<?= $booking['id'] ?>" class="btn btn-invoice">📄 Tải Hóa Đơn</a>
            <?php endif; ?>

            <?php if ($booking['status'] === 'pending'): ?>
                <a href="cancel_booking.php?id=<?= $booking['id'] ?>" class="btn btn-cancel" onclick="return confirm('Bạn chắc chắn muốn hủy lịch này?')">Hủy lịch</a>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>