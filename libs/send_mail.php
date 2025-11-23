<?php
// File: /TechFixPHP/libs/send_mail.php

// 1. Nạp thư viện PHPMailer
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
    $manualPath = __DIR__ . '/PHPMailer';
    if (is_dir($manualPath)) {
        $separator = DIRECTORY_SEPARATOR;
        require_once $manualPath . "{$separator}src{$separator}Exception.php";
        require_once $manualPath . "{$separator}src{$separator}PHPMailer.php";
        require_once $manualPath . "{$separator}src{$separator}SMTP.php";
    }
}

/**
 * Gửi email với giao diện HTML chuyên nghiệp
 */
function sendBookingEmail($toEmail, $data, $type = 'new') {
    static $mailConfig = null;

    // Load cấu hình
    if ($mailConfig === null) {
        $defaultConfig = [
            'host'       => 'smtp.gmail.com',
            'port'       => 465,
            'secure'     => 'ssl',
            'username'   => '22004073@st.vlute.edu.vn',
            'password'   => 'your_app_password', // <--- ĐIỀN APP PASSWORD
            'from_email' => 'funnyofficials@gmail.com',
            'from_name'  => 'TECHFIX Support',
        ];
        $configPath = __DIR__ . '/../config/mail.php';
        if (file_exists($configPath)) {
            $loadedConfig = require $configPath;
            $mailConfig = is_array($loadedConfig) ? array_merge($defaultConfig, $loadedConfig) : $defaultConfig;
        } else {
            $mailConfig = $defaultConfig;
        }
    }

    $phpMailerClass = '\\PHPMailer\\PHPMailer\\PHPMailer';
    $canUsePHPMailer = class_exists($phpMailerClass);

    try {
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email không hợp lệ.');
        }

        // --- XỬ LÝ DỮ LIỆU ---
        $customerName = htmlspecialchars($data['customer_name'] ?? 'Quý khách');
        $bookingId    = $data['booking_id'] ?? '...';
        
        // Dữ liệu Assigned
        $techName     = htmlspecialchars($data['technician'] ?? '');
        $techPhone    = htmlspecialchars($data['tech_phone'] ?? '');
        $appointment  = htmlspecialchars($data['appointment'] ?? '');

        // --- STYLE CHUNG (Màu sắc thương hiệu) ---
        $brandColor = '#0056b3'; // Xanh đậm TechFix
        $bgColor    = '#f4f6f8'; // Màu nền xám nhẹ

        // --- HEAD CHUNG CỦA EMAIL ---
        $emailHeader = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { margin: 0; padding: 0; background-color: $bgColor; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
                .container { width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
                .header { background-color: $brandColor; padding: 30px 20px; text-align: center; }
                .header h1 { color: #ffffff; margin: 0; font-size: 24px; letter-spacing: 1px; }
                .content { padding: 40px 30px; color: #333333; line-height: 1.6; }
                .info-box { background-color: #f8f9fa; border-left: 4px solid $brandColor; padding: 15px; margin: 20px 0; border-radius: 4px; }
                .btn { display: inline-block; padding: 12px 25px; background-color: $brandColor; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold; margin-top: 20px; }
                .footer { background-color: #e9ecef; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; }
                .highlight { color: $brandColor; font-weight: bold; }
            </style>
        </head>
        <body>
            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color: $bgColor; padding: 20px;'>
                <tr>
                    <td align='center'>
                        <div class='container'>
                            <div class='header'>
                                <h1>TECHFIX SERVICE</h1>
                                <div style='color: rgba(255,255,255,0.8); font-size: 14px; margin-top: 5px;'>Sửa đúng lỗi - Báo đúng giá</div>
                            </div>
        ";

        // --- FOOTER CHUNG ---
        $emailFooter = "
                            <div class='footer'>
                                <p>Bạn nhận được email này vì đã sử dụng dịch vụ tại <b>TECHFIX</b>.</p>
                                <p>Địa chỉ: 73 Nguyễn Huệ, TP. Vĩnh Long | Hotline: 1900 1234</p>
                                <p>&copy; " . date('Y') . " TechFix Inc. All rights reserved.</p>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        // --- NỘI DUNG CHI TIẾT TỪNG LOẠI ---
        $bodies = [
            // 1. ĐẶT LỊCH MỚI
            'new' => "
                <div class='content'>
                    <h2 style='color: #333; margin-top: 0;'>🎉 Đặt lịch thành công!</h2>
                    <p>Xin chào <strong>$customerName</strong>,</p>
                    <p>Cảm ơn bạn đã tin tưởng dịch vụ của TechFix. Đơn đặt lịch của bạn đã được ghi nhận thành công.</p>
                    
                    <div class='info-box'>
                        <table width='100%'>
                            <tr>
                                <td style='padding: 5px 0; color: #666;'>Mã đơn hàng:</td>
                                <td style='text-align: right; font-weight: bold;'>#$bookingId</td>
                            </tr>
                            <tr>
                                <td style='padding: 5px 0; color: #666;'>Trạng thái:</td>
                                <td style='text-align: right; color: #ff9800; font-weight: bold;'>Đang chờ xác nhận</td>
                            </tr>
                        </table>
                    </div>

                    <p>Chúng tôi sẽ sớm liên hệ để xác nhận tình trạng thiết bị và điều phối kỹ thuật viên.</p>
                    
                    <center>
                        <a href='#' class='btn'>Xem chi tiết đơn hàng</a>
                    </center>
                </div>
            ",

            // 2. THANH TOÁN THÀNH CÔNG
            'paid' => "
                <div class='content'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <img src='https://cdn-icons-png.flaticon.com/512/190/190411.png' width='64' alt='Success' />
                    </div>
                    <h2 style='text-align: center; color: #28a745; margin-top: 0;'>Thanh Toán Thành Công</h2>
                    <p>Xin chào <strong>$customerName</strong>,</p>
                    <p>TechFix xác nhận đã nhận được thanh toán cho đơn hàng <b>#$bookingId</b>.</p>

                    <div class='info-box' style='border-left-color: #28a745;'>
                        <p style='margin: 0; text-align: center; font-size: 16px;'>Cảm ơn bạn đã sử dụng dịch vụ!</p>
                    </div>

                    <p>Hóa đơn điện tử sẽ được lưu trữ trong phần lịch sử đơn hàng của bạn.</p>
                </div>
            ",

            // 3. GÁN KỸ THUẬT VIÊN (Assigned)
            'assigned' => "
                <div class='content'>
                    <h2 style='color: #0056b3; margin-top: 0;'>🚀 Kỹ thuật viên đang đến!</h2>
                    <p>Xin chào <strong>$customerName</strong>,</p>
                    <p>Đơn hàng <b>#$bookingId</b> của bạn đã được tiếp nhận. Dưới đây là thông tin kỹ thuật viên sẽ hỗ trợ bạn:</p>

                    <div style='background-color: #e7f1ff; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0;'>
                        <img src='https://cdn-icons-png.flaticon.com/512/4006/4006173.png' width='60' style='margin-bottom: 10px;'>
                        <h3 style='margin: 5px 0; color: #333;'>$techName</h3>
                        <p style='margin: 5px 0; font-size: 18px; font-weight: bold; color: #0056b3;'>📞 $techPhone</p>
                        <p style='margin: 5px 0; color: #666; font-size: 14px;'>Thời gian dự kiến: <b>$appointment</b></p>
                    </div>

                    <p>Vui lòng chú ý điện thoại để nhận cuộc gọi xác nhận từ kỹ thuật viên trước khi đến.</p>
                </div>
            "
        ];

        if (!isset($bodies[$type])) {
            throw new Exception("Loại email không xác định: $type");
        }

        // Ghép các phần lại thành HTML hoàn chỉnh
        $finalHtmlBody = $emailHeader . $bodies[$type] . $emailFooter;

        // Nội dung Plain text (cho máy cũ)
        $plainTextBody = strip_tags($finalHtmlBody);

        // --- GỬI MAIL (PHPMailer) ---
        if ($canUsePHPMailer) {
            $mail = new $phpMailerClass(true);
            $mail->isSMTP();
            $mail->SMTPSecure = (strtolower($mailConfig['secure']) === 'tls') ? 'tls' : 'ssl';
            $mail->Host       = $mailConfig['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $mailConfig['username'];
            $mail->Password   = $mailConfig['password'];
            $mail->Port       = $mailConfig['port'];
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
            $mail->addAddress($toEmail, $customerName);

            $mail->isHTML(true);
            // Subject theo từng loại
            $subjects = [
                'new'      => "[TECHFIX] ✅ Xác nhận đơn hàng #$bookingId",
                'paid'     => "[TECHFIX] 💰 Thanh toán thành công #$bookingId",
                'assigned' => "[TECHFIX] 🛠️ Kỹ thuật viên đã nhận lịch #$bookingId"
            ];
            $mail->Subject = $subjects[$type] ?? "Thông báo từ TechFix";
            
            $mail->Body    = $finalHtmlBody;
            $mail->AltBody = $plainTextBody;

            $mail->send();
        } else {
            // Fallback mail() thuần
            $headers  = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: {$mailConfig['from_name']} <{$mailConfig['from_email']}>" . "\r\n";
            
            $subject = "[TECHFIX] Thông báo đơn hàng #$bookingId";
            mail($toEmail, $subject, $finalHtmlBody, $headers);
        }

        return true;

    } catch (\Throwable $e) {
        error_log("Mail Error: " . $e->getMessage());
        return false;
    }
}
?>