<?php
// register.php
// Code PHP xử lý POST đã bị xóa, vì chúng ta sẽ dùng JavaScript fetch
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - TECHFIX</title>
    <link href="/TechFixPHP/assets/css/register.css" rel="stylesheet" />
    <style>
        /* CSS cho toast */
        .toast { position: fixed; bottom: 20px; right: 20px; background: #333; color: #fff; padding: 1rem 1.5rem; border-radius: 8px; opacity: 0; transition: opacity 0.3s; z-index: 9999; }
        .toast.show { opacity: 1; }
        
        /* CSS cho Camera (Thêm mới) */
        .video-container {
            position: relative; width: 100%; padding-top: 75%; /* Tỷ lệ 4:3 */
            background: #000;
            border-radius: 8px; overflow: hidden; margin-bottom: 1rem;
        }
        video {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
            object-fit: cover; transform: scaleX(-1); /* Lật ngược camera selfie */
        }
        .btn-camera {
            background: #6c757d; color: white; margin-top: 0.5rem; width: 100%;
            padding: 0.75rem; border: none; border-radius: 8px; cursor: pointer;
        }
    </style>
</head>
<body>

    <div class="register-page">
        <div class="particles">
            </div>
        
        <div class="register-card">
            <div class="register-header">
                <h2>Đăng Ký</h2>
                <p>Trải nghiệm dịch vụ tiện ích cùng TECHFIX</p>
            </div>

            <form id="registerForm" class="register-form" novalidate>
                
                <h5>Bước 1: Thông tin tài khoản</h5>
                <div class="form-group icon-input">
                    <input type="text" id="name" name="Name" placeholder="Tên người dùng" class="form-control" required>
                </div>

                <div class="form-group icon-input">
                    <input type="text" id="phone" name="Phone" placeholder="Số điện thoại" class="form-control" required>
                </div>

                <div class="form-group icon-input">
                    <input type="password" id="password" name="Password" placeholder="Mật khẩu (ít nhất 6 ký tự)" class="form-control" required>
                </div>

                <div class="form-group icon-input">
                    <textarea id="address" name="Address" placeholder="Địa chỉ" class="form-control"></textarea>
                </div>

                <hr style="margin: 20px 0;">
                <h5>Bước 2: Thêm khuôn mặt của bạn</h5>
                <div id="face-register-view">
                    <div class="video-container">
                        <video id="videoFeed" autoplay muted playsinline></video>
                    </div>
                    <button type="button" id="btnStartCamera" class="btn-camera" style="display: block !important;">Bật Camera</button>
                        (Dùng để đăng nhập nhanh hơn)
                    </small>
                </div>
                <button type="submit" class="btn-register" style="margin-top: 20px;">Hoàn tất Đăng Ký</button>
            </form>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script src="https://unpkg.com/face-api.js@0.22.2/dist/face-api.min.js"></script>
    
<script src="/TechFixPHP/assets/js/faceAuth.js"></script>
    <script>
        const btnStartCamera = document.getElementById('btnStartCamera');
        const registerForm = document.getElementById('registerForm');
        const videoFeed = document.getElementById("videoFeed");
        const toast = document.getElementById("toast");

        // Hàm showToast (từ code của bạn)
        function showToast(message, type = "info") {
            toast.textContent = message;
            toast.style.background = type === "error" ? "#d9534f" :
                                     type === "success" ? "#28a745" :
                                     type === "warning" ? "#f0ad4e" : "#333";
            toast.classList.add("show");
            setTimeout(() => toast.classList.remove("show"), 4000);
        }

        // Khi nhấn nút "Bật Camera"
        btnStartCamera.addEventListener("click", async () => {
            showToast("Đang tải models AI (~5MB)...", "info");
            await loadModels(); // Tải models từ faceAuth.js
            showToast("Đang bật camera...", "info");
            
            const started = await startVideo("videoFeed"); // Bật video
            
            if (started) {
                showToast("Đã bật camera! Hãy nhìn thẳng.", "success");
                btnStartCamera.style.display = 'none'; // Ẩn nút đi
            } else {
                showToast("Lỗi bật camera! Vui lòng cho phép trình duyệt truy cập.", "error");
            }
        });

        // Khi nhấn nút "Hoàn tất Đăng Ký"
        registerForm.addEventListener("submit", async (e) => {
            e.preventDefault(); // <-- Ngăn chặn POST truyền thống
            showToast("Đang xử lý, vui lòng chờ...", "info");

            // 1. Chụp ảnh và lấy đặc trưng khuôn mặt
            const descriptor = await getFaceDescriptor("videoFeed");
            if (!descriptor) {
                showToast("Không nhận diện được khuôn mặt. Vui lòng bật camera và nhìn thẳng.", "error");
                return;
            }

            // 2. Lấy thông tin form
            const name = document.getElementById('name').value;
            const phone = document.getElementById('phone').value;
            const password = document.getElementById('password').value;
            const address = document.getElementById('address').value;

            // 3. Gửi tất cả về server (api/register-face.php)
            try {
                // (Đường dẫn từ /pages/public_page/register.php)
                const res = await fetch("../api/register-face.php", {
                    method: "POST",
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        name: name, 
                        phone: phone, 
                        password: password, 
                        address: address,
                        descriptor: Array.from(descriptor) // Chuyển thành mảng thường
                    })
                });

                const data = await res.json();

                if (data.success) {
                    showToast("Đăng ký thành công! Đang chuyển đến trang đăng nhập.", "success");
                    stopVideo(); // Tắt camera
                    setTimeout(() => {
                        window.location.href = "login.php"; // Chuyển về trang login
                    }, 2000);
                } else {
                    showToast(data.message, "error");
                }
            } catch (err) {
                showToast("Lỗi máy chủ: " + err.message, "error");
            }
        });

        // Tắt camera khi rời trang
        window.addEventListener("beforeunload", () => stopVideo());
    </script>
</body>
</html>