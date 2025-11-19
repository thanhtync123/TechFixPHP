<?php
// File: /TechFixPHP/config/mail.php
// Sao chép file này và cập nhật thông số SMTP thực tế của bạn.

return [
    // Thông số kết nối SMTP
    'host'       => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
    'port'       => (int) (getenv('MAIL_PORT') ?: 465),
    'secure'     => getenv('MAIL_SECURE') ?: 'ssl', // ssl (465) hoặc tls (587)

    // Tài khoản SMTP (khuyến nghị dùng Gmail App Password)
    'username'   => getenv('MAIL_USERNAME') ?: 'your_email@gmail.com',
    'password'   => getenv('MAIL_PASSWORD') ?: 'your_app_password',

    // Thông tin hiển thị người gửi
    'from_email' => getenv('MAIL_FROM_ADDRESS') ?: (getenv('MAIL_USERNAME') ?: 'your_email@gmail.com'),
    'from_name'  => getenv('MAIL_FROM_NAME') ?: 'TECHFIX Support',
];

