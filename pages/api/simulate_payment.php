<?php
// pages/api/simulate_payment.php
session_start();
require_once '../../config/db.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$booking_id = $input['booking_id'] ?? 0;

if ($booking_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Mã đơn hàng không hợp lệ']); exit;
}

// 1. Lấy thông tin đơn hàng và khách hàng
$sql = "
    SELECT 
        b.id, b.final_price, b.appointment_time, b.address,
        c.name as customer_name, c.email as customer_email,
        s.name as service_name
    FROM bookings b
    LEFT JOIN users c ON b.customer_id = c.id
    LEFT JOIN services s ON b.service_id = s.id
    WHERE b.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']); exit;
}

// 2. Cập nhật trạng thái thanh toán (Giả sử bạn có cột payment_status hoặc dùng status)
// Ở đây mình ví dụ update status thành 'confirmed' (hoặc 'paid' nếu bạn có cột riêng)
$update = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
$update->bind_param("i", $booking_id);

if ($update->execute()) {
    
    // 3. GỬI EMAIL XÁC NHẬN THANH TOÁN (HTML CHUYÊN NGHIỆP)
    if (!empty($order['customer_email'])) {
        $subject = "[TechFix] Xác nhận thanh toán thành công - Đơn #{$booking_id}";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: TechFix Payment <payment@techfix.com>' . "\r\n"; // Tên người gửi uy tín
        
        $formattedPrice = number_format($order['final_price'], 0, ',', '.');
        $date = date('H:i d/m/Y', strtotime($order['appointment_time']));
        $currentDate = date('d/m/Y H:i');

        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                .invoice-box { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; border-top: 5px solid #28a745; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { text-align: center; margin-bottom: 20px; }
                .header h1 { color: #28a745; margin: 0; font-size: 24px; text-transform: uppercase; }
                .header p { color: #777; font-size: 14px; }
                .content { margin-bottom: 20px; }
                .info-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                .info-table td { padding: 10px; border-bottom: 1px solid #eee; }
                .info-table td:first-child { font-weight: bold; color: #555; width: 40%; }
                .total { font-size: 18px; color: #d32f2f; font-weight: bold; }
                .footer { text-align: center; font-size: 12px; color: #999; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
                .btn { display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class='invoice-box'>
                <div class='header'>
                    <h1>Thanh Toán Thành Công</h1>
                    <p>Mã giao dịch: TF-" . time() . "</p>
                </div>
                
                <div class='content'>
                    <p>Xin chào <strong>{$order['customer_name']}</strong>,</p>
                    <p>TechFix đã nhận được khoản thanh toán của bạn. Đơn hàng của bạn đã được xác nhận và kỹ thuật viên sẽ đến đúng giờ.</p>
                    
                    <table class='info-table'>
                        <tr>
                            <td>Mã đơn hàng:</td>
                            <td>#{$booking_id}</td>
                        </tr>
                        <tr>
                            <td>Dịch vụ:</td>
                            <td>{$order['service_name']}</td>
                        </tr>
                        <tr>
                            <td>Thời gian hẹn:</td>
                            <td>{$date}</td>
                        </tr>
                        <tr>
                            <td>Địa chỉ:</td>
                            <td>{$order['address']}</td>
                        </tr>
                        <tr>
                            <td>Số tiền:</td>
                            <td class='total'>{$formattedPrice} VNĐ</td>
                        </tr>
                        <tr>
                            <td>Trạng thái:</td>
                            <td style='color: green; font-weight: bold;'>✅ Đã thanh toán</td>
                        </tr>
                    </table>

                    <div style='text-align:center'>
                        <a href='http://{$_SERVER['HTTP_HOST']}/TechFixPHP/Customer/booking_detail.php?id={$booking_id}' class='btn'>Xem Chi Tiết Đơn Hàng</a>
                    </div>
                </div>

                <div class='footer'>
                    Email này được gửi tự động lúc $currentDate<br>
                    Cảm ơn quý khách đã sử dụng dịch vụ của TechFix!
                </div>
            </div>
        </body>
        </html>
        ";

        @mail($order['customer_email'], $subject, $message, $headers);
    }

    echo json_encode(['success' => true, 'message' => 'Thanh toán thành công! Email xác nhận đã được gửi.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật Database']);
}
?>