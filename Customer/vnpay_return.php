<?php
// File: /TechFixPHP/Customer/vnpay_return.php
session_start();
require_once '../config/db.php';
require_once 'config_vnpay.php';

$mailerPath = dirname(__DIR__) . '/libs/send_mail.php';
if (file_exists($mailerPath)) {
    require_once $mailerPath;
} else {
    error_log('Không tìm thấy thư viện gửi mail tại ' . $mailerPath);
}

// 1. Xử lý dữ liệu trả về từ VNPay để kiểm tra tính toàn vẹn (Checksum)
$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
$vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? '';
$vnp_BankCode = $_GET['vnp_BankCode'] ?? 'Không xác định';
$inputData = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

unset($inputData['vnp_SecureHash']);
ksort($inputData);
$i = 0;
$hashData = "";
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}

$secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

// 2. Chuẩn bị giao diện
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kết quả thanh toán - TECHFIX</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; text-align: center; padding: 50px; background: #f4f6f9; }
        .card { background: white; max-width: 600px; margin: 0 auto; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-top: 10px; }
        .success { color: #28a745; font-size: 60px; margin-bottom: 10px; }
        .fail { color: #dc3545; font-size: 60px; margin-bottom: 10px; }
        .info-box { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0; text-align: left; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px dashed #eee; padding-bottom: 10px; }
        .info-row:last-child { border-bottom: none; }
        .btn { display: inline-block; margin-top: 20px; padding: 12px 25px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; transition: 0.3s; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="card">
        <?php
        if ($secureHash == $vnp_SecureHash) {
            if ($vnp_ResponseCode == '00') {
                // ==========================================
                // TRƯỜNG HỢP: THANH TOÁN THÀNH CÔNG (00)
                // ==========================================
                
                $booking_id = (int) ($_GET['vnp_TxnRef'] ?? 0);
                $transaction_code = trim($_GET['vnp_TransactionNo'] ?? '');
                $amount = isset($_GET['vnp_Amount']) ? ($_GET['vnp_Amount'] / 100) : 0; // VNPay nhân 100, giờ chia lại

                // A. Cập nhật CSDL
                $sql = "UPDATE bookings SET payment_status = 'paid', transaction_code = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $transaction_code, $booking_id);
                $stmt->execute();
                $stmt->close();

                // B. Gửi Email xác nhận (Code mới thêm)
                // 1. Lấy thông tin khách hàng để gửi mail
                $sql_user = "SELECT u.email, u.full_name 
                             FROM bookings b 
                             JOIN users u ON b.customer_id = u.id 
                             WHERE b.id = ?";
                
                $stmt_user = $conn->prepare($sql_user);
                $stmt_user->bind_param("i", $booking_id);
                $stmt_user->execute();
                $res_user = $stmt_user->get_result();

                if ($user = $res_user->fetch_assoc()) {
                    if (function_exists('sendBookingEmail')) {
                        $emailSent = sendBookingEmail($user['email'], $user['full_name'], $booking_id, 'paid');
                        if (!$emailSent) {
                            error_log("Không thể gửi email xác nhận thanh toán cho booking #{$booking_id}");
                        }
                    } else {
                        error_log('Hàm sendBookingEmail chưa được khai báo.');
                    }
                }
                $stmt_user->close();

                // C. Hiển thị giao diện thành công
                echo "<div class='success'>✅</div>";
                echo "<h1>Thanh toán thành công!</h1>";
                echo "<p>Cảm ơn bạn đã thanh toán đơn hàng.</p>";
                
                echo "<div class='info-box'>";
                echo "<div class='info-row'><span>Mã đơn hàng:</span> <strong>#". htmlspecialchars((string) $booking_id) ."</strong></div>";
                echo "<div class='info-row'><span>Mã giao dịch:</span> <strong>". htmlspecialchars($transaction_code) ."</strong></div>";
                echo "<div class='info-row'><span>Số tiền:</span> <strong style='color:#d9534f'>" . number_format($amount) . " VND</strong></div>";
                echo "<div class='info-row'><span>Ngân hàng:</span> <strong>" . htmlspecialchars($vnp_BankCode) . "</strong></div>";
                echo "</div>";

            } else {
                // ==========================================
                // TRƯỜNG HỢP: GIAO DỊCH BỊ HỦY HOẶC LỖI
                // ==========================================
                echo "<div class='fail'>❌</div>";
                echo "<h1>Thanh toán thất bại</h1>";
                echo "<p style='color:red'>Giao dịch bị hủy hoặc có lỗi xảy ra.</p>";
            }
        } else {
            // ==========================================
            // TRƯỜNG HỢP: SAI CHỮ KÝ (Hack/Lỗi config)
            // ==========================================
            echo "<div class='fail'>⚠️</div>";
            echo "<h1>Lỗi bảo mật</h1>";
            echo "<p>Chữ ký không hợp lệ (Invalid Signature).</p>";
        }
        ?>
        
        <a href="my_booking.php" class="btn">Quay lại lịch đặt</a>
    </div>
</body>
</html>