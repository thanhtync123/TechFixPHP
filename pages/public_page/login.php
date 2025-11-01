<?php
// login.php
session_start();
include "../../config/db.php"; // Đường dẫn đúng tới config/db.php

// Xử lý đăng nhập bằng tài khoản/mật khẩu (khi gọi từ JS fetch)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $data = json_decode(file_get_contents('php://input'), true);
    $phone = mysqli_real_escape_string($conn, $data['phone'] ?? '');
    $password = $data['password'] ?? ''; // Lưu ý: nên mã hóa mật khẩu trong thực tế

    $query = "SELECT * FROM users WHERE phone = '$phone' AND password = '$password'";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Sai tài khoản hoặc mật khẩu']);
        exit;
    }

    // Lưu session
    // Lưu session (THAY đoạn cũ)
    $_SESSION['user'] = $user;
    $_SESSION['phone'] = $user['phone'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];

    // Trả về JSON cho frontend
    echo json_encode([
        'id' => $user['id'],
        'name' => $user['name'],
        'phone' => $user['phone'],
        'role' => $user['role']
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - TECHFIX</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../../../TechFixPHP/assets/css/login.css">

    <style>
        body, html { overflow: hidden; margin: 0; height: 100vh; }
        .or-separator { text-align: center; margin: 1.5rem 0; color: #888; font-weight: 600; }
        .btn-face-login {
            width: 100%; padding: 0.75rem; background-color: #3b5998; color: white;
            border: none; border-radius: 8px; font-weight: 700; cursor: pointer;
            transition: background-color 0.3s; display: flex; align-items: center;
            justify-content: center; gap: 0.5rem;
        }
        .btn-face-login:hover { background-color: #334d84; }
        .face-login-view .video-container {
            position: relative; width: 100%; padding-top: 75%; background: #000;
            border-radius: 8px; overflow: hidden; margin-bottom: 1rem;
        }
        .face-login-view video {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;
        }
        .btn-cancel {
            background: #6c757d; color: white; margin-top: 0.5rem; width: 100%;
            padding: 0.75rem; border: none; border-radius: 8px; cursor: pointer;
        }
        .toast {
            position: fixed; bottom: 20px; right: 20px; background: #333; color: #fff;
            padding: 1rem 1.5rem; border-radius: 8px; opacity: 0; transition: opacity 0.4s; z-index: 9999;
        }
        .toast.show { opacity: 1; }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="particles">
            <!-- Tạo hạt ngẫu nhiên -->
            <script>
                for (let i = 0; i < 30; i++) {
                    const p = document.createElement("div");
                    p.className = "particle";
                    p.style.left = Math.random() * 100 + "%";
                    p.style.top = Math.random() * 100 + "%";
                    p.style.animationDelay = (Math.random() * 15) + "s";
                    document.write(p.outerHTML);
                }
            </script>
        </div>

        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h2>Đăng Nhập</h2>
                    <p>Chào mừng bạn đến với TECHFIX</p>
                </div>

                <!-- Giao diện đăng nhập bằng khuôn mặt -->
                <div id="face-login-view" style="display:none;">
                    <p style="text-align: center; margin-bottom: 1rem;">Vui lòng nhìn thẳng vào camera</p>
                    <div class="video-container">
                        <video id="videoFeed" autoplay muted playsinline></video>
                    </div>
                    <button id="btnVerifyFace" class="btn-login">Xác thực</button>
                    <button id="btnCancelFace" class="btn-cancel">Hủy</button>
                </div>

                <!-- Form đăng nhập truyền thống -->
                <form id="loginForm" class="login-form">
                    <div class="form-group icon-input">
                        <input type="text" id="phone" placeholder="Số điện thoại" class="form-control" required>
                    </div>

                    <div class="form-group icon-input">
                        <input type="password" id="password" placeholder="Mật khẩu" class="form-control" required>
                    </div>

                    <button type="submit" class="btn-login">Đăng Nhập</button>
                </form>

                <div class="or-separator">HOẶC</div>

                <button id="btnFaceLogin" class="btn-face-login">
                    <span class="icon-face"></span> Đăng nhập bằng khuôn mặt
                </button>

                <div class="register-link">
                    <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
                </div>
            </div>

            <div class="company-info">
                <img src="../../../TechFixPHP/assets/image/logo.png" alt="Techfix Logo" class="company-logo" />
                <div class="company-description">
                    <h3>Về TECHFIX</h3>
                    <p>TECHFIX là web trong lĩnh vực kỹ thuật & công nghệ, cung cấp các giải pháp sáng tạo và dịch vụ sửa chữa chất lượng cao. Chúng tôi cam kết mang đến trải nghiệm tốt nhất cho khách hàng.</p>
                </div>
            </div>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <!-- Face API -->
    <script src="js/faceAuth.js"></script>
    <script>
        const toast = document.getElementById("toast");
        const loginForm = document.getElementById("loginForm");
        const faceView = document.getElementById("face-login-view");
        const btnFaceLogin = document.getElementById("btnFaceLogin");
        const btnVerifyFace = document.getElementById("btnVerifyFace");
        const btnCancelFace = document.getElementById("btnCancelFace");
        const videoFeed = document.getElementById("videoFeed");

        function showToast(message, type = "info") {
            toast.textContent = message;
            toast.style.background = type === "error" ? "#d9534f" :
                                     type === "success" ? "#28a745" :
                                     type === "warning" ? "#f0ad4e" : "#333";
            toast.classList.add("show");
            setTimeout(() => toast.classList.remove("show"), 3000);
        }

        // Xử lý đăng nhập bằng tài khoản/mật khẩu
        loginForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const phone = document.getElementById("phone").value.trim();
            const password = document.getElementById("password").value;

            try {
                const res = await fetch("login.php", { // Gọi chính file này (xử lý JSON)
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    body: JSON.stringify({ phone, password })
                });

                if (!res.ok) {
                    const err = await res.json();
                    throw new Error(err.error || "Lỗi đăng nhập");
                }

                const user = await res.json();
                showToast("Đăng nhập thành công!", "success");

                // Lưu vào localStorage (nếu cần dùng ở frontend)
                localStorage.setItem("currentUser", JSON.stringify(user));

                // Redirect theo role
                setTimeout(() => {
    // Chuyển hướng theo vai trò
    if (user.role === "admin" || user.role === "technical") {
        window.location.href = "/TechFixPHP/index.php"; // Trang quản trị
    } else if (user.role === "customer") {
        window.location.href = "/TechFixPHP/index.php"; // Trang chính khách hàng
    } else {
        window.location.href = "/TechFixPHP/pages/public_page/login.php"; // fallback nếu role không xác định
    }
}, 1000);


            } catch (err) {
                showToast(err.message, "error");
            }
        });

        // Đăng nhập bằng khuôn mặt
        btnFaceLogin.addEventListener("click", async () => {
            faceView.style.display = "block";
            loginForm.style.display = "none";
            btnFaceLogin.style.display = "none";

            try {
                await loadModels();
                const started = await startVideo("videoFeed");
                if (!started) {
                    showToast("Không thể truy cập camera.", "error");
                    cancelFace();
                }
            } catch (err) {
                showToast("Lỗi khi tải tài nguyên khuôn mặt.", "error");
                cancelFace();
            }
        });

        btnVerifyFace.addEventListener("click", async () => {
            try {
                const descriptor = await getFaceDescriptor("videoFeed");
                if (!descriptor) {
                    showToast("Không tìm thấy khuôn mặt.", "warning");
                    return;
                }

                const res = await fetch("../api/verify-face.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ descriptor })
                });

                const user = await res.json();
                if (user && user.role) {
                    showToast("Xác thực thành công!", "success");
                    localStorage.setItem("currentUser", JSON.stringify(user));
                    setTimeout(() => {
                        window.location.href = user.role === "admin" ? "../admin/dashboard.php" : "../index.php";
                    }, 1000);
                } else {
                    showToast("Không nhận dạng được khuôn mặt.", "error");
                }
            } catch (err) {
                showToast("Lỗi xác thực khuôn mặt.", "error");
            }
        });

        btnCancelFace.addEventListener("click", cancelFace);

        function cancelFace() {
            faceView.style.display = "none";
            loginForm.style.display = "block";
            btnFaceLogin.style.display = "block";
            stopVideo();
        }

        window.addEventListener("beforeunload", () => stopVideo());
    </script>
</body>
</html>