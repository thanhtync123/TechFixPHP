<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Lỗi Hệ Thống | TechFix</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --bg-color: #050505;
            --text-color: #f0f0f0;
            --primary: #0d6efd;
            --glitch-color-1: #ff00ff; /* Màu hồng */
            --glitch-color-2: #00ffff; /* Màu xanh cyan */
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Fira Code', monospace; /* Font kiểu code */
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            flex-direction: column;
            text-align: center;
            position: relative;
        }

        /* Hiệu ứng ồn (Noise Background) */
        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
            opacity: 0.05;
            pointer-events: none;
            z-index: -1;
        }

        .container { position: relative; z-index: 1; padding: 20px; }

        /* SỐ 404 GLITCH */
        .glitch {
            font-size: 8rem;
            font-weight: 700;
            position: relative;
            color: var(--text-color);
            letter-spacing: 5px;
        }

        .glitch::before, .glitch::after {
            content: attr(data-text);
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: var(--bg-color);
        }

        .glitch::before {
            color: var(--glitch-color-1);
            z-index: -1;
            animation: glitch-anim-1 2.5s infinite linear alternate-reverse;
        }

        .glitch::after {
            color: var(--glitch-color-2);
            z-index: -2;
            animation: glitch-anim-2 3s infinite linear alternate-reverse;
        }

        @keyframes glitch-anim-1 {
            0% { clip-path: inset(20% 0 80% 0); transform: translate(-2px, 2px); }
            20% { clip-path: inset(60% 0 10% 0); transform: translate(2px, -2px); }
            40% { clip-path: inset(40% 0 50% 0); transform: translate(2px, 2px); }
            60% { clip-path: inset(80% 0 5% 0); transform: translate(-2px, -2px); }
            80% { clip-path: inset(10% 0 70% 0); transform: translate(2px, 2px); }
            100% { clip-path: inset(30% 0 50% 0); transform: translate(-2px, -2px); }
        }

        @keyframes glitch-anim-2 {
            0% { clip-path: inset(10% 0 60% 0); transform: translate(2px, -2px); }
            20% { clip-path: inset(30% 0 20% 0); transform: translate(-2px, 2px); }
            40% { clip-path: inset(70% 0 10% 0); transform: translate(2px, 2px); }
            60% { clip-path: inset(20% 0 50% 0); transform: translate(-2px, -2px); }
            80% { clip-path: inset(50% 0 30% 0); transform: translate(2px, -2px); }
            100% { clip-path: inset(0% 0 80% 0); transform: translate(-2px, 2px); }
        }

        /* Text thông báo */
        .message { margin: 20px 0; }
        .message h2 { font-size: 1.5rem; margin-bottom: 10px; color: #fff; }
        .message p { color: #aaa; font-size: 1rem; max-width: 500px; margin: 0 auto; line-height: 1.6; }

        /* Terminal Effect */
        .terminal {
            background: #111;
            border: 1px solid #333;
            padding: 15px;
            border-radius: 5px;
            margin: 30px auto;
            width: fit-content;
            text-align: left;
            font-size: 0.9rem;
            color: #00ff00;
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.1);
        }
        .cursor {
            display: inline-block;
            width: 8px; height: 15px;
            background: #00ff00;
            animation: blink 1s infinite;
        }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }

        /* Nút bấm */
        .btn-home {
            display: inline-block;
            padding: 12px 30px;
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            text-decoration: none;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: 0.3s;
            margin-top: 20px;
            position: relative;
            overflow: hidden;
        }

        .btn-home::before {
            content: "";
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: var(--primary);
            transition: 0.3s;
            z-index: -1;
        }

        .btn-home:hover {
            color: #fff;
            box-shadow: 0 0 20px rgba(13, 110, 253, 0.6);
        }
        .btn-home:hover::before { left: 0; }

        /* Icon cờ lê bay bay */
        .floating-tool {
            position: absolute;
            color: #333;
            font-size: 3rem;
            opacity: 0.3;
            animation: floatTool 15s infinite linear;
            z-index: 0;
        }
        @keyframes floatTool {
            0% { transform: translateY(100vh) rotate(0deg); }
            100% { transform: translateY(-10vh) rotate(360deg); }
        }

    </style>
</head>
<body>

    <i class="fa-solid fa-screwdriver-wrench floating-tool" style="left: 10%; animation-duration: 12s;"></i>
    <i class="fa-solid fa-gear floating-tool" style="left: 80%; animation-duration: 18s; font-size: 5rem;"></i>
    <i class="fa-solid fa-microchip floating-tool" style="left: 50%; animation-duration: 25s; font-size: 2rem;"></i>

    <div class="container">
        <h1 class="glitch" data-text="404">404</h1>

        <div class="message">
            <h2>OOPS! HỆ THỐNG GẶP SỰ CỐ</h2>
            <p>Trang bạn đang tìm kiếm đã bị hỏng, bị xóa hoặc đường dẫn không chính xác. Đội ngũ kỹ thuật TechFix đang (không) xử lý vấn đề này.</p>
        </div>

        <div class="terminal">
            > ERROR_CODE: PAGE_NOT_FOUND<br>
            > LOCATION: /undefined<br>
            > SYSTEM: TechFix Server v2.0<br>
            > ACTION: Return to Base...<span class="cursor"></span>
        </div>

        <a href="/TechFixPHP/index.php" class="btn-home">
            <i class="fa-solid fa-house-signal"></i> Quay Về Trang Chủ
        </a>
    </div>

</body>
</html>