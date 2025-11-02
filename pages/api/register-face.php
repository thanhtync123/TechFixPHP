<?php
// /pages/api/register-face.php
include "../../config/db.php"; 

$data = json_decode(file_get_contents('php://input'), true);

$name = $conn->real_escape_string($data['name'] ?? '');
$phone = $conn->real_escape_string($data['phone'] ?? '');
$email = $conn->real_escape_string($data['email'] ?? ''); // <-- THÊM DÒNG NÀY
$password = $data['password'] ?? ''; 
$address = $conn->real_escape_string($data['address'] ?? '');
$descriptor = $data['descriptor'] ?? null;
$role = 'customer';

// 2. Validation (Thêm email)
if (empty($name) || empty($phone) || empty($email) || empty($password) || strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đủ tên, SĐT, email và mật khẩu (6+ ký tự).']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Địa chỉ email không hợp lệ.']);
    exit;
}
if (empty($descriptor)) {
    echo json_encode(['success' => false, 'message' => 'Không nhận được dữ liệu khuôn mặt.']);
    exit;
}

// 3. Kiểm tra SĐT hoặc Email đã tồn tại
$stmt = $conn->prepare("SELECT id FROM users WHERE phone = ? OR email = ? LIMIT 1");
$stmt->bind_param('ss', $phone, $email); // <-- Sửa
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Số điện thoại hoặc Email này đã được đăng ký.']);
    $stmt->close();
    exit;
}
$stmt->close();

// 4. Băm mật khẩu (Tốt nhất là dùng, nhưng tôi tôn trọng lựa chọn của bạn)
// $hashed_password = password_hash($password, PASSWORD_DEFAULT);
$hashed_password = $password; // (Theo yêu cầu "không băm" của bạn)


// 5. Thêm người dùng mới (Thêm email)
$stmt_insert = $conn->prepare("INSERT INTO users (name, phone, email, password, address, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
$stmt_insert->bind_param('ssssss', $name, $phone, $email, $hashed_password, $address, $role); // <-- Sửa 'sssss' thành 'ssssss'

if ($stmt_insert->execute()) {
    $user_id = $conn->insert_id; 
    
    // 6. Thêm đặc trưng khuôn mặt (face_descriptor)
    $descriptor_json = json_encode($descriptor);
    $stmt_update = $conn->prepare("UPDATE users SET face_descriptor = ? WHERE id = ?");
    $stmt_update->bind_param("si", $descriptor_json, $user_id);
    
    if ($stmt_update->execute()) {
        echo json_encode(['success' => true, 'message' => 'Đăng ký thành công!']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Đăng ký tài khoản thành công, nhưng lỗi lưu khuôn mặt.']);
    }
    $stmt_update->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi CSDL khi tạo tài khoản.']);
}

$stmt_insert->close();
$conn->close();
?>