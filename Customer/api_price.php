<?php
header('Content-Type: application/json');

require_once '../config/db.php';
require_once __DIR__ . '/../libs/pricing.php';

if ($conn->connect_error) {
    echo json_encode(['error' => 'Không thể kết nối CSDL']);
    exit;
}

$service_id    = isset($_GET['service_id']) ? (int) $_GET['service_id'] : 0;
$district      = trim($_GET['district'] ?? '');
$selected_date = $_GET['date'] ?? date('Y-m-d');

if ($service_id <= 0 || empty($district)) {
    echo json_encode(['error' => 'Vui lòng chọn dịch vụ và khu vực.']);
    exit;
}

$stmt = $conn->prepare("SELECT price FROM services WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $service_id);
$stmt->execute();
$result = $stmt->get_result();
$service = $result->fetch_assoc();
$stmt->close();

if (!$service) {
    echo json_encode(['error' => 'Không tìm thấy dịch vụ.']);
    exit;
}

$base_price = (float) $service['price'];
$notes = [];

[$defaultQuote, $defaultNotes] = calculateSmartQuote($base_price, $district, $selected_date);
$notes = $defaultNotes;

$booked_slots = ['14:00:00']; // TODO: thay bằng dữ liệu thực tế khi lưu slot
$all_slots = [
    '09:00:00' => ['note' => ''],
    '11:00:00' => ['note' => ''],
    '14:00:00' => ['note' => ''],
    '16:00:00' => ['note' => ''],
    '18:00:00' => ['note' => 'Giờ cao điểm']
];

$available_slots = [];
foreach ($all_slots as $time => $slot) {
    [$slotPrice, $slotNotes] = calculateSmartQuote($base_price, $district, $selected_date, $time);
    $is_available = !in_array($time, $booked_slots, true);

    $noteText = $slot['note'];
    if (empty($noteText) && !empty($slotNotes)) {
        $noteText = implode(', ', $slotNotes);
    } elseif (!empty($slotNotes)) {
        $noteText .= ' - ' . implode(', ', $slotNotes);
    }

    $available_slots[$time] = [
        'price' => round($slotPrice),
        'available' => $is_available,
        'note' => $is_available ? $noteText : 'Đã có người đặt'
    ];
}

echo json_encode([
    'base_price'    => $base_price,
    'price_notes'   => $notes,
    'available_slots' => $available_slots
]);

$conn->close();
?>