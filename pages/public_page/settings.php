<?php
session_start();
include '../../config/db.php';

// ✅ Chỉ kiểm tra có đăng nhập hay chưa, KHÔNG phân biệt role
if (!isset($_SESSION['user'])) {
    header('Location: /TechFixPHP/pages/public_page/login.php');
    exit;
}

// Lấy thông tin người dùng từ session
$user = $_SESSION['user'];





// Khi bấm nút "Lưu thay đổi"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $address = $_POST['address'];

    $avatarPath = null;

    // ✅ Nếu có tải ảnh mới (UPLOAD THẬT, KHÔNG BASE64)
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../assets/upload/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . '_' . basename($_FILES['avatar']['name']);
        $targetFile = $uploadDir . $fileName;
        $imagePath = 'assets/upload/' . $fileName; // đường dẫn lưu DB

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
            $avatarPath = $imagePath;
        }
    }

    // ✅ Cập nhật cơ sở dữ liệu
    $sql = "UPDATE users SET name=?, phone=?, password=?, address=? " . 
           ($avatarPath ? ", avatar=? " : "") . 
           "WHERE id=?";

    $stmt = $conn->prepare($sql);
    if ($avatarPath) {
        $stmt->bind_param("sssssi", $name, $phone, $password, $address, $avatarPath, $user['id']);
    } else {
        $stmt->bind_param("ssssi", $name, $phone, $password, $address, $user['id']);
    }
    $stmt->execute();

    // ✅ Cập nhật session
    $_SESSION['user']['name'] = $name;
    $_SESSION['user']['phone'] = $phone;
    $_SESSION['user']['password'] = $password;
    $_SESSION['user']['address'] = $address;
    if ($avatarPath) $_SESSION['user']['avatar'] = $avatarPath;

    header("Location: settings.php?success=1");
    exit;
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hồ sơ cá nhân</title>
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
        }
        .profile-container {
            display: flex;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            width: 800px;
        }
        .profile-left {
            width: 35%;
            background: #0078d7;
            color: white;
            text-align: center;
            padding: 30px 20px;
        }
        .avatar-wrapper {
            position: relative;
            display: inline-block;
        }
        .avatar-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
        }
        .avatar-circle {
            width: 120px;
            height: 120px;
            background: #444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
        }
        .change-avatar-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #28a745;
            border-radius: 50%;
            padding: 5px;
            cursor: pointer;
        }
        .profile-right {
            flex: 1;
            padding: 30px;
        }
        .form-group {
            margin-bottom: 15px;
            position: relative;
        }
        .icon {
            position: absolute;
            left: 10px;
            top: 10px;
        }
        .form-control {
            width: 100%;
            padding: 10px 10px 10px 35px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .btn-save {
            background: #0078d7;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-save:hover {
            background: #005fa3;
        }
        .success-msg {
            color: green;
            text-align: center;
            margin-bottom: 15px;
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
        </div>

        <div class="profile-right">
            <h2 class="form-title">Cập nhật thông tin</h2>
            <?php if (isset($_GET['success'])): ?>
                <p class="success-msg">✅ Cập nhật thành công!</p>
            <?php endif; ?>

            <form id="profileForm" method="POST" enctype="multipart/form-data" class="profile-form">
                <div class="form-group icon-input">
                    <span class="icon">👤</span>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" placeholder="Tên người dùng" required>
                </div>

                <div class="form-group icon-input">
                    <span class="icon">📱</span>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" placeholder="Số điện thoại">
                </div>

                <div class="form-group icon-input">
                    <span class="icon">🔒</span>
                    <input type="password" name="password" class="form-control" value="<?= htmlspecialchars($user['password']) ?>" placeholder="Mật khẩu">
                </div>

                <div class="form-group icon-input">
                    <span class="icon">📍</span>
                    <textarea name="address" class="form-control" placeholder="Địa chỉ"><?= htmlspecialchars($user['address']) ?></textarea>
                </div>

                <div class="form-group icon-input">
                    <span class="icon">⭐</span>
                    <input type="text" name="role" class="form-control" value="<?= htmlspecialchars($user['role']) ?>" readonly>
                </div>

                <button type="submit" class="btn-save">💾 Lưu Thay Đổi</button>
                 <!-- ✅ Nút quay lại Dashboard -->
            <a href="/TechFixPHP/index.php" class="btn-back">⬅️ Quay Lại Trang Chủ</a>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelector('.change-avatar-btn').addEventListener('click', () => {
    document.getElementById('avatarInput').click();
});
</script>

</body>
</html>
