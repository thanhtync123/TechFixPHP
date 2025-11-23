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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        /* Cấu trúc Navbar chia 3 phần */
        .navbar .container { display: flex; justify-content: space-between; align-items: center; width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 15px; }
        .nav-left { display: flex; align-items: center; min-width: 150px; }
        .nav-center { flex: 1; display: flex; justify-content: center; margin: 0 20px; max-width: 500px; }
        
        .search-box-wrapper { position: relative; width: 100%; display: flex; align-items: center; }
        .search-box-wrapper input { width: 100%; padding: 10px 45px 10px 20px; border-radius: 50px; border: 1px solid #e0e0e0; background: #f5f5f5; font-size: 14px; outline: none; transition: all 0.3s ease; }
        .search-box-wrapper input:focus { background: #fff; border-color: #0d6efd; box-shadow: 0 2px 8px rgba(13, 110, 253, 0.15); }
        .voice-btn { position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: white; border-radius: 50%; width: 35px; height: 35px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; transition: 0.2s; }
        .voice-btn:hover { background: #e7f1ff; color: #0d6efd; }

        .nav-right { display: flex; align-items: center; gap: 15px; }
        .nav-right a { text-decoration: none; color: #333; font-weight: 500; font-size: 14px; transition: color 0.3s; white-space: nowrap; }
        .nav-right a:hover { color: #0d6efd; }

        #install-app-btn { display: none; padding: 6px 15px; background: linear-gradient(45deg, #28a745, #218838); color: white; border: none; border-radius: 20px; cursor: pointer; font-size: 13px; font-weight: bold; white-space: nowrap; }

        @media (max-width: 900px) { .nav-center { display: none; } }

        /* Voice Overlay */
        #voiceOverlay { backdrop-filter: blur(8px); }
        .voice-wave { width: 80px; height: 80px; border-radius: 50%; background: #0d6efd; position: relative; display: flex; justify-content: center; align-items: center; animation: pulse-blue 1.5s infinite; }
        .voice-wave::after { content: '🎙️'; font-size: 40px; }
        @keyframes pulse-blue { 0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.7); } 70% { transform: scale(1.1); box-shadow: 0 0 0 30px rgba(13, 110, 253, 0); } 100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); } }

        /* CSS CHO SECTION BẢO HÀNH (MỚI) */
        .warranty-section {
            background: linear-gradient(135deg, #e3f2fd 0%, #f8f9fa 100%);
            padding: 60px 20px; text-align: center;
        }
        .warranty-box {
            background: white; max-width: 700px; margin: 0 auto;
            padding: 40px; border-radius: 20px; box-shadow: 0 15px 30px rgba(0,0,0,0.05);
        }
        .warranty-input-group {
            display: flex; gap: 10px; margin-top: 20px; justify-content: center; flex-wrap: wrap;
        }
        .warranty-input {
            flex: 1; min-width: 250px; padding: 15px 20px;
            border: 2px solid #eee; border-radius: 50px; font-size: 16px; outline: none; transition: 0.3s;
        }
        .warranty-input:focus { border-color: #0d6efd; }
        .warranty-btn {
            padding: 15px 40px; background: #0d6efd; color: white;
            border: none; border-radius: 50px; font-weight: bold; font-size: 16px; cursor: pointer;
            transition: 0.3s; box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }
        .warranty-btn:hover { background: #0b5ed7; transform: translateY(-2px); }
    </style>
</head>
<body>

    <nav class="navbar" style="background: white; padding: 10px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000;">
        <div class="container">
            <div class="nav-left">
                <a href="#home" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                    <img src="/TechFixPHP/assets/image/VLUTE.png" alt="Logo" style="width:40px; height:40px; object-fit:contain; margin-right:8px;">
                    <h1 class="logo" style="margin:0; font-size: 24px; color: #0d6efd; font-weight: 800;">TECHFIX</h1>
                </a>
            </div>

            <div class="nav-center">
                <form action="/TechFixPHP/Customer/Service.php" method="GET" class="search-box-wrapper">
                    <input type="text" name="search" id="voiceSearchInput" placeholder="Tìm dịch vụ (nói 'Sửa máy tính')..." autocomplete="off">
                    <button type="button" class="voice-btn" onclick="startVoiceSearch()" title="Tìm bằng giọng nói">🎙️</button>
                </form>
            </div>

            <div class="nav-right">
                <button id="install-app-btn">📲 Tải App</button>
                <a href="#home">Trang Chủ</a>
                <a href="/TechFixPHP/Customer/Service.php">Dịch Vụ</a>
                <a href="/TechFixPHP/Customer/my_booking.php">Lịch Đặt</a>
                <?php if ($isLoggedIn): ?>
                    <a href="/TechFixPHP/pages/public_page/settings.php">Cài Đặt</a>
                <?php endif; ?>
                <?php if ($role === 'admin'): ?>
                    <a href="/TechFixPHP/pages/admin/dashboard.php" style="color: #d63384;">Quản Trị</a>
                <?php elseif ($role === 'technical'): ?>
                    <a href="/TechFixPHP/pages/admin/tech_schedule.php">Lịch Làm Việc</a>
                <?php endif; ?>
                <?php if (!$isLoggedIn): ?>
                    <span style="color:#ddd">|</span>
                    <a href="/TechFixPHP/pages/public_page/login.php" style="font-weight: bold;">Đăng Nhập</a>
                <?php else: ?>
                    <span style="color:#ddd">|</span>
                    <div class="user-menu" style="display: flex; align-items: center; gap: 5px;">
                        <span style="color: #666;">Hi, <b><?= htmlspecialchars($name) ?></b></span>
                        <a href="/TechFixPHP/pages/public_page/login.php" class="logout-btn" style="color: #dc3545; font-size: 18px;" title="Đăng Xuất">⏻</a>
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
            <p>Từ ngôi nhà đến văn phòng, từ điện, nước, điện lạnh đến vệ sinh – TECHFIX mang đến dịch vụ nhanh chóng, uy tín và chuyên nghiệp.</p>
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
                <div class="slide"><img src="/TechFixPHP/assets/image/car.jpg"><h3>Sửa Chữa Xe</h3><p>Dịch vụ chuyên nghiệp...</p></div>
                <div class="slide"><img src="/TechFixPHP/assets/image/pcc.jpg"><h3>Sửa Máy Tính</h3><p>Phần cứng & phần mềm...</p></div>
                <div class="slide"><img src="/TechFixPHP/assets/image/elec.jpg"><h3>Điện Dân Dụng</h3><p>Sửa chữa hệ thống điện...</p></div>
                <div class="slide"><img src="/TechFixPHP/assets/image/air.jpg"><h3>Điện Lạnh</h3><p>Vệ sinh & bảo trì...</p></div>
            </div>
            <button class="control-btn" id="prevBtn">❮</button>
            <button class="control-btn" id="nextBtn">❯</button>
            <div class="pagination" id="pagination"></div>
        </div>
        <div class="more-btn-container">
            <a href="/TechFixPHP/Customer/Service.php" class="btn-primary">Xem Tất Cả Dịch Vụ</a>
        </div>
    </section>

    <section id="warranty" class="warranty-section">
        <div class="container">
            <div class="warranty-box">
                <i class="fa-solid fa-shield-halved" style="font-size: 50px; color: #0d6efd; margin-bottom: 15px;"></i>
                <h2 style="color: #333; margin-bottom: 10px;">Tra Cứu Bảo Hành Điện Tử</h2>
                <p style="color: #666;">Kiểm tra thời hạn bảo hành nhanh chóng bằng <b>Mã đơn hàng</b> hoặc <b>Số điện thoại</b>.</p>
                
                <form action="/TechFixPHP/warranty.php" method="GET" class="warranty-input-group">
                    <input type="text" name="keyword" class="warranty-input" placeholder="Nhập mã đơn hoặc SĐT..." required>
                    <button type="submit" class="warranty-btn">
                        <i class="fa-solid fa-magnifying-glass"></i> Tra Cứu
                    </button>
                </form>
            </div>
        </div>
    </section>
    <section id="about" class="section light">
        <h2 class="section-title">Giới Thiệu</h2>
        <div class="about-container">
            <img src="/TechFixPHP/assets/image/vlute.png" />
            <div>
                <p>TECHFIX là nền tảng dịch vụ gia đình hiện đại, kết nối khách hàng với đội ngũ kỹ thuật viên uy tín.</p>
            </div>
        </div>
    </section>

    <section id="contact" class="section">
        <h2 class="section-title">Liên Hệ</h2>
        <form class="contact-form">
            <input type="text" placeholder="Tên của bạn">
            <input type="email" placeholder="Email">
            <textarea rows="5" placeholder="Nội dung..."></textarea>
            <button type="submit" class="btn-primary w-full">Gửi</button>
        </form>
        <div class="map-container">
             <iframe src="https://www.google.com/maps/embed?pb=..." allowfullscreen loading="lazy"></iframe>
        </div>
    </section>

    <footer class="footer">
        <div><h3>TECHFIX</h3><p>UY TÍN - CHẤT LƯỢNG</p></div>
        <div><h3>Liên Kết</h3><a href="#services">Dịch Vụ</a> | <a href="#contact">Liên Hệ</a></div>
        <div><h3>Liên Hệ</h3><p>Hotline: 1900 1234</p></div>
        <p class="copy">© 2025 TECHFIX.</p>
    </footer>

    <div id="voiceOverlay" style="display:none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.85); z-index: 99999; justify-content: center; align-items: center; flex-direction: column; color: white;">
        <div class="voice-wave"></div>
        <h2 id="voiceStatus" style="margin-top: 30px; font-weight: 300; font-family: sans-serif;">Đang nghe...</h2>
        <p style="color: #ccc; margin-top: 10px;">Hãy nói tên dịch vụ (Ví dụ: "Sửa máy lạnh")</p>
        <button onclick="closeVoiceSearch()" style="margin-top: 30px; padding: 8px 25px; background: #ff4757; color: white; border: none; border-radius: 20px; cursor: pointer; font-weight: bold;">Hủy bỏ</button>
    </div>

    <script>
        // Giữ nguyên script cũ của bạn
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
                [...pagination.children].forEach((dot, i) => { dot.classList.toggle("active", i === index); });
            }
            function showSlide(i) {
                if (!track) return;
                if (i < 0) index = slides.length - 1; else if (i >= slides.length) index = 0; else index = i;
                track.style.transform = `translateX(-${index * 100}%)`;
                updatePagination();
            }
            if (prevBtn && nextBtn) {
                prevBtn.addEventListener("click", () => showSlide(index - 1));
                nextBtn.addEventListener("click", () => showSlide(index + 1));
            }
            if (slides.length > 0) { setInterval(() => showSlide(index + 1), 5000); showSlide(0); }
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
        if (aboutSection) observer.observe(aboutSection);
    });
    </script>

    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/TechFixPHP/sw.js').catch(err => console.log('PWA Error:', err));
        });
    }
    document.addEventListener('DOMContentLoaded', () => {
        let deferredPrompt;
        const installBtn = document.getElementById('install-app-btn');
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault(); deferredPrompt = e;
            if(installBtn) installBtn.style.display = 'inline-block';
        });
        if(installBtn) {
            installBtn.addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    deferredPrompt = null;
                    installBtn.style.display = 'none';
                }
            });
        }
        window.addEventListener('appinstalled', () => {
            if(installBtn) installBtn.style.display = 'none';
        });
    });

    function startVoiceSearch() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            alert("Trình duyệt này không hỗ trợ tìm kiếm giọng nói (Dùng Chrome nhé!).");
            return;
        }
        const recognition = new SpeechRecognition();
        const overlay = document.getElementById('voiceOverlay');
        const statusText = document.getElementById('voiceStatus');
        const searchInput = document.getElementById('voiceSearchInput');
        recognition.lang = 'vi-VN'; 
        recognition.interimResults = false; 
        recognition.start();
        overlay.style.display = 'flex';
        statusText.innerText = "Đang nghe...";
        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            statusText.innerText = `Đã nhận diện: "${transcript}"`;
            setTimeout(() => {
                if(searchInput) searchInput.value = transcript;
                window.location.href = `/TechFixPHP/Customer/Service.php?search=${encodeURIComponent(transcript)}`;
            }, 800);
        };
        recognition.onerror = () => {
            statusText.innerText = "Không nghe rõ. Thử lại nhé!";
            setTimeout(() => { overlay.style.display = 'none'; }, 2000);
        };
    }
    function closeVoiceSearch() {
        document.getElementById('voiceOverlay').style.display = 'none';
        window.location.reload();
    }
    </script>

    <?php include __DIR__ . '/pages/public_page/chatbot.php'; ?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const chatButton = document.getElementById("chatButton");
            const chatWindow = document.getElementById("chatWindow");
            const closeChat = document.getElementById("closeChat");
            if(chatButton && chatWindow && closeChat) {
                chatButton.addEventListener("click", () => chatWindow.style.display = "block");
                closeChat.addEventListener("click", () => chatWindow.style.display = "none");
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

</body>
</html>