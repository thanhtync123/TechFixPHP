<?php
// login.php
session_start();
include "../../config/db.php";
include_once '../../config/log_helper.php';
writeLog($conn, "Đăng nhập vào hệ thống");
// --- XỬ LÝ AJAX LOGIN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    $phone    = trim($data['phone'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($phone) || empty($password)) {
        echo json_encode(['error' => 'Vui lòng nhập đầy đủ thông tin']);
        http_response_code(400);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo json_encode(['error' => 'Tài khoản không tồn tại']);
        http_response_code(401);
        exit;
    }

    $storedPassword = $user['password'];
    $isMatch = false;
    if (password_verify($password, $storedPassword)) {
        $isMatch = true; 
    } elseif ($password === $storedPassword || (strlen($storedPassword) === 32 && md5($password) === $storedPassword)) {
        $isMatch = true; 
    }

    if (!$isMatch) {
        echo json_encode(['error' => 'Mật khẩu không chính xác']);
        http_response_code(401);
        exit;
    }

    $_SESSION['user']     = $user;
    $_SESSION['role']     = $user['role'];
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['name']     = $user['name'];

    echo json_encode([
        'success' => true,
        'role' => $user['role'],
        'redirect' => getRedirectUrl($user['role'])
    ]);
    exit;
}

function getRedirectUrl($role) {
    switch ($role) {
        case 'admin': return '/TechFixPHP/pages/admin/dashboard.php';
        case 'technical': return '/TechFixPHP/pages/admin/tech_schedule.php';
        default: return '/TechFixPHP/index.php';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TECHFIX</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --blue-primary: #0d47a1; /* Màu xanh đậm bên trái */
            --blue-light: #1565c0;
            --bg-gray: #f0f2f5;
            --text-dark: #333;
            --text-gray: #666;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%); /* Nền chuyển màu nhẹ */
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* --- MAIN CARD CONTAINER --- */
        .login-card-wrapper {
            display: flex;
            width: 900px;
            height: 550px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            overflow: hidden; /* Để bo góc ảnh nền */
        }

        /* --- LEFT SIDE (BLUE) --- */
        .left-side {
            flex: 1; /* Chiếm 50% */
            background-color: var(--blue-primary);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        /* Họa tiết trang trí mờ */
        .left-side::before {
            content: ''; position: absolute; top: -50px; right: -50px;
            width: 200px; height: 200px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .left-side::after {
            content: ''; position: absolute; bottom: -30px; left: -30px;
            width: 150px; height: 150px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }

        .brand-header {
            text-align: center; z-index: 1;
        }
        .brand-header img { width: 60px; margin-bottom: 10px;  }
        .brand-header h3 { font-size: 1.5rem; font-weight: 700; letter-spacing: 1px; }

        .company-info {
            text-align: center; z-index: 1; margin-top: 20px;
        }
        .company-info h2 { font-size: 1.8rem; margin-bottom: 15px; font-weight: 700; }
        .company-info p { font-size: 0.9rem; line-height: 1.6; opacity: 0.9; margin-bottom: 20px; }

        .qr-box {
            background: white; padding: 10px; border-radius: 8px;
            width: 100px; height: 100px; margin: 0 auto;
            display: flex; align-items: center; justify-content: center;
        }
        .qr-box img { width: 100%; }

        .version-tag { font-size: 0.8rem; opacity: 0.7; text-align: center; margin-top: 20px; }


        /* --- RIGHT SIDE (WHITE) --- */
        .right-side {
            flex: 1; /* Chiếm 50% */
            background: white;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .login-header { margin-bottom: 30px; }
        .login-header h2 { font-size: 2rem; color: var(--text-dark); margin-bottom: 5px; }
        .login-header p { color: var(--text-gray); font-size: 0.9rem; }

        /* Form Inputs */
        .form-group { margin-bottom: 15px; position: relative; }
        .form-group i {
            position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
            color: #aaa; font-size: 1.1rem;
        }
        .form-control {
            width: 100%; padding: 12px 15px 12px 45px;
            border: 1px solid #e0e0e0; border-radius: 8px;
            font-size: 0.95rem; outline: none; transition: 0.3s;
            background: #f9f9f9;
        }
        .form-control:focus {
            background: white; border-color: var(--blue-light);
            box-shadow: 0 0 0 3px rgba(21, 101, 192, 0.1);
        }

        /* Buttons */
        .btn-login {
            width: 100%; padding: 12px; background: var(--blue-primary);
            color: white; border: none; border-radius: 8px;
            font-size: 1rem; font-weight: 600; cursor: pointer;
            transition: 0.3s; margin-top: 10px;
        }
        .btn-login:hover { background: var(--blue-light); transform: translateY(-1px); }

        .or-divider {
            text-align: center; margin: 20px 0; font-size: 0.85rem; color: #aaa; position: relative;
        }
        .or-divider::before {
            content: ""; position: absolute; top: 50%; left: 0; right: 0; height: 1px; background: #eee; z-index: 0;
        }
        .or-divider span { background: white; padding: 0 10px; position: relative; z-index: 1; }

        .btn-face {
            width: 100%; padding: 10px; background: white;
            border: 1px solid #ddd; border-radius: 8px;
            color: #555; font-weight: 500; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: 0.3s;
        }
        .btn-face:hover { background: #f8f9fa; border-color: #ccc; }
        .btn-face i { color: var(--blue-primary); font-size: 1.2rem; }

        .register-text { text-align: center; margin-top: 20px; font-size: 0.9rem; color: #666; }
        .register-text a { color: var(--blue-primary); text-decoration: none; font-weight: 600; }
        
        /* Forgot Password Link */
        .forgot-pass { 
            text-align: right; margin-bottom: 10px; 
        }
        .forgot-pass a { 
            font-size: 0.85rem; color: var(--text-gray); text-decoration: none; 
        }
        .forgot-pass a:hover { color: var(--blue-primary); text-decoration: underline; }

        /* Face ID View */
        #face-view { display: none; text-align: center; animation: fadeIn 0.4s; }
        .video-frame {
            width: 100%; height: 220px; background: #000;
            border-radius: 10px; overflow: hidden; margin-bottom: 15px;
            position: relative;
        }
        video { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
        .scan-bar {
            position: absolute; top: 0; left: 0; width: 100%; height: 2px;
            background: #00e676; box-shadow: 0 0 8px #00e676;
            animation: scan 2s infinite linear; z-index: 2;
        }
        @keyframes scan { 0% {top:0} 100% {top:100%} }
        @keyframes fadeIn { from{opacity:0; transform:translateY(10px)} to{opacity:1; transform:translateY(0)} }

        /* Loading Overlay */
        .loading-overlay {
            position: absolute; top:0; left:0; width:100%; height:100%;
            background: rgba(255,255,255,0.85); z-index: 10;
            display: none; justify-content: center; align-items: center;
            border-radius: 20px;
        }
        .spinner {
            width: 40px; height: 40px; border: 4px solid #f3f3f3;
            border-top: 4px solid var(--blue-primary); border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        /* Toast */
        #toast-box { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .toast {
            background: #333; color: white; padding: 12px 20px; border-radius: 6px;
            margin-bottom: 10px; display: flex; align-items: center; gap: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15); animation: slideIn 0.3s forwards;
        }
        .toast.success { border-left: 4px solid #00e676; }
        .toast.error { border-left: 4px solid #ff1744; }
        @keyframes slideIn { from{transform:translateX(100%)} to{transform:translateX(0)} }

        /* Mobile Responsive */
        @media (max-width: 850px) {
            .left-side { display: none; } /* Ẩn cột trái trên mobile */
            .login-card-wrapper { width: 90%; height: auto; min-height: 500px; }
            .right-side { padding: 30px; }
        }
    </style>
</head>
<body>

<div class="login-card-wrapper">
    
    <div class="left-side">
        <div class="brand-header">
            <img src="../../../TechFixPHP/assets/image/vlute1.png" alt="Logo">
            <h3>TECHFIX</h3>
        </div>

        <div class="company-info">
            <h2>GIỚI THIỆU</h2>
            <p>TECHFIX là nền tảng dịch vụ gia đình hiện đại, kết nối khách hàng với đội ngũ kỹ thuật viên uy tín và chuyên nghiệp.</p>
            <p>Chỉ với vài thao tác đơn giản, bạn có thể dễ dàng đặt lịch sửa chữa, bảo trì hay vệ sinh nhà cửa ngay tại nhà.</p>
            <p class="slogan">"Sửa chữa tận tâm - Nâng tầm cuộc sống"</p>
            
            <div class="qr-box">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=TechFixLogin" alt="QR Code">
            </div>
        </div>

        <div class="version-tag">
            <i class="fa-solid fa-shield-halved"></i> TechFix System v2.0
        </div>
    </div>

    <div class="right-side">
        <div class="loading-overlay" id="loading"><div class="spinner"></div></div>

        <div class="login-header">
            <h2>Đăng nhập</h2>
            <p>Vui lòng nhập đầy đủ bản tin</p>
        </div>

        <div id="form-view">
            <form id="loginForm">
                <div class="form-group">
                    <i class="fa-solid fa-phone"></i>
                    <input type="text" id="phone" class="form-control" placeholder="Số điện thoại" required>
                </div>
                <div class="form-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" class="form-control" placeholder="Mật khẩu" required>
                </div>
                
                <div class="forgot-pass">
                    <a href="forgot_password.php">Quên mật khẩu?</a>
                </div>
                
                <button type="submit" class="btn-login">Đăng nhập</button>
            </form>

            <div class="or-divider"><span>hoặc</span></div>

            <button id="btnToFace" class="btn-face">
                <i class="fa-solid fa-face-viewfinder"></i> Đăng Nhập Bằng Khuôn Mặt
            </button>

            <div class="register-text">
                Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
            </div>
        </div>

        <div id="face-view">
            <div class="video-frame">
                <video id="video" autoplay muted playsinline></video>
                <div class="scan-bar"></div>
            </div>
            <p style="font-size: 0.9rem; color: #666; margin-bottom: 15px;">Giữ khuôn mặt trong khung hình</p>
            <button id="btnVerify" class="btn-login">Xác thực ngay</button>
            <button id="btnBack" class="btn-face" style="margin-top: 10px;">Quay lại</button>
        </div>

    </div>
</div>

<div id="toast-box"></div>

<script src="https://unpkg.com/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script src="/TechFixPHP/assets/js/faceAuth.js"></script>

<script>
    const loading = document.getElementById('loading');
    const formView = document.getElementById('form-view');
    const faceView = document.getElementById('face-view');
    const video = document.getElementById('video');

    function toast(msg, type='info') {
        const div = document.createElement('div');
        div.className = `toast ${type}`;
        div.innerHTML = `<i class="fa-solid fa-info-circle"></i> <span>${msg}</span>`;
        document.getElementById('toast-box').appendChild(div);
        setTimeout(() => div.remove(), 3000);
    }

    // 1. Đăng nhập thường
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        loading.style.display = 'flex';
        const phone = document.getElementById('phone').value;
        const pass = document.getElementById('password').value;

        try {
            const res = await fetch('login.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json', 'X-Requested-With':'XMLHttpRequest'},
                body: JSON.stringify({phone, password: pass})
            });
            const data = await res.json();
            loading.style.display = 'none';

            if(data.success) {
                toast('Đăng nhập thành công!', 'success');
                setTimeout(() => window.location.href = data.redirect, 1000);
            } else {
                toast(data.error, 'error');
            }
        } catch (err) {
            loading.style.display = 'none';
            toast('Lỗi kết nối', 'error');
        }
    });

    // 2. Chuyển sang Face ID
    document.getElementById('btnToFace').addEventListener('click', async () => {
        loading.style.display = 'flex';
        try {
            await loadModels(); 
            const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
            video.srcObject = stream;
            formView.style.display = 'none';
            faceView.style.display = 'block';
            loading.style.display = 'none';
        } catch (err) {
            loading.style.display = 'none';
            toast('Không mở được Camera', 'error');
        }
    });

    // 3. Xác thực Face ID
    document.getElementById('btnVerify').addEventListener('click', async () => {
        loading.style.display = 'flex';
        try {
            const descriptor = await getFaceDescriptor('video');
            if(!descriptor) {
                loading.style.display = 'none';
                toast('Không tìm thấy khuôn mặt', 'error');
                return;
            }

            const res = await fetch('../api/verify-face.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ descriptor: Array.from(descriptor) })
            });
            const user = await res.json();
            loading.style.display = 'none';

            if(user && user.role) {
                toast('Xác thực thành công!', 'success');
                let url = '/TechFixPHP/index.php';
                if(user.role === 'admin') url = '/TechFixPHP/pages/admin/dashboard.php';
                if(user.role === 'technical') url = '/TechFixPHP/pages/admin/tech_schedule.php';
                setTimeout(() => window.location.href = url, 1000);
            } else {
                toast('Khuôn mặt không khớp', 'error');
            }
        } catch (err) {
            loading.style.display = 'none';
            toast('Lỗi xử lý', 'error');
        }
    });

    // 4. Quay lại
    document.getElementById('btnBack').addEventListener('click', () => {
        if(video.srcObject) video.srcObject.getTracks().forEach(t => t.stop());
        faceView.style.display = 'none';
        formView.style.display = 'block';
    });
</script>

</body>
</html>