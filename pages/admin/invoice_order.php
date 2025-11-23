<?php
session_start();
require_once '../../config/db.php';

// Khởi tạo biến mặc định để tránh lỗi Undefined
$orderId = '';
$customerName = '';
$phone = '';
$address = '';
$serviceName = '';
$servicePrice = 0;
$technicalName = 'Chưa phân công';
$scheduleTime = '';
$total = 0;
$status = 'Chưa xác định';
$equipments = [];

// TRƯỜNG HỢP 1: Nhận dữ liệu từ Form POST (admin_dispatch.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = $_POST['orderId'] ?? '';
    $customerName = $_POST['customerName'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $serviceName = $_POST['serviceName'] ?? '';
    $servicePrice = intval($_POST['servicePrice'] ?? 0);
    $technicalName = $_POST['technicalName'] ?? '';
    $scheduleTime = $_POST['scheduleTime'] ?? '';
    $total = intval($_POST['total'] ?? 0);
    $equipments = json_decode($_POST['equipments'] ?? '[]', true);
    $status = 'Đang xử lý'; // Mặc định nếu từ dispatch
} 

// TRƯỜNG HỢP 2: Nhận ID từ URL GET (orders.php) -> PHẢI TRUY VẤN LẠI DB
elseif (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $sql = "
        SELECT 
            b.id, b.status, b.appointment_time, b.final_price,
            c.name as customer_name, c.phone, c.address,
            s.name as service_name, s.price as service_price,
            t.name as tech_name
        FROM bookings b
        LEFT JOIN users c ON b.customer_id = c.id
        LEFT JOIN services s ON b.service_id = s.id
        LEFT JOIN users t ON b.technician_id = t.id
        WHERE b.id = ?
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if ($order) {
        $orderId = $order['id'];
        $customerName = $order['customer_name'];
        $phone = $order['phone'];
        $address = $order['address']; // Lấy địa chỉ từ user (hoặc từ booking nếu có cột address riêng)
        
        $serviceName = $order['service_name'];
        $servicePrice = $order['service_price']; // Giá gốc dịch vụ
        
        $technicalName = $order['tech_name'] ?? 'Chưa phân công';
        $scheduleTime = date('d/m/Y H:i', strtotime($order['appointment_time']));
        $status = ucfirst($order['status']);
        
        // Tổng tiền cuối cùng (đã bao gồm phụ phí/giảm giá nếu có)
        $total = $order['final_price']; 
        
        // TODO: Nếu bạn có bảng `booking_equipments` lưu vật tư đi kèm, hãy query thêm ở đây
        // Hiện tại để trống mảng thiết bị
        $equipments = [];
    } else {
        die("Không tìm thấy đơn hàng #$id");
    }
} else {
    die("Truy cập không hợp lệ.");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hóa đơn #<?= htmlspecialchars($orderId) ?></title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .invoice { background: #fff; max-width: 800px; margin: auto; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #f0f0f0; padding-bottom: 20px; }
        .logo { height: 80px; object-fit: contain; }
        h2 { text-align: center; color: #0d6efd; margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 1px; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        .info-box h3 { font-size: 16px; color: #555; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px; }
        .info-line { margin-bottom: 8px; font-size: 14px; }
        .info-line strong { color: #333; width: 100px; display: inline-block; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f8f9fa; color: #333; font-weight: bold; text-align: left; padding: 12px; border-bottom: 2px solid #ddd; }
        td { padding: 12px; border-bottom: 1px solid #eee; color: #555; }
        .text-right { text-align: right; }
        .total-row td { font-size: 18px; font-weight: bold; color: #d32f2f; border-top: 2px solid #333; }

        .actions { margin-top: 30px; text-align: center; }
        button { padding: 10px 25px; border: none; border-radius: 50px; cursor: pointer; font-weight: bold; margin: 0 10px; transition: 0.3s; font-size: 14px; }
        .btn-print { background: #0d6efd; color: white; }
        .btn-print:hover { background: #0b5ed7; box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3); }
        .btn-email { background: #fff; border: 1px solid #0d6efd; color: #0d6efd; }
        .btn-email:hover { background: #e7f1ff; }

        @media print {
            body { background: white; padding: 0; }
            .invoice { box-shadow: none; max-width: 100%; padding: 0; }
            .actions { display: none; }
        }
    </style>
</head>
<body>

<div class="invoice">
    <div class="header">
        <img src="/TechFixPHP/assets/image/hometech.jpg" alt="Logo" class="logo">
        <div style="text-align: right;">
            <h2 style="margin-bottom: 10px;">HÓA ĐƠN DỊCH VỤ</h2>
            <div style="font-size: 13px; color: #777;">Mã đơn: <strong>#<?= htmlspecialchars($orderId) ?></strong></div>
            <div style="font-size: 13px; color: #777;">Ngày in: <?= date("d/m/Y H:i") ?></div>
        </div>
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=Order-<?= $orderId ?>" alt="QR">
    </div>

    <div class="info-grid">
        <div class="info-box">
            <h3>THÔNG TIN KHÁCH HÀNG</h3>
            <div class="info-line"><strong>Họ tên:</strong> <?= htmlspecialchars($customerName) ?></div>
            <div class="info-line"><strong>Điện thoại:</strong> <?= htmlspecialchars($phone) ?></div>
            <div class="info-line"><strong>Địa chỉ:</strong> <?= htmlspecialchars($address) ?></div>
        </div>
        <div class="info-box">
            <h3>THÔNG TIN ĐƠN HÀNG</h3>
            <div class="info-line"><strong>Trạng thái:</strong> <?= htmlspecialchars($status) ?></div>
            <div class="info-line"><strong>Ngày hẹn:</strong> <?= htmlspecialchars($scheduleTime) ?></div>
            <div class="info-line"><strong>Kỹ thuật viên:</strong> <?= htmlspecialchars($technicalName) ?></div>
        </div>
    </div>

    <h3>CHI TIẾT THANH TOÁN</h3>
    <table>
        <thead>
            <tr>
                <th>Mô tả dịch vụ / Vật tư</th>
                <th class="text-right">Đơn giá</th>
                <th class="text-right" style="width: 50px;">SL</th>
                <th class="text-right">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <b><?= htmlspecialchars($serviceName) ?></b>
                    <br><small style="color:#888">Dịch vụ sửa chữa/bảo trì</small>
                </td>
                <td class="text-right"><?= number_format($servicePrice) ?> đ</td>
                <td class="text-right">1</td>
                <td class="text-right"><?= number_format($servicePrice) ?> đ</td>
            </tr>

            <?php 
            $totalEquip = 0;
            foreach ($equipments as $eq): 
                $sum = $eq['price'] * $eq['quantity'];
                $totalEquip += $sum;
            ?>
                <tr>
                    <td><?= htmlspecialchars($eq['name']) ?></td>
                    <td class="text-right"><?= number_format($eq['price']) ?> đ</td>
                    <td class="text-right"><?= $eq['quantity'] ?></td>
                    <td class="text-right"><?= number_format($sum) ?> đ</td>
                </tr>
            <?php endforeach; ?>

            <tr class="total-row">
                <td colspan="3" class="text-right">TỔNG THANH TOÁN:</td>
                <td class="text-right"><?= number_format($total > 0 ? $total : ($servicePrice + $totalEquip)) ?> đ</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 40px; text-align: center; font-style: italic; color: #666; font-size: 13px;">
        <p>Cảm ơn quý khách đã tin tưởng sử dụng dịch vụ của TechFix!</p>
        <p>Mọi thắc mắc xin liên hệ hotline: <strong>1900 1234</strong></p>
    </div>
</div>

<div class="actions">
    <button onclick="window.print()" class="btn-print">🖨️ In hóa đơn</button>
    <button onclick="alert('Tính năng đang phát triển')" class="btn-email">📧 Gửi Email</button>
</div>

</body>
</html>