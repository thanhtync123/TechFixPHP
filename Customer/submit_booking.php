<?php
// File: /TechFixPHP/Customer/submit_booking.php

header('Content-Type: application/json; charset=utf-8');
session_start();

// 1. Nạp các file cấu hình và thư viện
require_once '../config/db.php';
require_once __DIR__ . '/../libs/pricing.php';   // Chứa hàm tính giá và hàm lấy tọa độ (getCoordinates)
require_once __DIR__ . '/../libs/send_mail.php'; // Chứa hàm gửi mail

// Hàm trả về JSON chuẩn
function respond_json(bool $success, string $message, array $extra = []): void
{
    http_response_code($success ? 200 : 400);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

// 2. Kiểm tra quyền đăng nhập
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? null) !== 'customer') {
    respond_json(false, 'Bạn cần đăng nhập bằng tài khoản khách hàng.');
}

// 3. Nhận dữ liệu JSON từ Frontend
$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    respond_json(false, 'Dữ liệu gửi lên không hợp lệ.');
}

// 4. Lấy thông tin khách hàng từ Session
$customer      = $_SESSION['user'];
$customerId    = (int) ($customer['id'] ?? 0);
$customerName  = $customer['name'] ?? 'Khách hàng';
$customerPhone = $customer['phone'] ?? '';
$customerEmail = $customer['email'] ?? ''; // Email này dùng để gửi thông báo
$defaultAddress = $customer['address'] ?? '';

// 5. Lấy thông tin đặt lịch từ Payload
$serviceId        = isset($payload['ServiceId']) ? (int) $payload['ServiceId'] : 0;
$district         = trim($payload['District'] ?? '');
$appointmentDate  = $payload['AppointmentDate'] ?? '';
$appointmentSlot  = $payload['AppointmentTime'] ?? '';
$address          = trim($payload['Address'] ?? $defaultAddress);

// 6. Validate dữ liệu (Kiểm tra tính hợp lệ)
if ($customerId <= 0) {
    respond_json(false, 'Lỗi phiên làm việc, vui lòng đăng nhập lại.');
}

if ($serviceId <= 0) {
    respond_json(false, 'Vui lòng chọn dịch vụ.');
}

if (empty($district) || empty($appointmentDate) || empty($appointmentSlot)) {
    respond_json(false, 'Vui lòng nhập đầy đủ khu vực, ngày và giờ hẹn.');
}

// Kiểm tra định dạng ngày
$dateObj = DateTime::createFromFormat('Y-m-d', $appointmentDate);
if (!$dateObj) {
    respond_json(false, 'Ngày hẹn không hợp lệ.');
}

// Kiểm tra định dạng giờ
$slotObj = DateTime::createFromFormat('H:i:s', $appointmentSlot);
if (!$slotObj) {
    // Thử format H:i nếu frontend gửi thiếu giây
    $slotObj = DateTime::createFromFormat('H:i', $appointmentSlot);
    if (!$slotObj) {
        respond_json(false, 'Khung giờ không hợp lệ.');
    }
}

// 7. Lấy thông tin và giá dịch vụ từ DB
$stmtService = $conn->prepare("SELECT name, price FROM services WHERE id = ? LIMIT 1");
$stmtService->bind_param('i', $serviceId);
$stmtService->execute();
$serviceResult = $stmtService->get_result();
$service = $serviceResult->fetch_assoc();
$stmtService->close();

if (!$service) {
    respond_json(false, 'Dịch vụ không tồn tại.');
}

// 8. Tính toán giá cuối cùng (Pricing Logic)
// Hàm này nằm trong libs/pricing.php
[$finalPrice, $priceNotes] = calculateSmartQuote((float) $service['price'], $district, $appointmentDate, $appointmentSlot);

// Tạo ghi chú cho đơn hàng
$status = 'pending';
$noteParts = ["Khung giờ {$appointmentSlot}"];
if (!empty($priceNotes)) {
    $noteParts[] = implode(' | ', $priceNotes);
}
$note = implode(' - ', $noteParts);

// ============================================================
// 9. TỰ ĐỘNG LẤY TỌA ĐỘ (GEOCODING)
// ============================================================
$lat = null;
$lng = null;
try {
    $fullAddressToCheck = $address . ', ' . $district;
    
    if (function_exists('getCoordinates')) {
        $coords = getCoordinates($fullAddressToCheck);
        if ($coords) {
            $lat = $coords['lat'];
            $lng = $coords['lng'];
        }
    }
} catch (Exception $e) {
    // Lỗi lấy tọa độ không được làm gián đoạn quy trình đặt lịch
    error_log("Geo Error: " . $e->getMessage());
}

// ============================================================
// 10. LƯU VÀO DATABASE
// ============================================================
$sql = "INSERT INTO bookings (customer_id, customer_name, phone, address, district, service_id, appointment_time, note, final_price, status, lat, lng) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmtInsert = $conn->prepare($sql);

$appointmentDateOnly = $dateObj->format('Y-m-d');
$finalPriceValue = round($finalPrice, 0);

// Bind params: issssissdsdd (12 tham số)
$stmtInsert->bind_param(
    'issssissdsdd',
    $customerId,      // i
    $customerName,    // s
    $customerPhone,   // s
    $address,         // s
    $district,        // s
    $serviceId,       // i
    $appointmentDateOnly, // s
    $note,            // s
    $finalPriceValue, // d (double)
    $status,          // s
    $lat,             // d
    $lng              // d
);

if ($stmtInsert->execute()) {
    $newBookingId = $stmtInsert->insert_id;

    // ============================================================
    // 11. GỬI EMAIL XÁC NHẬN (ĐÃ SỬA CHỮA)
    // ============================================================
    if (!empty($customerEmail)) {
        try {
            // [QUAN TRỌNG] Tạo mảng dữ liệu đúng chuẩn file send_mail.php mới
            $mailData = [
                'customer_name' => $customerName,
                'booking_id'    => $newBookingId
            ];
            
            // Gọi hàm với 3 tham số: Email, Mảng Dữ Liệu, Loại Email ('new')
            sendBookingEmail($customerEmail, $mailData, 'new');
            
        } catch (Exception $e) {
            // Chỉ ghi log, không báo lỗi ra cho khách
            error_log("Mail sending failed for ID $newBookingId: " . $e->getMessage());
        }
    }

    // 12. Trả về kết quả thành công
    respond_json(true, 'Đặt lịch thành công!', [
        'booking_id'   => $newBookingId,
        'final_price'  => $finalPriceValue,
        'has_location' => ($lat && $lng) ? true : false
    ]);

} else {
    // Lỗi khi insert vào DB
    error_log("DB Insert Error: " . $stmtInsert->error);
    respond_json(false, 'Lỗi hệ thống khi lưu đơn hàng. Vui lòng thử lại sau.');
}

$stmtInsert->close();
$conn->close();
?>