<?php
// File: /TechFixPHP/libs/send_mail.php

// 1. Nạp thư viện PHPMailer
// Ưu tiên autoload của Composer, fallback sang thư mục thủ công trong libs/PHPMailer
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

function sendBookingEmail($toEmail, $customerName, $bookingId, $type = 'new') {
    static $mailConfig = null;

    if ($mailConfig === null) {
        $defaultConfig = [
            'host'       => 'smtp.gmail.com',
            'port'       => 465,
            'secure'     => 'ssl', // ssl (465) hoặc tls (587)
            'username'   => '22004073@st.vlute.edu.vn',
            'password'   => 'your_app_password',
            'from_email' => 'funnyofficials@gmail.com',
            'from_name'  => 'TECHFIX Support',
        ];

        $configPath = __DIR__ . '/../config/mail.php';
        if (file_exists($configPath)) {
            $loadedConfig = require $configPath;
            if (is_array($loadedConfig)) {
                $mailConfig = array_merge($defaultConfig, $loadedConfig);
            } else {
                $mailConfig = $defaultConfig;
            }
        } else {
            $mailConfig = $defaultConfig;
        }
    }

    $phpMailerClass = '\\PHPMailer\\PHPMailer\\PHPMailer';
    $canUsePHPMailer = class_exists($phpMailerClass);

    try {
        // =================================================================
        // 2. CẤU HÌNH SERVER GMAIL (QUAN TRỌNG)
        // =================================================================
        $fromEmail = !empty($mailConfig['from_email']) ? $mailConfig['from_email'] : $mailConfig['username'];
        $fromName  = !empty($mailConfig['from_name']) ? $mailConfig['from_name'] : 'TECHFIX Support';

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Địa chỉ email khách hàng không hợp lệ.');
        }

        $emailTemplates = [
            'new' => [
                'subject' => "[TECHFIX] Xác nhận đặt lịch thành công - Mã #$bookingId",
                'html'    => "
                    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <div style='background-color: #007bff; padding: 15px; text-align: center;'>
                            <h2 style='color: #fff; margin: 0;'>TECHFIX - Đặt Lịch Thành Công</h2>
                        </div>
                        <div style='padding: 20px; border: 1px solid #ddd;'>
                            <p>Xin chào <strong>$customerName</strong>,</p>
                            <p>Cảm ơn bạn đã tin tưởng sử dụng dịch vụ của chúng tôi.</p>
                            <p>Đơn đặt lịch <b>#$bookingId</b> của bạn đã được hệ thống ghi nhận và đang chờ xử lý.</p>
                            <p>Kỹ thuật viên sẽ liên hệ với bạn trong thời gian sớm nhất để xác nhận.</p>
                            <br>
                            <p>Nếu cần hỗ trợ gấp, vui lòng gọi hotline: <b>1900 1234</b></p>
                        </div>
                        <div style='text-align: center; padding-top: 10px; font-size: 12px; color: #888;'>
                            <p>Đây là email tự động, vui lòng không trả lời email này.</p>
                        </div>
                    </div>
                ",
                'text'    => "Xin chào $customerName,\nĐơn đặt lịch #$bookingId đã được ghi nhận và đang chờ xử lý.",
            ],
            'paid' => [
                'subject' => "[TECHFIX] Thanh toán thành công - Đơn hàng #$bookingId",
                'html'    => "
                    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <div style='background-color: #28a745; padding: 15px; text-align: center;'>
                            <h2 style='color: #fff; margin: 0;'>Thanh Toán Thành Công!</h2>
                        </div>
                        <div style='padding: 20px; border: 1px solid #ddd;'>
                            <p>Xin chào <strong>$customerName</strong>,</p>
                            <p>Hệ thống TECHFIX xác nhận đã nhận được thanh toán cho đơn hàng <b>#$bookingId</b>.</p>
                            <p style='font-size: 18px; color: #28a745; font-weight: bold;'>
                                ✅ Trạng thái: Đã thanh toán
                            </p>
                            <p>Kỹ thuật viên sẽ đến địa chỉ của bạn đúng theo lịch hẹn.</p>
                            <p>Bạn có thể xem chi tiết đơn hàng và tải hóa đơn tại website.</p>
                        </div>
                        <div style='text-align: center; padding-top: 10px; font-size: 12px; color: #888;'>
                            <p>Cảm ơn bạn đã lựa chọn TECHFIX!</p>
                        </div>
                    </div>
                ",
                'text'    => "Xin chào $customerName,\nTechFix xác nhận đã nhận thanh toán cho đơn hàng #$bookingId.",
            ],
        ];

        if (!isset($emailTemplates[$type])) {
            throw new Exception('Không xác định được loại email cần gửi.');
        }

        $template = $emailTemplates[$type];

        if ($canUsePHPMailer) {
            $mail = new $phpMailerClass(true);

            $mail->isSMTP();
            $securePref = strtolower((string) $mailConfig['secure']) === 'tls' ? 'tls' : 'ssl';
            $secureConst = ($securePref === 'tls') ? 'ENCRYPTION_STARTTLS' : 'ENCRYPTION_SMTPS';
            $mail->SMTPSecure = constant($phpMailerClass . '::' . $secureConst);

            $mail->Host       = $mailConfig['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $mailConfig['username'];
            $mail->Password   = $mailConfig['password'];
            $mail->Port       = $mailConfig['port'];
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $customerName);
            $mail->isHTML(true);
            $mail->Subject = $template['subject'];
            $mail->Body    = $template['html'];
            $mail->AltBody = $template['text'];
            $mail->send();
        } else {
            // Fallback: sử dụng mail() thuần nếu PHPMailer chưa được cài
            $headers = [];
            $headers[] = "From: {$fromName} <{$fromEmail}>";
            $headers[] = "Reply-To: {$fromEmail}";
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/html; charset=UTF-8";

            $sent = mail($toEmail, $template['subject'], $template['html'], implode("\r\n", $headers));
            if (!$sent) {
                throw new RuntimeException('Không thể gửi email bằng hàm mail(). Vui lòng cài đặt PHPMailer và cấu hình SMTP.');
            }
        }

        return true;
    } catch (\Throwable $e) {
        error_log("Mail Error: " . $e->getMessage());
        return false;
    }
}
?>