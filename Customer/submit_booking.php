<?php
// /TechFixPHP/Customer/submit_booking.php
header('Content-Type: application/json');

// Lấy dữ liệu JSON được gửi từ JavaScript (Fetch)
$data = json_decode(file_get_contents("php://input"), true);

// 1. LẤY DỮ LIỆU
$idCustomer = $data['IdCustomer'] ?? '';
$customerName = $data['CustomerName'] ?? '';
$phone = $data['Phone'] ?? '';
$address = $data['Address'] ?? '';
$district = $data['District'] ?? ''; // Thêm trường mới
$serviceId = $data['ServiceId'] ?? '';
$finalPrice = $data['FinalPrice'] ?? 0; // Thêm trường mới
$status = 'pending';

// Kết hợp ngày và giờ để tạo datetime chuẩn
$appointmentDate = $data['AppointmentDate'] ?? '';
$appointmentTime = $data['AppointmentTime'] ?? '';
$fullAppointmentTime = $appointmentDate . ' ' . $appointmentTime;

// 2. KẾT NỐI CSDL
$conn = new mysqli("localhost", "root", "", "hometech_db");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối CSDL']);
    exit;
}

// 3. THÊM DỮ LIỆU
// (Bạn cần thêm cột 'district' và 'final_price' vào bảng 'bookings' của mình)
// Ví dụ: ALTER TABLE bookings ADD district VARCHAR(100), ADD final_price DECIMAL(10, 2);

$stmt = $conn->prepare("INSERT INTO bookings 
    (customer_id, customer_name, phone, address, district, service_id, appointment_time, final_price, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("issssisds", 
    $idCustomer, 
    $customerName, 
    $phone, 
    $address, 
    $district, // Thêm
    $serviceId, 
    $fullAppointmentTime, 
    $finalPrice, // Thêm
    $status
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Đặt lịch thành công!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu đơn đặt lịch: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>