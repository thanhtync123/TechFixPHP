<?php
// login.php
session_start();
include "../../config/db.php"; // Đường dẫn đúng tới config/db Ways

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

    $data = json_decode(file_get_contents('php://input'), true);
    $phone    = trim($data['phone'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($phone) || empty($password)) {
        http_response_code(401);
        echo json_encode(['error' => 'Vui lòng nhập đầy đủ thông tin']);
        exit;
    }

    // Lấy user theo số điện thoại
    $stmt = $conn->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    $user   = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Sai số điện thoại hoặc mật khẩu']);
        exit;
    }

    // === SO SÁNH MẬT KHẨU ===
    $storedPassword = $user['password'];

    // Trường hợp 1: Mật khẩu lưu dạng plaintext
    $plainMatch = ($password === $storedPassword);

    // Trường hợp 2: Mật khẩu lưu dạng MD5 (32 ký tự hex)
    $md5Match = (strlen($storedPassword) === 32 && md5($password) === $storedPassword);

    // Nếu cả 2 đều không khớp → sai mật khẩu
    if (!$plainMatch && !$md5Match) {
        http_response_code(401);
        echo json_encode(['error' => 'Sai số điện thoại hoặc mật khẩu']);
        exit;
    }

    // Đăng nhập thành công
    $_SESSION['user']     = $user;
    $_SESSION['phone']    = $user['phone'];
    $_SESSION['name']     = $user['name'];
    $_SESSION['role']     = $user['role'];
    $_SESSION['user_id']  = $user['id'];

    // Trả về thông tin user (không trả password)
    echo json_encode([
        'id'    => $user['id'],
        'name'  => $user['name'],
        'phone' => $user['phone'],
        'role'  => $user['role']
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
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
            object-fit: cover; transform: scaleX(-1);
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

                <div id="face-login-view" style="display:none;">
                    <p style="text-align: center; margin-bottom: 1rem;">Vui lòng nhìn thẳng vào camera</p>
                    <div class="video-container">
                        <video id="videoFeed" autoplay muted playsinline></video>
                    </div>
                    <button id="btnVerifyFace" class="btn-login" style="margin-top: 1rem;">Xác thực</button>
                    <button id="btnCancelFace" class="btn-cancel">Hủy</button>
                </div>

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
                <img src="../../../TechFixPHP/assets/image/vlute1.png" alt="Techfix Logo" class="company-logo" />
                <div class="company-description">
                    <h3>Về TECHFIX</h3>
                    <p>TECHFIX là nền tảng dịch vụ gia đình hiện đại, kết nối khách hàng với đội ngũ kỹ thuật viên uy tín và chuyên nghiệp.</p>
                    <p>Chỉ với vài thao tác đơn giản, bạn có thể dễ dàng đặt lịch sửa chữa, bảo trì hay vệ sinh nhà cửa ngay tại nhà.</p>
                    <p>Với TECHFIX, chúng tôi mang đến trải nghiệm nhanh chóng, minh bạch và an toàn – giúp bạn tận hưởng không gian sống tiện nghi hơn mỗi ngày.</p>
                </div>
            </div>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script src="https://unpkg.com/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script src="/TechFixPHP/assets/js/faceAuth.js"></script> 
    
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

        // Đăng nhập thường
        loginForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const phone = document.getElementById("phone").value.trim();
            const password = document.getElementById("password").value;

            try {
                const res = await fetch("login.php", {
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
                localStorage.setItem("currentUser", JSON.stringify(user));

                setTimeout(() => {
                    if (user.role === "admin") {
                        window.location.href = "/TechFixPHP/pages/admin/dashboard.php";
                    } else if (user.role === "technical") {
                        window.location.href = "/TechFixPHP/pages/admin/tech_schedule.php";
                    } else if (user.role === "customer") {
                        window.location.href = "/TechFixPHP/index.php";
                    } else {
                        window.location.href = "/TechFixPHP/pages/public_page/login.php";
                    }
                }, 1000);

            } catch (err) {
                showToast(err.message, "error");
            }
        });

        // Đăng nhập bằng khuôn mặt (giữ nguyên)
        btnFaceLogin.addEventListener("click", async () => {
            faceView.style.display = "block";
            loginForm.style.display = "none";
            btnFaceLogin.style.display = "none";

            try {
                showToast("Đang tải models AI...", "info");
                await loadModels();
                showToast("Đang bật camera...", "info");
                const started = await startVideo("videoFeed");
                if (!started) {
                    showToast("Không thể truy cập camera.", "error");
                    cancelFace();
                } else {
                    showToast("Đã bật camera. Nhấn 'Xác thực'.", "success");
                }
            } catch (err) {
                showToast("Lỗi khi tải tài nguyên khuôn mặt.", "error");
                cancelFace();
            }
        });

        btnVerifyFace.addEventListener("click", async () => {
            try {
                showToast("Đang xác thực...", "info");
                const descriptor = await getFaceDescriptor("videoFeed");
                if (!descriptor) {
                    showToast("Không tìm thấy khuôn mặt.", "warning");
                    return;
                }

                const res = await fetch("../api/verify-face.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ descriptor: Array.from(descriptor) })
                });

                if (!res.ok) {
                    const err = await res.json();
                    throw new Error(err.error || "Lỗi xác thực");
                }

                const user = await res.json();
                if (user && user.role) {
                    showToast("Xác thực thành công!", "success");
                    localStorage.setItem("currentUser", JSON.stringify(user));
                    setTimeout(() => {
                        if (user.role === "admin") {
                            window.location.href = "/TechFixPHP/pages/admin/dashboard.php";
                        } else if (user.role === "technical") {
                            window.location.href = "/TechFixPHP/pages/technical/tech_schedule.php";
                        } else if (user.role === "customer") {
                            window.location.href = "/TechFixPHP/index.php";
                        } else {
                            window.location.href = "/TechFixPHP/pages/public_page/login.php";
                        }
                    }, 1000);
                } else {
                    showToast("Không nhận dạng được khuôn mặt.", "error");
                }
            } catch (err) {
                showToast(err.message, "error");
            }
        });

        btnCancelFace.addEventListener("click", cancelFace);

        function cancelFace() {
            faceView.style.display = "none";
            loginForm.style.display = "block";
            btnFaceLogin.style.display = "flex";
            stopVideo();
        }

        window.addEventListener("beforeunload", () => stopVideo());
    </script>
</body>
</html>