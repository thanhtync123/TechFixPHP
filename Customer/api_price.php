<?php
// /TechFixPHP/Customer/api_price.php
header('Content-Type: application/json');

// 1. KẾT NỐI CSDL CỦA BẠN (ĐỂ LẤY LUẬT GIÁ VÀ GIỜ ĐÃ ĐẶT)
// $conn = new mysqli("localhost", "root", "", "hometech_db");
// if ($conn->connect_error) {
//     echo json_encode(['error' => 'Không thể kết nối CSDL']);
//     exit;
// }

// 2. LẤY DỮ LIỆU TỪ JAVASCRIPT
$service_id = $_GET['service_id'] ?? 0;
$district = $_GET['district'] ?? '';
$selected_date = $_GET['date'] ?? date('Y-m-d');

if (empty($service_id) || empty($district)) {
    echo json_encode(['error' => 'Vui lòng chọn dịch vụ và khu vực.']);
    exit;
}

// 3. LẤY GIÁ GỐC (DEMO - Lấy từ CSDL trong đồ án thật)
$base_price = 0;
// Dựa trên ID dịch vụ trong form của bạn
switch ($service_id) {
    case 1: $base_price = 150000; break; // Sửa điện
    case 2: $base_price = 300000; break; // Điều hòa
    case 3: $base_price = 250000; break; // Tủ lạnh
    case 4: $base_price = 200000; break; // IT
    default: $base_price = 200000;
}

$final_price = $base_price;
$notes = []; // Mảng lưu các ghi chú về giá

// 4. LOGIC TÍNH GIÁ "THÔNG MINH"

// === 4.1. Luật theo Khu vực ===
if ($district === 'Quận 1') {
    $final_price *= 1.15; // Tăng 15%
    $notes[] = "Phụ phí khu vực trung tâm (Quận 1): +15%";
} elseif ($district === 'Hóc Môn') {
    $final_price += 50000; // Cộng 50k
    $notes[] = "Phụ phí khu vực xa (Hóc Môn): +50.000đ";
}

// === 4.2. Luật theo Ngày ===
$dayOfWeek = date('N', strtotime($selected_date));
if ($dayOfWeek >= 6) { // T7 hoặc CN
    $final_price *= 1.2; // Tăng 20%
    $notes[] = "Phụ phí cuối tuần: +20%";
}

// === 4.3. Kiểm tra các slot đã bị đặt ===
// (Truy vấn CSDL: SELECT TIME(appointment_time) FROM bookings WHERE DATE(appointment_time) = ?)
$booked_slots = ['14:00:00']; // Giả sử slot 14:00 đã bị đặt

// 5. TẠO CÁC KHUNG GIỜ VÀ GIÁ CUỐI CÙNG
$all_slots = [
    '09:00:00' => ['note' => ''],
    '11:00:00' => ['note' => ''],
    '14:00:00' => ['note' => ''],
    '16:00:00' => ['note' => ''],
    '18:00:00' => ['note' => 'Giờ cao điểm (+10%)'] // Slot này có luật riêng
];

$available_slots = [];
foreach ($all_slots as $time => $slot) {
    $slot_price = $final_price;
    $is_available = !in_array($time, $booked_slots);
    
    // Áp dụng luật riêng của slot
    if ($time === '18:00:00') {
        $slot_price *= 1.10;
    }

    $available_slots[$time] = [
        'price' => round($slot_price),
        'available' => $is_available,
        'note' => $is_available ? $slot['note'] : 'Đã có người đặt'
    ];
}

// 6. TRẢ KẾT QUẢ VỀ CHO JAVASCRIPT
echo json_encode([
    'base_price' => $base_price,
    'price_notes' => $notes,
    'available_slots' => $available_slots
]);

// $conn->close();
?>