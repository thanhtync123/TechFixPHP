<?php
session_start();
include '../../config/db.php';

// ✅ Chỉ kiểm tra có đăng nhập hay chưa
if (!isset($_SESSION['user'])) {
    header('Location: /TechFixPHP/pages/public_page/login.php');
    exit;
}

// Lấy thông tin người dùng từ session
$user = $_SESSION['user'];

// Khi bấm nút "Lưu thay đổi"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email']; // <--- 1. LẤY EMAIL TỪ FORM
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $address = $_POST['address'];

    $avatarPath = null;

    // ✅ Xử lý upload ảnh
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../assets/upload/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . '_' . basename($_FILES['avatar']['name']);
        $targetFile = $uploadDir . $fileName;
        $imagePath = 'assets/upload/' . $fileName; 

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
            $avatarPath = $imagePath;
        }
    }

    // ✅ Cập nhật cơ sở dữ liệu (Thêm cột email)
    $sql = "UPDATE users SET name=?, email=?, phone=?, password=?, address=? " . 
           ($avatarPath ? ", avatar=? " : "") . 
           "WHERE id=?";

    $stmt = $conn->prepare($sql);
    
    // Bind param: sssss (Name, Email, Phone, Pass, Addr)
    if ($avatarPath) {
        // Có avatar: 6 string + 1 int (ID)
        $stmt->bind_param("ssssssi", $name, $email, $phone, $password, $address, $avatarPath, $user['id']);
    } else {
        // Không đổi avatar: 5 string + 1 int (ID)
        $stmt->bind_param("sssssi", $name, $email, $phone, $password, $address, $user['id']);
    }
    $stmt->execute();

    // ✅ Cập nhật session ngay lập tức
    $_SESSION['user']['name'] = $name;
    $_SESSION['user']['email'] = $email; // <--- 2. CẬP NHẬT SESSION EMAIL
    $_SESSION['user']['phone'] = $phone;
    $_SESSION['user']['password'] = $password;
    $_SESSION['user']['address'] = $address;
    if ($avatarPath) $_SESSION['user']['avatar'] = $avatarPath;

    // Refresh lại trang để thấy thay đổi
    header("Location: settings.php?success=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hồ sơ cá nhân - TechFix</title>
    <link rel="stylesheet" href="/TechFixPHP/assets/css/settings.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        .profile-page {
            display: flex;
            justify-content: center;
            padding: 40px;
            min-height: 100vh;
            align-items: center;
        }
        .profile-container {
            display: flex;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 900px;
            max-width: 100%;
        }
        .profile-left {
            width: 35%;
            background: linear-gradient(135deg, #0078d7, #005a9e);
            color: white;
            text-align: center;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .avatar-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 15px;
        }
        .avatar-img {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255,255,255,0.3);
        }
        .avatar-circle {
            width: 140px;
            height: 140px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            color: white;
            border: 4px solid rgba(255,255,255,0.3);
        }
        .change-avatar-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: #28a745;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid white;
            transition: transform 0.2s;
        }
        .change-avatar-btn:hover {
            transform: scale(1.1);
        }
        .user-name {
            margin: 10px 0 5px;
            font-size: 24px;
            font-weight: 600;
        }
        .user-role {
            font-size: 14px;
            opacity: 0.8;
            background: rgba(0,0,0,0.1);
            padding: 5px 15px;
            border-radius: 20px;
        }
        .profile-right {
            flex: 1;
            padding: 40px;
        }
        .form-title {
            color: #333;
            margin-bottom: 25px;
            font-size: 22px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        .icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: #666;
        }
        .form-control {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        .form-control:focus {
            border-color: #0078d7;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,120,215,0.1);
        }
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }
        .btn-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
        }
        .btn-save {
            background: #0078d7;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: background 0.3s;
        }
        .btn-save:hover {
            background: #005fa3;
        }
        .btn-back {
            text-decoration: none;
            color: #666;
            font-size: 14px;
            display: flex;
            align-items: center;
            transition: color 0.3s;
        }
        .btn-back:hover {
            color: #333;
        }
        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="profile-page">
    <div class="profile-container">
            
        <div class="profile-left">
            <div class="avatar-wrapper">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="/TechFixPHP/<?= htmlspecialchars($user['avatar']) ?>" class="avatar-img" alt="Avatar">
                <?php else: ?>
                    <div class="avatar-circle"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                <?php endif; ?>

                <label class="change-avatar-btn" for="avatarInput" title="Thay đổi ảnh">
                    📷
                </label>
            </div>
            <input type="file" id="avatarInput" name="avatar" form="profileForm" accept="image/*" style="display:none">
            
            <h2 class="user-name"><?= htmlspecialchars($user['name']) ?></h2>
            <span class="user-role"><?= htmlspecialchars(ucfirst($user['role'])) ?></span>
        </div>

        <div class="profile-right">
            <h2 class="form-title">Cập nhật thông tin cá nhân</h2>
            
            <?php if (isset($_GET['success'])): ?>
                <p class="success-msg">✅ Cập nhật hồ sơ thành công!</p>
            <?php endif; ?>

            <form id="profileForm" method="POST" enctype="multipart/form-data" class="profile-form">
                
                <div class="form-group">
                    <span class="icon">👤</span>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" placeholder="Họ và tên" required>
                </div>

                <div class="form-group">
                    <span class="icon">📧</span>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="Địa chỉ Email (Quan trọng để nhận thông báo)" required>
                </div>

                <div class="form-group">
                    <span class="icon">📱</span>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" placeholder="Số điện thoại">
                </div>

                <div class="form-group">
                    <span class="icon">🔒</span>
                    <input type="password" name="password" class="form-control" value="<?= htmlspecialchars($user['password']) ?>" placeholder="Mật khẩu">
                </div>

                <div class="form-group">
                    <span class="icon">📍</span>
                    <textarea name="address" class="form-control" placeholder="Địa chỉ giao hàng mặc định"><?= htmlspecialchars($user['address']) ?></textarea>
                </div>

                <div class="btn-group">
                    <a href="/TechFixPHP/index.php" class="btn-back">⬅️ Quay lại trang chủ</a>
                    <button type="submit" class="btn-save">💾 Lưu Thay Đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Hiệu ứng xem trước ảnh khi chọn
document.getElementById('avatarInput').addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.querySelector('.avatar-img');
            const circle = document.querySelector('.avatar-circle');
            
            if (img) {
                img.src = e.target.result;
            } else if (circle) {
                // Nếu đang dùng avatar chữ cái, thay bằng thẻ img
                circle.outerHTML = `<img src="${e.target.result}" class="avatar-img" alt="Avatar">`;
            }
        }
        reader.readAsDataURL(file);
    }
});
</script>

</body>
</html>