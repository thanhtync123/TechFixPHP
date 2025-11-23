<?php
header('Content-Type: application/json');
require_once '../../config/db.php';

$booking_id = $_POST['booking_id'] ?? 0;
$tech_lat = $_POST['lat'] ?? 0;
$tech_lng = $_POST['lng'] ?? 0;

if ($booking_id <= 0 || !$tech_lat || !$tech_lng) {
    echo json_encode(['success' => false, 'message' => 'Thiếu dữ liệu GPS.']);
    exit;
}

// 1. Lấy tọa độ nhà khách hàng từ DB
$sql = "SELECT lat, lng FROM bookings WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking || empty($booking['lat']) || empty($booking['lng'])) {
    // Nếu đơn hàng chưa có tọa độ khách (đơn cũ), tạm thời cho qua (để test)
    echo json_encode(['success' => true, 'message' => 'Không có tọa độ khách, bỏ qua check GPS.', 'distance' => 0]);
    exit;
}

// 2. Công thức Haversine tính khoảng cách (đơn vị: mét)
function getDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371000; // Bán kính trái đất (mét)
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng/2) * sin($dLng/2);
         
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $earthRadius * $c;
    
    return $distance;
}

$distance = getDistance($tech_lat, $tech_lng, $booking['lat'], $booking['lng']);
$allowed_distance = 300; // Cho phép sai số 300 mét

if ($distance <= $allowed_distance) {
    echo json_encode([
        'success' => true, 
        'message' => 'Check-in thành công! Khoảng cách: ' . round($distance) . 'm',
        'distance' => $distance
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Bạn đang ở quá xa nhà khách hàng (' . round($distance) . 'm). Vui lòng di chuyển đến đúng địa điểm.',
        'distance' => $distance
    ]);
}
?>