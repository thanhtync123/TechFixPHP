<?php
// PROFESSIONAL INVOICE PAGE WITH PRINT, QR, LOGO, EMAIL BUTTON
include '../../config/db.php';
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
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Hóa đơn thanh toán</title>
<style>
 body{font-family:Arial;background:#f5f5f5;padding:20px}
 .invoice{background:#fff;max-width:800px;margin:auto;padding:30px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1)}
 .header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
 h2{text-align:center;color:#e74c3c}
 table{width:100%;border-collapse:collapse;margin-top:10px}
 th,td{border-bottom:1px solid #ddd;padding:8px}
 .actions{margin-top:30px;text-align:center}
 button{padding:8px 16px;border:none;border-radius:8px;cursor:pointer;font-weight:bold;margin:5px}
</style>
</head>
<body>
<div class="invoice" id="invoice-area">
  <div class="header">
    <img src="/TechFixPHP/assets/image/hometech.jpg" alt="Logo" style="height:100px;">
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?=$orderId?>" alt="QR">
  </div>
  <h2>HÓA ĐƠN THANH TOÁN</h2>
  <p><strong>Mã đơn hàng:</strong> <?=htmlspecialchars($orderId)?></p>
  <p><strong>Trạng thái:</strong> Đang xử lý</p>
  <p><strong>Ngày in:</strong> <?=date("d/m/Y H:i")?></p>
  <p><strong>Thời gian hẹn:</strong> <?=htmlspecialchars($scheduleTime)?></p>
  <hr>
  <h3>Khách hàng</h3>
  <p><strong>Tên:</strong> <?=$customerName?></p>
  <p><strong>SĐT:</strong> <?=$phone?></p>
  <p><strong>Địa chỉ:</strong> <?=$address?></p>
  <hr>
  <h3>Dịch vụ</h3>
  <p><strong><?=$serviceName?></strong> - <?=number_format($servicePrice)?> đ</p>
  <hr>
  <h3>Thiết bị</h3>
  <table><tr><th>Tên</th><th>Đơn giá</th><th>SL</th><th>Thành tiền</th></tr>
  <?php $totalEquip=0; foreach($equipments as $eq){$sum=$eq['price']*$eq['quantity'];$totalEquip+=$sum;echo"<tr><td>{$eq['name']}</td><td>".number_format($eq['price'])."</td><td>{$eq['quantity']}</td><td>".number_format($sum)."</td></tr>";}?>
  </table>
  <p style="text-align:right;margin-top:10px;"><strong>Tổng thiết bị:</strong> <?=number_format($totalEquip)?> đ</p>
  <p style="text-align:right;"><strong>Tổng dịch vụ:</strong> <?=number_format($servicePrice)?> đ</p>
  <h3 style="text-align:right;">TỔNG CỘNG: <?=number_format($total)?> đ</h3>
  <p style="text-align:center;margin-top:20px;font-style:italic;">Cảm ơn quý khách!</p>
</div>
<div class="actions">
  <button onclick="window.print()">In hoá đơn</button>
  <button onclick="sendEmail()">Gửi email</button>
</div>
<script>
function sendEmail(){alert('Tính năng gửi email sẽ được tích hợp SMTP ở backend.')}
</script>
</body>
</html>