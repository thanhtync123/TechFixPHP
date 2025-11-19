<?php
// /pages/api/get_calendar_events.php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([]);
    exit;
}
header('Content-Type: application/json');
include "../../config/db.php"; // Đảm bảo đường dẫn đúng

/* * Truy vấn tất cả các đơn hàng đã được xác nhận hoặc hoàn thành.
 * Chúng ta sẽ lấy tên dịch vụ và tên khách hàng để làm Tiêu đề (title).
 * Và lấy `appointment_time` làm ngày bắt đầu (start).
 */

$query = "
    SELECT 
        b.id,
        b.appointment_time,
        b.status,
        s.name AS service_name,
        b.customer_name
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    WHERE 
        b.status IN ('confirmed', 'completed')
";

$result = $conn->query($query);
$events = []; // Mảng rỗng để chứa các sự kiện

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        
        // Gán màu dựa trên trạng thái
        $color = '#28a745'; // Mặc định là Xanh lá (Completed)
        if ($row['status'] == 'confirmed') {
            $color = '#007bff'; // Xanh dương (Confirmed)
        }

        // Tạo mảng sự kiện theo chuẩn FullCalendar
        $events[] = [
            'id' => $row['id'],
            'title' => $row['service_name'] . ' (' . $row['customer_name'] . ')',
            'start' => $row['appointment_time'], // FullCalendar tự hiểu Y-m-d H:i:s
            'color' => $color,
            'borderColor' => $color
        ];
    }
}

// Trả về dữ liệu dưới dạng JSON
echo json_encode($events);
?>