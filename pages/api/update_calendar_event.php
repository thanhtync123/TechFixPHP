<?php
// pages/api/update_calendar_event.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

require_once '../../config/db.php';

$id = $_POST['id'] ?? null;
$new_date = $_POST['new_date'] ?? null;

if (!$id || !$new_date) {
    echo json_encode(['success' => false, 'message' => 'Thiếu dữ liệu']); exit;
}

try {
    if (strlen($new_date) <= 10) $new_date .= " 08:00:00"; 
    $formatted_date = date('Y-m-d H:i:s', strtotime($new_date));
    $display_date = date('d/m/Y H:i', strtotime($formatted_date));

    // 1. LẤY EMAIL CỦA CẢ THỢ VÀ KHÁCH
    // u_tech: Bảng user của thợ
    // u_cus: Bảng user của khách (Join qua customer_id)
    $sqlInfo = "
        SELECT 
            b.id, b.customer_name, s.name as service_name,
            u_tech.email as tech_email, u_tech.name as tech_name,
            u_cus.email as cus_email -- Lấy thêm email khách
        FROM bookings b
        LEFT JOIN users u_tech ON b.technician_id = u_tech.id
        LEFT JOIN users u_cus ON b.customer_id = u_cus.id
        LEFT JOIN services s ON b.service_id = s.id
        WHERE b.id = ?
    ";
    $stmtInfo = $conn->prepare($sqlInfo);
    $stmtInfo->bind_param("i", $id);
    $stmtInfo->execute();
    $info = $stmtInfo->get_result()->fetch_assoc();

    // 2. UPDATE DATABASE
    $stmt = $conn->prepare("UPDATE bookings SET appointment_time = ? WHERE id = ?");
    $stmt->bind_param("si", $formatted_date, $id);
    
    if ($stmt->execute()) {
        
        // CẤU HÌNH MAIL
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: TechFix Support <support@techfix.com>' . "\r\n";

        // --- GỬI CHO THỢ ---
        if (!empty($info['tech_email'])) {
            $msgTech = "<h3>Thông báo đổi lịch - Đơn #{$id}</h3>
                        <p>Chào {$info['tech_name']}, lịch làm việc đã thay đổi:</p>
                        <ul>
                            <li>Khách: {$info['customer_name']}</li>
                            <li>Dịch vụ: {$info['service_name']}</li>
                            <li>Giờ MỚI: <b style='color:red'>{$display_date}</b></li>
                        </ul>";
            @mail($info['tech_email'], "[TechFix] Thay đổi lịch làm việc", $msgTech, $headers);
        }

        // --- GỬI CHO KHÁCH (QUAN TRỌNG) ---
        if (!empty($info['cus_email'])) {
            $msgCus = "<h3>Xin chào {$info['customer_name']},</h3>
                       <p>TechFix xin thông báo thay đổi thời gian hẹn cho đơn hàng <b>#{$id}</b>.</p>
                       <p>Dịch vụ: <b>{$info['service_name']}</b></p>
                       <p>Thời gian hẹn mới: <span style='color: #0d6efd; font-size: 16px; font-weight: bold;'>{$display_date}</span></p>
                       <p>Nếu giờ này không phù hợp, vui lòng liên hệ Hotline 1900 1234.</p>
                       <p>Xin lỗi quý khách vì sự bất tiện này.</p>";
            @mail($info['cus_email'], "[TechFix] Cập nhật thời gian hẹn", $msgCus, $headers);
        }

        echo json_encode(['success' => true, 'message' => 'Đã đổi lịch & Gửi mail cho các bên']);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>