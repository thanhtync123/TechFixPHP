<?php
// Dữ liệu dịch vụ với đường dẫn ảnh trỏ tới assets/image (web path)
$serviceGroups = [
    [
        "GroupName" => "Điện – Điện tử",
        "Services" => [
            ["Title" => "Sửa chữa & bảo trì hệ thống điện", "Description" => "Khắc phục sự cố, lắp đặt & bảo trì hệ thống điện dân dụng & công nghiệp.", "Image" => "/TechFixPHP/assets/image/fixelec.jpg"],
            ["Title" => "Lắp đặt & thay thế đèn, ổ cắm, công tắc", "Description" => "Thay thế nhanh chóng, đảm bảo an toàn & thẩm mỹ.", "Image" => "/TechFixPHP/assets/image/lapden.jpg"],
            ["Title" => "Lắp đặt & bảo trì điều hòa, quạt điện", "Description" => "Lắp đặt, bảo dưỡng điều hòa & quạt điện định kỳ.", "Image" => "/TechFixPHP/assets/image/air.jpg"],
            ["Title" => "Kiểm tra & đánh giá an toàn điện", "Description" => "Đo kiểm tải điện, phát hiện nguy cơ chập cháy, lập báo cáo an toàn.", "Image" => "/TechFixPHP/assets/image/ktraelec.jpg"],
            ["Title" => "Thi công hệ thống chiếu sáng thông minh", "Description" => "Giải pháp chiếu sáng IoT hiện đại, tiết kiệm năng lượng.", "Image" => "/TechFixPHP/assets/image/denTM.jpg"],
        ]
    ],
    [
        "GroupName" => "Nước – Môi trường",
        "Services" => [
            ["Title" => "Sửa chữa & bảo trì hệ thống nước", "Description" => "Xử lý rò rỉ, tắc nghẽn, thay thế thiết bị vệ sinh.", "Image" => "/TechFixPHP/assets/image/plumbing.jpg"],
            ["Title" => "Lắp đặt & sửa chữa máy bơm nước", "Description" => "Lắp đặt, bảo trì máy bơm dân dụng & công nghiệp.", "Image" => "/TechFixPHP/assets/image/waterpump.jpg"],
            ["Title" => "Chống thấm tường, sàn, mái", "Description" => "Giải pháp xử lý thấm dột lâu dài, chuyên nghiệp.", "Image" => "/TechFixPHP/assets/image/constructionn.jpg"],
            ["Title" => "Vệ sinh & bảo trì bể chứa, bồn nước", "Description" => "Vệ sinh định kỳ đảm bảo nguồn nước sạch.", "Image" => "/TechFixPHP/assets/image/watertank.jpg"],
            ["Title" => "Lắp đặt & bảo trì hệ thống lọc nước", "Description" => "Cung cấp & bảo dưỡng hệ thống lọc nước sinh hoạt.", "Image" => "/TechFixPHP/assets/image/waterfilter.jpg"],
        ]
    ],
    [
        "GroupName" => "Thiết bị gia dụng",
        "Services" => [
            ["Title" => "Sửa chữa tủ lạnh", "Description" => "Khắc phục các lỗi tủ lạnh không lạnh, kém lạnh, hỏng block.", "Image" => "/TechFixPHP/assets/image/fridge.jpg"],
            ["Title" => "Sửa chữa máy giặt", "Description" => "Xử lý các lỗi máy giặt không vắt, kêu to, rò nước.", "Image" => "/TechFixPHP/assets/image/washingmachine.jpg"],
            ["Title" => "Sửa chữa bếp từ, lò vi sóng, máy nước nóng", "Description" => "Sửa chữa sự cố điện tử gia dụng thường gặp.", "Image" => "/TechFixPHP/assets/image/kitchen.jpg"],
            ["Title" => "Vệ sinh & bảo dưỡng thiết bị gia dụng", "Description" => "Vệ sinh, bảo dưỡng điều hòa, tủ lạnh, quạt điện định kỳ.", "Image" => "/TechFixPHP/assets/image/appliances.jpg"],
        ]
    ],
    [
        "GroupName" => "CNTT – Viễn thông",
        "Services" => [
            ["Title" => "Hỗ trợ kỹ thuật IT", "Description" => "Cài đặt phần mềm, diệt virus, hỗ trợ từ xa.", "Image" => "/TechFixPHP/assets/image/computer.jpg"],
            ["Title" => "Sửa chữa laptop & PC", "Description" => "Khắc phục lỗi phần cứng, thay linh kiện, vệ sinh máy.", "Image" => "/TechFixPHP/assets/image/laptop.jpg"],
            ["Title" => "Cài đặt hệ điều hành", "Description" => "Cài Windows, Linux, macOS & ứng dụng cần thiết.", "Image" => "/TechFixPHP/assets/image/os.jpg"],
            ["Title" => "Lắp đặt & cấu hình mạng WiFi, router", "Description" => "Triển khai hệ thống mạng cho gia đình & doanh nghiệp.", "Image" => "/TechFixPHP/assets/image/network.jpg"],
            ["Title" => "Lắp đặt & bảo trì camera giám sát", "Description" => "Giải pháp an ninh thông minh, giám sát 24/7.", "Image" => "/TechFixPHP/assets/image/camera.jpg"],
            ["Title" => "Dịch vụ an ninh mạng & sao lưu dữ liệu", "Description" => "Đảm bảo an toàn dữ liệu & hệ thống CNTT.", "Image" => "/TechFixPHP/assets/image/security.jpg"],
        ]
    ],
    [
        "GroupName" => "An toàn – Kiểm định",
        "Services" => [
            ["Title" => "Kiểm định hệ thống phòng cháy chữa cháy", "Description" => "Đảm bảo hệ thống PCCC vận hành ổn định.", "Image" => "/TechFixPHP/assets/image/fire.jpg"],
            ["Title" => "Đánh giá chất lượng nguồn nước", "Description" => "Phân tích chất lượng & độ an toàn nước sinh hoạt.", "Image" => "/TechFixPHP/assets/image/waterquality.jpg"],
            ["Title" => "Kiểm định thiết bị công suất lớn", "Description" => "Đo kiểm máy móc công nghiệp, đảm bảo vận hành an toàn.", "Image" => "/TechFixPHP/assets/image/factory.jpg"],
            ["Title" => "Lập báo cáo vận hành & bảo trì định kỳ", "Description" => "Hồ sơ theo dõi & báo cáo kỹ thuật định kỳ.", "Image" => "/TechFixPHP/assets/image/report.jpg"],
        ]
    ],
    [
        "GroupName" => "Bảo trì – Quản lý thiết bị",
        "Services" => [
            ["Title" => "Bảo dưỡng định kỳ hệ thống kỹ thuật", "Description" => "Lập lịch bảo trì định kỳ cho hệ thống điện, nước, IT.", "Image" => "/TechFixPHP/assets/image/maintenance.jpg"],
            ["Title" => "Quản lý & thay thế vật tư tiêu hao", "Description" => "Theo dõi, cung cấp & thay thế vật tư cần thiết.", "Image" => "/TechFixPHP/assets/image/tools.jpg"],
            ["Title" => "Quản lý thiết bị kèm theo", "Description" => "Theo dõi, thay thế & nâng cấp thiết bị đi kèm dịch vụ.", "Image" => "/TechFixPHP/assets/image/equipment.jpg"],
            ["Title" => "Tư vấn & nâng cấp hệ thống", "Description" => "Đề xuất giải pháp cải tiến điện – nước – CNTT.", "Image" => "/TechFixPHP/assets/image/upgrade.jpg"],
        ]
    ],
    [
        "GroupName" => "Dịch vụ đặc biệt",
        "Services" => [
            ["Title" => "Vệ sinh công nghiệp", "Description" => "Vệ sinh nhà xưởng, văn phòng, tòa nhà chuyên nghiệp.", "Image" => "/TechFixPHP/assets/image/cleaning.jpg"],
            ["Title" => "Khử trùng & xử lý môi trường", "Description" => "Khử khuẩn, diệt côn trùng, xử lý rác thải.", "Image" => "/TechFixPHP/assets/image/sanitation.jpg"],
            ["Title" => "Dịch vụ khẩn cấp 24/7", "Description" => "Có mặt trong 30 phút khi khách cần gấp.", "Image" => "/TechFixPHP/assets/image/emergency.jpg"],
            ["Title" => "Hỗ trợ kỹ thuật ngoài giờ", "Description" => "Dịch vụ hỗ trợ ban đêm, cuối tuần, ngày lễ.", "Image" => "/TechFixPHP/assets/image/support.jpg"],
            ["Title" => "Dịch vụ VIP cho doanh nghiệp", "Description" => "Bảo trì ưu tiên, SLA nhanh cho khách hàng VIP.", "Image" => "/TechFixPHP/assets/image/vip.jpg"],
        ]
    ]
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dịch Vụ - TECHFIX</title>
    <link href="/TechFixPHP/assets/css/service.css" rel="stylesheet" />
</head>
<body>

    <div class="services-page">
        <header class="header">
            <h1>Dịch Vụ Của TECHFIX</h1>
            <p>Hơn 25 dịch vụ sửa chữa, lắp đặt & bảo trì chuyên nghiệp cho gia đình và doanh nghiệp.</p>
        </header>

        <main class="services-wrapper">
            <?php foreach ($serviceGroups as $group): ?>
                <section class="service-group">
                    <h2><?= htmlspecialchars($group['GroupName']) ?></h2>
                    <div class="services-container">
                        
                        <?php foreach ($group['Services'] as $service): ?>
                            <div class="service-card">
                                <img src="<?= htmlspecialchars($service['Image']) ?>" alt="<?= htmlspecialchars($service['Title']) ?>" />
                                <div class="content">
                                    <h3><?= htmlspecialchars($service['Title']) ?></h3>
                                    <p><?= htmlspecialchars($service['Description']) ?></p>
                                    <a href="book.php" class="btn">Đặt Dịch Vụ</a>
                                </div>
                            </div>
                        <?php endforeach; // Kết thúc vòng lặp 'service' ?>

                    </div>
                </section>
            <?php endforeach; // Kết thúc vòng lặp 'group' ?>
        </main>

        <footer class="footer">
            <p>© 2025 TECHFIX - HomeTech | All Rights Reserved</p>
        </footer>
    </div>

</body>
</html>