<?php
// /pages/api/verify-face.php
session_start();
// Đảm bảo đường dẫn này đúng
include "../../config/db.php"; 

// === HÀM TÍNH TOÁN KHOẢNG CÁCH (Bộ não) ===
// Hàm này tính "độ giống nhau" giữa 2 khuôn mặt (mảng 128 số)
function calculateEuclideanDistance($desc1, $desc2) {
    $sum = 0;
    if (count($desc1) !== count($desc2)) {
        return 999; // Lỗi, 2 mảng không cùng kích thước
    }
    for ($i = 0; $i < count($desc1); $i++) {
        $diff = ($desc1[$i] ?? 0) - ($desc2[$i] ?? 0);
        $sum += $diff * $diff;
    }
    return sqrt($sum);
}

// =============================================

// 1. Lấy đặc trưng (descriptor) từ JavaScript
$data = json_decode(file_get_contents('php://input'), true);
$newDescriptor = $data['descriptor'] ?? null;

if (!$newDescriptor || count($newDescriptor) !== 128) {
    http_response_code(400);
    echo json_encode(['error' => 'Không có dữ liệu khuôn mặt.']);
    exit;
}

// 2. Lấy TẤT CẢ khuôn mặt đã lưu từ CSDL
$query = "SELECT id, name, phone, role, password, address, face_descriptor FROM users WHERE face_descriptor IS NOT NULL";
$result = $conn->query($query);

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Không có khuôn mặt nào trong CSDL.']);
    exit;
}

$all_users_with_faces = $result->fetch_all(MYSQLI_ASSOC);

// 3. Tìm khuôn mặt khớp nhất
$matchThreshold = 0.55; // Ngưỡng "giống nhau". (Giá trị 0.4 - 0.55 là tốt nhất)
$bestMatchUser = null;
$bestDistance = 1; // Giả sử khoảng cách tệ nhất là 1

foreach ($all_users_with_faces as $user) {
    // Chuyển chuỗi JSON trong CSDL thành mảng PHP
    $storedDescriptor = json_decode($user['face_descriptor'], true);

    if (empty($storedDescriptor) || count($storedDescriptor) !== 128) {
        continue; // Bỏ qua nếu dữ liệu rác
    }

    // Tính toán độ giống nhau
    $distance = calculateEuclideanDistance($newDescriptor, $storedDescriptor);

    if ($distance < $bestDistance && $distance < $matchThreshold) {
        $bestDistance = $distance;
        $bestMatchUser = $user;
    }
}

// 4. Trả kết quả
if ($bestMatchUser) {
    // === ĐĂNG NHẬP THÀNH CÔNG ===
    // Tạo Session (Giống hệt file login.php truyền thống của bạn)
    $_SESSION['user'] = $bestMatchUser;
    $_SESSION['phone'] = $bestMatchUser['phone'];
    $_SESSION['name'] = $bestMatchUser['name'];
    $_SESSION['role'] = $bestMatchUser['role'];
    $_SESSION['user_id'] = $bestMatchUser['id']; 

    // Trả về dữ liệu user cho JavaScript (đúng như JS của bạn mong đợi)
    echo json_encode([
        'id' => $bestMatchUser['id'],
        'name' => $bestMatchUser['name'],
        'phone' => $bestMatchUser['phone'],
        'role' => $bestMatchUser['role']
    ]);
    
} else {
    // === ĐĂNG NHẬP THẤT BẠI ===
    http_response_code(401);
    echo json_encode(['error' => 'Không nhận dạng được khuôn mặt.']);
}

$conn->close();
?>