<?php
// /pages/api/register-face.php
session_start();
include "../../config/db.php"; // Đường dẫn đúng tới config/db.php

// 1. Lấy dữ liệu JSON từ JavaScript
$data = json_decode(file_get_contents('php://input'), true);

$name = $conn->real_escape_string($data['name'] ?? '');
$phone = $conn->real_escape_string($data['phone'] ?? '');
$password = $data['password'] ?? ''; // Lấy mật khẩu
$address = $conn->real_escape_string($data['address'] ?? '');
$descriptor = $data['descriptor'] ?? null;
$role = 'customer';

// 2. Validation (Giống code cũ của bạn)
if (empty($name) || empty($phone) || empty($password) || strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đủ thông tin. Mật khẩu phải ít nhất 6 ký tự.']);
    exit;
}
if (empty($descriptor)) {
    echo json_encode(['success' => false, 'message' => 'Không nhận được dữ liệu khuôn mặt. Vui lòng bật camera và thử lại.']);
    exit;
}

// 3. Kiểm tra SĐT đã tồn tại (Giống code cũ của bạn)
$stmt = $conn->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
$stmt->bind_param('s', $phone);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Số điện thoại này đã được đăng ký.']);
    $stmt->close();
    exit;
}
$stmt->close();

// 4. Băm mật khẩu (Giống code cũ của bạn)
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 5. Thêm người dùng mới (CHƯA CÓ KHUÔN MẶT)
$stmt_insert = $conn->prepare("INSERT INTO users (name, phone, password, address, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt_insert->bind_param('sssss', $name, $phone, $hashed_password, $address, $role);

if ($stmt_insert->execute()) {
    // 6. Thêm đặc trưng khuôn mặt (face_descriptor)
    $user_id = $conn->insert_id; // Lấy ID của user vừa tạo
    
    // Chuyển mảng 128 con số thành chuỗi JSON để lưu
    $descriptor_json = json_encode($descriptor);

    // Cập nhật lại user với dữ liệu khuôn mặt
    $stmt_update = $conn->prepare("UPDATE users SET face_descriptor = ? WHERE id = ?");
    $stmt_update->bind_param("si", $descriptor_json, $user_id);
    
    if ($stmt_update->execute()) {
        echo json_encode(['success' => true, 'message' => 'Đăng ký thành công!']);
    } else {
        // Vẫn thành công, nhưng báo lỗi
        echo json_encode(['success' => true, 'message' => 'Đăng ký tài khoản thành công, nhưng lỗi lưu khuôn mặt.']);
    }
    $stmt_update->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi CSDL khi tạo tài khoản.']);
}

$stmt_insert->close();
$conn->close();
?>