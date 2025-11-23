<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - TECHFIX</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --glass-bg: rgba(255, 255, 255, 0.88); /* Hơi ít trong suốt hơn */
            --glass-border: rgba(255, 255, 255, 0.6); /* Viền rõ hơn chút */
            --text-dark: #2b2d42;

            /* Màu nền mới - Tinh tế hơn */
            --bg-start: #e0f2f7; /* Xanh nhạt */
            --bg-end: #c9e6f1; /* Xanh trời nhạt hơn */
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Outfit', sans-serif; height: 100vh;
            display: flex; justify-content: center; align-items: center;
            overflow: hidden; 
            /* NỀN MỚI: Chuyển màu tĩnh, nhẹ nhàng */
            background: linear-gradient(135deg, var(--bg-start) 0%, var(--bg-end) 100%);
            position: relative;
        }

        /* Bong bóng trang trí nền - Màu sắc và opacity dịu hơn */
        .bubble {
            position: absolute;
            border-radius: 50%;
            filter: blur(50px); /* Tăng blur để mờ hơn */
            z-index: 0;
            animation: float 10s infinite ease-in-out;
            opacity: 0.2; /* Giảm độ trong suốt */
        }
        .b1 { width: 400px; height: 400px; background: var(--primary); top: -10%; left: -10%; }
        .b2 { width: 300px; height: 300px; background: var(--secondary); bottom: -5%; right: -5%; animation-delay: 2s; }
        /* .b3 đã bị xóa để giảm bớt sự "màu mè" */

        /* MAIN CARD */
        .register-wrapper {
            position: relative; z-index: 1; width: 900px; max-width: 95%;
            background: var(--glass-bg); backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border); border-radius: 24px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1); /* Đổ bóng nhẹ nhàng hơn */
            padding: 40px;
            display: flex; flex-direction: column; max-height: 90vh; overflow-y: auto;
        }
        .header-section { text-align: center; margin-bottom: 20px; }
        .header-section img { height: 100px; margin-bottom: 10px; }
        .header-section h2 { font-size: 2rem; font-weight: 700; color: var(--primary); margin-bottom: 5px; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .input-box { position: relative; }
        .input-box label { font-size: 0.85rem; font-weight: 600; color: #555; margin-bottom: 5px; display: block; }
        .input-field {
            width: 100%; padding: 12px 15px 12px 40px; border: 2px solid #e0e0e0;
            border-radius: 12px; font-size: 0.95rem; background: #f8f9fa; transition: 0.3s;
        }
        .input-field:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1); outline: none; }
        .input-box i { position: absolute; left: 14px; top: 38px; color: #aaa; font-size: 1rem; }
        .input-field:focus + i { color: var(--primary); }
        .full-width { grid-column: span 2; }

        /* FACE ID CARD - Kích thước to đã giữ nguyên */
        .face-id-card {
            grid-column: span 2;
            background: rgba(255, 255, 255, 0.7); /* Nền nhẹ nhàng hơn */
            border: 2px dashed #cbd5e1; border-radius: 16px; padding: 20px;
            display: flex; flex-direction: column; align-items: center; text-align: center;
            margin-top: 10px; transition: 0.3s;
        }
        .face-id-card:hover { border-color: var(--primary); background: rgba(255, 255, 255, 0.95); }
        .face-info h4 { font-size: 1.1rem; margin-bottom: 5px; color: var(--text-dark); display: flex; align-items: center; gap: 10px; justify-content: center;}
        .face-info p { font-size: 0.9rem; color: #666; }

        .face-action {
            position: relative; width: 100%; max-width: 350px; aspect-ratio: 4/3;
            margin-top: 15px; background: #e0e7ff; border-radius: 12px; overflow: hidden;
            box-shadow: inset 0 0 15px rgba(0,0,0,0.05);
        }
        .video-preview { width: 100%; height: 100%; background: #000; display: none; }
        .video-preview.active { display: block; }
        video { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
        
        .btn-camera-trigger {
            width: 100%; height: 100%; background: transparent; border: none; cursor: pointer;
            color: var(--primary); font-size: 1.1rem; font-weight: 600; transition: 0.3s;
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-camera-trigger i { font-size: 2.5rem; opacity: 0.8; }
        .btn-camera-trigger:hover { background: rgba(67, 97, 238, 0.1); transform: scale(1.02); }

        .btn-submit {
            grid-column: span 2; margin-top: 10px; padding: 15px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white; border: none; border-radius: 12px; font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: 0.3s; box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
        }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(67, 97, 238, 0.4); }
        .login-redirect { grid-column: span 2; text-align: center; margin-top: 20px; font-size: 0.9rem; }
        .login-redirect a { color: var(--primary); text-decoration: none; font-weight: 600; }

        /* Loading & Toast */
        .loading-overlay { position: absolute; inset: 0; background: rgba(255,255,255,0.9); z-index: 50; display: none; justify-content: center; align-items: center; flex-direction: column; border-radius: 24px; }
        .loader { width: 48px; height: 48px; border: 5px solid #FFF; border-bottom-color: var(--primary); border-radius: 50%; animation: rotation 1s linear infinite; }
        @keyframes rotation { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        /* Đã xóa animation gradientBG */
        @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-20px); } 100% { transform: translateY(0px); } }
        
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .toast { background: #2b2d42; color: white; padding: 12px 20px; border-radius: 8px; margin-bottom: 10px; display: flex; align-items: center; gap: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); animation: slideIn 0.3s forwards; }
        .toast.success { border-left: 4px solid #00e676; } .toast.error { border-left: 4px solid #ff1744; } .toast.warning { border-left: 4px solid #ffab00; }
        @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
        
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } .full-width, .face-id-card, .btn-submit, .login-redirect { grid-column: span 1; } .register-wrapper { padding: 25px; border-radius: 0; height: 100vh; } 
            body { background: var(--glass-bg); /* Nền mobile đơn giản hơn */ }
        }
    </style>
</head>
<body>
    <div class="bubble b1"></div><div class="bubble b2"></div>

    <div class="register-wrapper">
        <div class="loading-overlay" id="loading"><span class="loader"></span><p style="margin-top: 15px; color: #555;" id="loadingText">Đang xử lý...</p></div>
        
        <div class="header-section">
            <img src="../../../TechFixPHP/assets/image/vlute1.png" alt="Logo">
            
            <h2>Tạo tài khoản</h2>
        </div>

        <form id="registerForm" class="form-grid" novalidate>
            <div class="input-box"><label>Họ và tên</label><input type="text" id="name" class="input-field" required><i class="fa-solid fa-user"></i></div>
            <div class="input-box"><label>Số điện thoại</label><input type="text" id="phone" class="input-field" required><i class="fa-solid fa-phone"></i></div>
            <div class="input-box full-width"><label>Email</label><input type="email" id="email" class="input-field" required><i class="fa-solid fa-envelope"></i></div>
            <div class="input-box"><label>Mật khẩu</label><input type="password" id="password" class="input-field" required><i class="fa-solid fa-lock"></i></div>
            <div class="input-box"><label>Xác nhận</label><input type="password" id="confirm_password" class="input-field" required><i class="fa-solid fa-shield"></i></div>
            <div class="input-box full-width"><label>Địa chỉ</label><input type="text" id="address" class="input-field"><i class="fa-solid fa-location-dot"></i></div>

            <div class="face-id-card">
                <div class="face-info">
                    <h4><i class="fa-solid fa-face-viewfinder" style="color: var(--primary);"></i> Đăng ký Face ID</h4>
                    <p>Để đăng nhập nhanh hơn, hãy bật camera và nhìn thẳng.</p>
                </div>
                <div class="face-action">
                    <div id="video-preview" class="video-preview">
                        <video id="videoFeed" autoplay muted playsinline></video>
                        <div style="position: absolute; border: 2px solid rgba(255,255,255,0.5); width: 80%; height: 80%; top: 10%; left: 10%; border-radius: 12px; pointer-events: none;"></div>
                    </div>
                    <button type="button" id="btnStartCamera" class="btn-camera-trigger">
                        <i class="fa-solid fa-camera"></i>
                        <span>BẤM ĐỂ BẬT CAMERA</span>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">ĐĂNG KÝ NGAY</button>
            <div class="login-redirect">Đã có tài khoản? <a href="login.php">Đăng nhập</a></div>
        </form>
    </div>

    <div id="toast-container" class="toast-container"></div>
    <script src="https://unpkg.com/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script src="/TechFixPHP/assets/js/faceAuth.js"></script>
    <script>
        const loading = document.getElementById('loading');
        const loadingText = document.getElementById('loadingText');
        const btnStartCamera = document.getElementById('btnStartCamera');
        const videoPreview = document.getElementById('video-preview');
        let isCameraOn = false;

        function showToast(msg, type='info') {
            const div = document.createElement('div');
            div.className = `toast ${type}`;
            div.innerHTML = `<i class="fa-solid fa-info-circle"></i> <span>${msg}</span>`;
            document.getElementById('toast-container').appendChild(div);
            setTimeout(() => div.remove(), 3000);
        }

        function toggleLoading(show, text="Đang xử lý...") {
            loading.style.display = show ? 'flex' : 'none';
            loadingText.innerText = text;
        }

        btnStartCamera.addEventListener('click', async () => {
            if(isCameraOn) return;
            toggleLoading(true, "Đang tải AI...");
            try {
                await loadModels();
                toggleLoading(true, "Khởi động Camera...");
                const started = await startVideo("videoFeed");
                if(started) {
                    btnStartCamera.style.display = 'none';
                    videoPreview.classList.add('active');
                    isCameraOn = true;
                    showToast("Hãy nhìn thẳng vào camera", "info");
                } else { showToast("Không thể bật camera", "error"); }
            } catch (err) { showToast("Lỗi: " + err.message, "error"); } 
            finally { toggleLoading(false); }
        });

        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const name = document.getElementById('name').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const email = document.getElementById('email').value.trim();
            const pass = document.getElementById('password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            const addr = document.getElementById('address').value.trim();

            if(!name || !phone || !email || !pass) { showToast("Vui lòng điền đầy đủ thông tin", "warning"); return; }
            if(pass !== confirmPass) { showToast("Mật khẩu không khớp", "warning"); return; }

            toggleLoading(true, "Đang đăng ký...");
            let faceDescriptor = null;
            if(isCameraOn) {
                toggleLoading(true, "Đang quét khuôn mặt...");
                try {
                    const descriptor = await getFaceDescriptor("videoFeed");
                    if(descriptor) faceDescriptor = Array.from(descriptor);
                    else showToast("Không tìm thấy khuôn mặt, bỏ qua FaceID", "warning");
                } catch (err) { console.error(err); }
            }

            toggleLoading(true, "Đang lưu dữ liệu...");
            try {
                const res = await fetch("../api/register-face.php", {
                    method: "POST", headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, phone, email, password: pass, address: addr, descriptor: faceDescriptor })
                });
                const data = await res.json();
                if (data.success) {
                    showToast("Đăng ký thành công!", "success");
                    if(isCameraOn) stopVideo();
                    setTimeout(() => { window.location.href = "login.php"; }, 2000);
                } else {
                    showToast(data.message, "error");
                    toggleLoading(false);
                }
            } catch (err) {
                showToast("Lỗi máy chủ", "error");
                toggleLoading(false);
            }
        });
        window.addEventListener("beforeunload", () => stopVideo());
    </script>
</body>
</html>