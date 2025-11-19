<?php
session_start();

$isLoggedIn = isset($_SESSION['role']);
$role = $_SESSION['role'] ?? null;
$name = $_SESSION['name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TECHFIX - Dịch vụ sửa chữa toàn diện</title>
    
    <link rel="stylesheet" href="/TechFixPHP/assets/css/home.css">
    
<link rel="manifest" href="/TechFixPHP/manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <link rel="apple-touch-icon" href="/TechFixPHP/assets/image/vlute2.png">
</head>
<body>

    <nav class="navbar">
        <div class="container">
            <div class="flex items-center">
                <img src="/TechFixPHP/assets/image/VLUTE.png" alt="Logo" 
                     style="width:40px; height:40px; object-fit:contain; margin-right:8px;">
                <h1 class="logo" style="margin:0; display:inline-block;">TECHFIX</h1>
            </div>

            <div class="nav-links">
                <button id="install-app-btn" style="display: none; margin-left: 10px; padding: 6px 12px; background-color: #28a745; color: white; border: none; border-radius: 20px; cursor: pointer; font-weight: bold; font-size: 13px; transition: all 0.3s;">
                    📲 Tải App
                </button>
                <a href="#home">Trang Chủ</a>
                <a href="/TechFixPHP/Customer/Service.php">Dịch Vụ</a>
                <a href="#about">Về Chúng Tôi</a>
                <a href="#contact">Liên Hệ</a>
                <a href="/TechFixPHP/Customer/my_booking.php">Lịch Đặt</a>

                
                <?php if ($isLoggedIn): ?>
                    <a href="/TechFixPHP/pages/public_page/settings.php">Cài Đặt</a>
                <?php endif; ?>

                <?php if ($role === 'admin'): ?>
                    <a href="/TechFixPHP/pages/admin/dashboard.php">Trang Quản Trị</a>
                <?php elseif ($role === 'technical'): ?>
                    <a href="/TechFixPHP/pages/admin/tech_schedule.php">Lịch Làm Việc</a>
                <?php endif; ?>
                
                <?php if (!$isLoggedIn): ?>
                    <a href="/TechFixPHP/pages/public_page/register.php">Đăng Ký</a>
                    <a href="/TechFixPHP/pages/public_page/login.php">Đăng Nhập</a>

                <?php else: ?>
                    <div class="user-menu">
                        <span>Xin chào, <?= htmlspecialchars($name) ?></span>
                        <a href="/TechFixPHP/pages/public_page/login.php" class="logout-btn">Đăng Xuất</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <section id="home" class="hero">
        <video autoplay muted loop playsinline class="hero-video">
            <source src="/TechFixPHP/assets/image/home.mp4" type="video/mp4">
        </video>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>TECHFIX – Giải Pháp Sửa Chữa & Dịch Vụ Toàn Diện</h1>
            <p>Từ ngôi nhà đến văn phòng, từ điện, nước, điện lạnh đến vệ sinh – TECHFIX mang đến dịch vụ nhanh chóng, uy tín và chuyên nghiệp cho mọi nhu cầu của bạn.</p>
            <div class="hero-actions">
                <a href="/TechFixPHP/Customer/book.php" class="btn-primary">Đặt Ngay</a>
                <a href="#about" class="btn-secondary">Tìm Hiểu Thêm</a>
            </div>
        </div>
    </section>

    <section id="services" class="section">
        <h2 class="section-title">Các Dịch Vụ TECHFIX</h2>
        <div class="slider-container">
            <div class="slide-track" id="slideTrack">
                <div class="slide">
                    <img src="/TechFixPHP/assets/image/car.jpg" alt="car">
                    <h3>Sửa Chữa & Bảo Trì Xe</h3>
                    <p>Dịch vụ sửa chữa và bảo trì xe chuyên nghiệp...</p>
                </div>
                <div class="slide">
                    <img src="/TechFixPHP/assets/image/pcc.jpg" alt="Sửa Chữa Máy Tính">
                    <h3>Sửa Chữa Máy Tính</h3>
                    <p>Sửa chữa máy tính từ phần cứng đến phần mềm...</p>
                </div>
                <div class="slide">
                    <img src="/TechFixPHP/assets/image/elec.jpg" alt="Electrical">
                    <h3>Sửa chữa & bảo trì hệ thống điện</h3>
                    <p>Dịch vụ điện dân dụng và công nghiệp toàn diện...</p>
                </div>
                <div class="slide">
                    <img src="/TechFixPHP/assets/image/air.jpg" alt="air-conditioned">
                    <h3>Sửa Chữa & Vệ Sinh Điện Lạnh</h3>
                    <p>Vệ sinh, sửa chữa và bảo trì hệ thống điện lạnh định kỳ...</p>
                </div>
            </div>
            <button class="control-btn" id="prevBtn">❮</button>
            <button class="control-btn" id="nextBtn">❯</button>
            <div class="pagination" id="pagination"></div>
        </div>
        <div class="more-btn-container">
            <a href="/TechFixPHP/Customer/Service.php" class="btn-primary">Tìm Hiểu Thêm</a>
        </div>
    </section>

    <section id="about" class="section light">
        <h2 class="section-title">Giới Thiệu TECHFIX</h2>
        <div class="about-container">
            <img src="/TechFixPHP/assets/image/hometech.jpg" alt="HomeTech logo" />
            <div>
                <p>TECHFIX là nền tảng dịch vụ gia đình hiện đại, kết nối khách hàng với đội ngũ kỹ thuật viên uy tín và chuyên nghiệp.</p>
                <p>Chỉ với vài thao tác đơn giản, bạn có thể dễ dàng đặt lịch sửa chữa, bảo trì hay vệ sinh nhà cửa ngay tại nhà.</p>
                <p>Với TECHFIX, chúng tôi mang đến trải nghiệm nhanh chóng, minh bạch và an toàn – giúp bạn tận hưởng không gian sống tiện nghi hơn mỗi ngày.</p>
            </div>
        </div>
    </section>

    <section id="contact" class="section">
        <h2 class="section-title">Liên Hệ</h2>
        <form class="contact-form">
            <input type="text" placeholder="Tên của bạn">
            <input type="email" placeholder="Email của bạn">
            <textarea rows="5" placeholder="Gửi về chúng tôi..."></textarea>
            <button type="submit" class="btn-primary w-full">Gửi</button>
        </form>
        <div class="map-container">
            <iframe src="https://www.google.com/maps/embed?pb=..." allowfullscreen loading="lazy"></iframe>
        </div>
    </section>

    <footer class="footer">
        <div>
            <h3>TECHFIX</h3>
            <p>ĐẶT NIỀM TIN - TRAO CHỮ TÍN</p>
        </div>
        <div>
            <h3>Liên Kết Nhanh</h3>
            <a href="#services">Dịch Vụ</a> |
            <a href="#about">Về Chúng Tôi</a> |
            <a href="#contact">Liên Hệ</a>
        </div>
        <div>
            <h3>Thông Tin Liên Hệ</h3>
            <p>Email: support@techfix.com</p>
            <p>Điện thoại: +84 123 456 789</p>
            <p>Địa chỉ: P4 Phạm Thái Bường</p>
        </div>
        <p class="copy">© 2025 TECHFIX. All rights reserved.</p>
    </footer>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const track = document.getElementById("slideTrack");
            const slides = track ? track.children : [];
            const prevBtn = document.getElementById("prevBtn");
            const nextBtn = document.getElementById("nextBtn");
            const pagination = document.getElementById("pagination");
            let index = 0;

            if (pagination && slides.length > 0) {
                pagination.innerHTML = "";
                for (let i = 0; i < slides.length; i++) {
                    const dot = document.createElement("span");
                    dot.addEventListener("click", () => showSlide(i));
                    pagination.appendChild(dot);
                }
            }

            function updatePagination() {
                if (!pagination) return;
                [...pagination.children].forEach((dot, i) => {
                    dot.classList.toggle("active", i === index);
                });
            }

            function showSlide(i) {
                if (!track || slides.length === 0) return;
                if (i < 0) index = slides.length - 1;
                else if (i >= slides.length) index = 0;
                else index = i;

                track.style.transform = `translateX(-${index * 100}%)`;
                updatePagination();
            }

            if (prevBtn && nextBtn) {
                prevBtn.addEventListener("click", () => showSlide(index - 1));
                nextBtn.addEventListener("click", () => showSlide(index + 1));
            }

            if (slides.length > 0) {
                setInterval(() => showSlide(index + 1), 5000);
            }

            showSlide(0);
        });
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const aboutSection = document.querySelector(".about-container");
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    aboutSection.classList.add("show");
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });

        if (aboutSection) {
            observer.observe(aboutSection);
        }
    });
    </script>

    <?php include __DIR__ . '/pages/public_page/chatbot.php'; ?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const chatButton = document.getElementById("chatButton");
            const chatWindow = document.getElementById("chatWindow");
            const closeChat = document.getElementById("closeChat");

            if(chatButton && chatWindow && closeChat) {
                chatButton.addEventListener("click", () => {
                    chatWindow.style.display = "block";
                });
                closeChat.addEventListener("click", () => {
                    chatWindow.style.display = "none";
                });
            }
        });
    </script>

    <script type="text/javascript">
    var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
    (function(){
    var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
    s1.async=true;
    s1.src='https://embed.tawk.to/6905f9c17909cc195310a2e1/1j8vlf7ib';
    s1.charset='UTF-8';
    s1.setAttribute('crossorigin','*');
    s0.parentNode.insertBefore(s1,s0);
    })();
    </script>

    <script>
    // 1. Đăng ký Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
navigator.serviceWorker.register('/TechFixPHP/sw.js')
            .then(reg => console.log('PWA Service Worker Ready! Scope:', reg.scope))
            .catch(err => console.log('PWA Error:', err));
        });
    }

    // 2. Xử lý nút cài đặt App
    document.addEventListener('DOMContentLoaded', () => {
        let deferredPrompt;
        const installBtn = document.getElementById('install-app-btn');

        // Lắng nghe sự kiện trình duyệt cho phép cài đặt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault(); // Chặn popup mặc định
            deferredPrompt = e;
            
            // Hiện nút bấm
            if(installBtn) {
                installBtn.style.display = 'inline-block';
            }
        });

        // Xử lý khi bấm nút
        if(installBtn) {
            installBtn.addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    console.log(`Kết quả cài đặt: ${outcome}`);
                    deferredPrompt = null;
                    installBtn.style.display = 'none'; // Ẩn nút sau khi bấm
                }
            });
        }
        
        // Nếu đã cài app rồi thì ẩn nút luôn
        window.addEventListener('appinstalled', () => {
            console.log('TechFix đã được cài đặt thành công!');
            if(installBtn) installBtn.style.display = 'none';
        });
    });
    </script>

</body>
</html>