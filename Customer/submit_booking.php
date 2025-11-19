<?php
// /TechFixPHP/Customer/submit_booking.php
header('Content-Type: application/json');
session_start();
require_once '../config/db.php';
require_once __DIR__ . '/../libs/pricing.php';
require_once __DIR__ . '/../libs/send_mail.php'; // <--- 1. THÊM DÒNG NÀY ĐỂ NẠP HÀM GỬI MAIL

function respond_json(bool $success, string $message, array $extra = []): void
{
    http_response_code($success ? 200 : 400);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? null) !== 'customer') {
    respond_json(false, 'Bạn cần đăng nhập bằng tài khoản khách hàng.');
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    respond_json(false, 'Dữ liệu không hợp lệ.');
}

$customer      = $_SESSION['user'];
$customerId    = (int) ($customer['id'] ?? 0);
$customerName  = $customer['name'] ?? '';
$customerPhone = $customer['phone'] ?? '';
// Lấy email từ session (đảm bảo lúc login bạn đã lưu email vào session)
$customerEmail = $customer['email'] ?? ''; // <--- 2. LẤY EMAIL KHÁCH HÀNG
$defaultAddress = $customer['address'] ?? '';

$serviceId        = isset($payload['ServiceId']) ? (int) $payload['ServiceId'] : 0;
$district         = trim($payload['District'] ?? '');
$appointmentDate  = $payload['AppointmentDate'] ?? '';
$appointmentSlot  = $payload['AppointmentTime'] ?? '';
$address          = trim($payload['Address'] ?? $defaultAddress);

if ($customerId <= 0 || empty($customerName) || empty($customerPhone)) {
    respond_json(false, 'Không tìm thấy thông tin khách hàng.');
}
// Kiểm tra nếu thiếu email thì báo lỗi hoặc bỏ qua tùy bạn (ở đây mình chỉ log warning nếu thiếu)
if (empty($customerEmail)) {
    error_log("Warning: Khách hàng ID $customerId không có email trong session.");
}

if ($serviceId <= 0) {
    respond_json(false, 'Vui lòng chọn dịch vụ.');
}
if (empty($district) || empty($appointmentDate) || empty($appointmentSlot)) {
    respond_json(false, 'Vui lòng chọn khu vực, ngày và khung giờ.');
}
$dateObj = DateTime::createFromFormat('Y-m-d', $appointmentDate);
if (!$dateObj) {
    respond_json(false, 'Ngày hẹn không hợp lệ.');
}
$slotObj = DateTime::createFromFormat('H:i:s', $appointmentSlot);
if (!$slotObj) {
    respond_json(false, 'Khung giờ không hợp lệ.');
}

$stmtService = $conn->prepare("SELECT name, price FROM services WHERE id = ? LIMIT 1");
$stmtService->bind_param('i', $serviceId);
$stmtService->execute();
$serviceResult = $stmtService->get_result();
$service = $serviceResult->fetch_assoc();
$stmtService->close();

if (!$service) {
    respond_json(false, 'Không tìm thấy dịch vụ.');
}

[$finalPrice, $priceNotes] = calculateSmartQuote((float) $service['price'], $district, $appointmentDate, $appointmentSlot);

$status = 'pending';
$noteParts = ["Khung giờ {$appointmentSlot}"];
if (!empty($priceNotes)) {
    $noteParts[] = implode(' | ', $priceNotes);
}
$note = implode(' - ', $noteParts);

$stmtInsert = $conn->prepare("INSERT INTO bookings (customer_id, customer_name, phone, address, district, service_id, appointment_time, note, final_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$appointmentDateOnly = $dateObj->format('Y-m-d');
$finalPriceValue = round($finalPrice, 0);
$stmtInsert->bind_param(
    'issssissds',
    $customerId,
    $customerName,
    $customerPhone,
    $address,
    $district,
    $serviceId,
    $appointmentDateOnly,
    $note,
    $finalPriceValue,
    $status
);

if ($stmtInsert->execute()) {
    $newBookingId = $stmtInsert->insert_id;

    // <--- 3. THỰC HIỆN GỬI MAIL TẠI ĐÂY --->
    if (!empty($customerEmail)) {
        // Gọi hàm gửi mail, type là 'new' để lấy template xác nhận đơn
        sendBookingEmail($customerEmail, $customerName, $newBookingId, 'new');
    }
    // <--- KẾT THÚC PHẦN GỬI MAIL --->

    respond_json(true, 'Đặt lịch thành công!', [
        'booking_id' => $newBookingId,
        'final_price' => $finalPriceValue,
    ]);
}

respond_json(false, 'Lỗi khi lưu đơn đặt lịch: ' . $stmtInsert->error);
?>