<?php
session_start();
require_once '../../config/db.php';

// 1. ĐỒNG BỘ MÚI GIỜ (Để chắc chắn)
date_default_timezone_set('Asia/Ho_Chi_Minh');

$msg = "";
$msg_type = "";
$token = $_GET["token"] ?? "";
$show_form = false;
$user_id = 0;

// 2. KIỂM TRA TOKEN VÀ THỜI GIAN
if (!empty($token)) {
    $token_hash = hash("sha256", $token);

    // Lấy thông tin token từ DB (Chưa kiểm tra thời gian vội)
    $sql = "SELECT id, reset_token_expires_at FROM users WHERE reset_token_hash = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data) {
        // Chuyển đổi thời gian hết hạn từ DB sang timestamp (số giây)
        $expiry_timestamp = strtotime($data['reset_token_expires_at']);
        $current_timestamp = time(); // Thời gian hiện tại (số giây)

        // SO SÁNH: Thời gian hết hạn > Thời gian hiện tại => Còn hạn
        if ($expiry_timestamp > $current_timestamp) {
            $show_form = true;
            $user_id = $data['id'];
        } else {
            // Hết hạn
            $msg = "Đường dẫn này đã hết hạn vào lúc: " . date('H:i d/m/Y', $expiry_timestamp);
            $msg_type = "danger";
        }
    } else {
        // Không tìm thấy token (Link sai hoặc đã dùng rồi)
        $msg = "Đường dẫn không hợp lệ hoặc bạn đã đổi mật khẩu rồi!";
        $msg_type = "danger";
    }
} else {
    $msg = "Thiếu mã xác thực (Token) trên URL.";
    $msg_type = "danger";
}

// 3. XỬ LÝ ĐỔI MẬT KHẨU (KHI POST FORM)
if ($_SERVER["REQUEST_METHOD"] == "POST" && $show_form) {
    $pass = $_POST["password"];
    $confirm = $_POST["confirm_password"];

    if (strlen($pass) < 6) {
        $msg = "Mật khẩu quá ngắn (Tối thiểu 6 ký tự).";
        $msg_type = "warning";
    } elseif ($pass !== $confirm) {
        $msg = "Mật khẩu xác nhận không khớp.";
        $msg_type = "warning";
    } else {
        $new_hash = password_hash($pass, PASSWORD_DEFAULT);
        
        // Cập nhật mật khẩu & Xóa token ngay lập tức (Bảo mật)
        $upd = $conn->prepare("UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?");
        $upd->bind_param("si", $new_hash, $user_id);
        
        if ($upd->execute()) {
            $msg = "Đổi mật khẩu thành công!";
            $msg_type = "success";
            $show_form = false; // Ẩn form đi
            echo "<script>setTimeout(function(){ window.location.href='login.php'; }, 3000);</script>";
        } else {
            $msg = "Lỗi hệ thống: " . $conn->error;
            $msg_type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - TechFix</title>
    
    <link href="/TechFixPHP/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #f3f4f6;
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        
        .reset-container {
            background: white;
            padding: 48px 40px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 440px;
            text-align: center;
            position: relative;
        }

        /* Icon Header */
        .icon-header {
            width: 80px; height: 80px;
            background: #eff6ff; color: #3b82f6;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 32px; margin: 0 auto 24px;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }

        .form-title { font-weight: 800; color: #1f2937; margin-bottom: 8px; font-size: 1.5rem; letter-spacing: -0.5px; }
        .form-desc { color: #6b7280; font-size: 0.95rem; margin-bottom: 32px; line-height: 1.5; }

        /* Input Styles */
        .input-group { margin-bottom: 20px; }
        .input-group-text { background: #f9fafb; border-right: none; color: #9ca3af; border-radius: 12px 0 0 12px; border-color: #e5e7eb; }
        .form-control { 
            border-left: none; padding: 12px 16px 12px 0; background: #f9fafb; 
            border-radius: 0 12px 12px 0; border-color: #e5e7eb; font-size: 1rem;
            color: #374151;
        }
        
        /* Focus Effect */
        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control {
            border-color: #3b82f6; background: white;
        }
        .input-group:focus-within .input-group-text i { color: #3b82f6; }
        .form-control:focus { box-shadow: none; }

        /* Button */
        .btn-submit {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: none; border-radius: 12px;
            color: white; font-weight: 700; font-size: 1rem;
            transition: all 0.3s ease; margin-top: 10px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3); }
        
        /* Alert */
        .alert { border-radius: 12px; border: none; font-size: 0.9rem; display: flex; align-items: center; padding: 15px; }
        .alert i { margin-right: 10px; font-size: 1.2rem; }
        
        /* Error State */
        .state-icon { font-size: 64px; margin-bottom: 20px; display: block; opacity: 0.9; }
        .text-success { color: #10b981 !important; }
        .text-danger { color: #ef4444 !important; }
        
        .btn-outline {
            border: 2px solid #e5e7eb; color: #4b5563; background: white;
            font-weight: 600; border-radius: 12px; padding: 12px 24px;
            text-decoration: none; display: inline-block; margin-top: 20px;
            transition: 0.3s;
        }
        .btn-outline:hover { border-color: #3b82f6; color: #3b82f6; background: #eff6ff; }
    </style>
</head>
<body>

    <div class="reset-container">
        <?php if ($show_form): ?>
            <div class="icon-header">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h1 class="form-title">Đặt Lại Mật Khẩu</h1>
            <p class="form-desc">Vui lòng nhập mật khẩu mới an toàn để bảo vệ tài khoản của bạn.</p>

            <?php if ($msg): ?>
                <div class="alert alert-<?= $msg_type ?> text-start mb-4">
                    <i class="fa-solid fa-circle-info"></i> <div><?= $msg ?></div>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Mật khẩu mới (Min 6 ký tự)" required>
                </div>
                
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Xác nhận mật khẩu" required>
                </div>
                
                <button class="btn-submit">Xác Nhận Thay Đổi</button>
            </form>

        <?php else: ?>
            <div class="mt-2">
                <?php if ($msg_type == 'success'): ?>
                    <div class="icon-header" style="background: #ecfdf5; color: #10b981; box-shadow: none;">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <h3 class="form-title text-success">Thành Công!</h3>
                    <p class="form-desc mb-4"><?= $msg ?></p>
                    <div class="spinner-border text-primary mt-3" role="status" style="width: 1.5rem; height: 1.5rem;"></div>
                    <p class="small text-muted mt-2">Đang chuyển hướng về trang đăng nhập...</p>
                <?php else: ?>
                    <div class="icon-header" style="background: #fef2f2; color: #ef4444; box-shadow: none;">
                        <i class="fa-solid fa-link-slash"></i>
                    </div>
                    <h3 class="form-title text-danger">Liên kết Lỗi</h3>
                    <p class="form-desc"><?= $msg ?></p>
                    <a href="forgot_password.php" class="btn-outline">Thử lại</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>