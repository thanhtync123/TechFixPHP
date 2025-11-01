<?php
include '../../config/db.php'; // Đảm bảo đường dẫn này đúng

// 1. KIỂM TRA ID ĐƠN HÀNG
if (!isset($_GET['id'])) {
    die("Không tìm thấy ID đơn hàng.");
}
$booking_id = intval($_GET['id']);

// 2. TRUY VẤN CSDL
// Lấy thông tin chi tiết của booking, join với tên dịch vụ và tên thợ
$query = "
    SELECT 
        b.id,
        b.customer_name,
        b.phone,
        b.address,
        b.district,
        b.appointment_time,
        b.final_price,
        b.status,
        s.name AS service_name,
        t.name AS technician_name
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    LEFT JOIN users t ON b.technician_id = t.id
    WHERE b.id = ?
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Lỗi SQL: " . $conn->error);
}
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Không tìm thấy đơn hàng (booking) với ID này.");
}
$booking = $result->fetch_assoc();

// Tính toán
$service_price = $booking['final_price']; // Hệ thống booking lưu giá cuối cùng
$total = $booking['final_price'];

?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Hóa đơn Booking #<?= htmlspecialchars($booking['id']) ?></title>
<style>
    /* (Tôi lấy lại CSS từ file invoice_order.php của bạn) */
    body{font-family:Arial, sans-serif;background:#f5f5f5;padding:20px}
    .invoice{background:#fff;max-width:800px;margin:auto;padding:30px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1)}
    .header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
    h2{text-align:center;color:#007bff} /* Đổi sang màu xanh cho booking */
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{border-bottom:1px solid #ddd;padding:10px;text-align:left;}
    th{background:#f9f9f9;}
    .actions{margin-top:30px;text-align:center; padding: 20px 0; border-top: 1px dashed #ccc;}
    button{padding:10px 20px;border:none;border-radius:8px;cursor:pointer;font-weight:bold;margin:5px; background:#007bff; color:white; font-size: 16px;}
    .total-right{text-align:right;margin-top:10px;font-size: 1.2em;}
    hr{border:0; border-top: 1px dashed #ccc; margin: 20px 0;}
    
    /* CSS cho ẩn nút khi in */
    @media print {
        body { background: #fff; padding: 0; }
        .actions { display: none; }
        .invoice { box-shadow: none; border: 1px solid #ccc; margin: 0; }
    }
</style>
</head>
<body>

<div class="invoice" id="invoice-area">
    <div class="header">
        <img src="/TechFixPHP/assets/image/hometech.jpg" alt="Logo" style="height:80px;">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=BookingID:<?=htmlspecialchars($booking['id'])?>" alt="QR">
    </div>
    <h2>BIÊN NHẬN DỊCH VỤ</h2>
    <p><strong>Mã Đơn hàng (Booking):</strong> #<?=htmlspecialchars($booking['id'])?></p>
    <p><strong>Trạng thái:</strong> <?=ucfirst(htmlspecialchars($booking['status']))?></p>
    <p><strong>Ngày in:</strong> <?=date("d/m/Y H:i")?></p>
    <p><strong>Thời gian hẹn:</strong> <?=date('d/m/Y H:i', strtotime($booking['appointment_time']))?></p>
    <hr>
    <h3>Khách hàng</h3>
    <p><strong>Tên:</strong> <?=htmlspecialchars($booking['customer_name'])?></p>
    <p><strong>SĐT:</strong> <?=htmlspecialchars($booking['phone'])?></p>
    <p><strong>Địa chỉ:</strong> <?=htmlspecialchars($booking['address'])?>, <?=htmlspecialchars($booking['district'])?></p>
    <hr>
    
    <h3>Chi Tiết Thanh Toán</h3>
    <table>
        <thead>
            <tr>
                <th>Mô tả Dịch vụ</th>
                <th>Kỹ thuật viên</th>
                <th>Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?=htmlspecialchars($booking['service_name'])?></td>
                <td><?=htmlspecialchars($booking['technician_name'] ?? 'Chưa gán')?></td>
                <td style="text-align:right;"><?=number_format($service_price)?> đ</td>
            </tr>
        </tbody>
    </table>
    
    <p class="total-right"><strong>Tổng dịch vụ:</strong> <?=number_format($service_price)?> đ</p>
    <h3 class="total-right">TỔNG CỘNG: <?=number_format($total)?> đ</h3>
    <p style="text-align:center;margin-top:20px;font-style:italic;">Cảm ơn quý khách!</p>
</div>

<div class="actions">
    <button onclick="window.print()">🖨️ In hoá đơn</button>
</div>

</body>
</html>