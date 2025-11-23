<?php
// File: /TechFixPHP/view_map.php
require_once 'config/db.php';

// 1. Lấy ID từ URL (Ví dụ: view_map.php?id=17)
$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($bookingId <= 0) {
    die("Mã đơn hàng không hợp lệ!");
}

// 2. Lấy thông tin đơn hàng theo ID
$stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    die("Không tìm thấy đơn hàng!");
}

// 3. Kiểm tra tọa độ
$hasLocation = !empty($booking['lat']) && !empty($booking['lng']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechFix Map - Theo dõi đơn hàng #<?= $booking['id'] ?></title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { color: #0d6efd; margin-top: 0; }
        .info-box { background: #e7f1ff; padding: 10px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #0d6efd; }
        #map { height: 500px; width: 100%; border-radius: 10px; border: 2px solid #ddd; }
        .badge { background: #28a745; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h2>📍 Lộ Trình Di Chuyển - Đơn #<?= $booking['id'] ?></h2>
    
    <div class="info-box">
        <p><strong>Khách hàng:</strong> <?= htmlspecialchars($booking['customer_name']) ?></p>
        <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($booking['address']) ?>, <?= htmlspecialchars($booking['district']) ?></p>
        
        <?php if ($hasLocation): ?>
            <p><strong>Tọa độ:</strong> <?= $booking['lat'] ?>, <?= $booking['lng'] ?> <span class="badge">Đã định vị</span></p>
        <?php else: ?>
            <p class="error">⚠️ Đơn này chưa có tọa độ (Do đặt trước khi update code hoặc không tìm thấy địa chỉ).</p>
        <?php endif; ?>
    </div>

    <div id="map"></div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Cấu hình tọa độ
        // Tọa độ SHOP (Ví dụ: ĐH Sư Phạm Kỹ Thuật Vĩnh Long) -> Bạn thay đổi theo ý muốn
        const shopLat = 10.254227; 
        const shopLng = 105.972428;

        // Tọa độ KHÁCH (Lấy từ PHP)
        <?php if ($hasLocation): ?>
            const customerLat = <?= $booking['lat'] ?>;
            const customerLng = <?= $booking['lng'] ?>;
        <?php else: ?>
            // Nếu không có tọa độ, mặc định để tránh lỗi JS (Ẩn bản đồ hoặc báo lỗi)
            document.getElementById('map').innerHTML = '<h3 style="text-align:center; padding-top: 200px; color: gray;">Không thể hiển thị bản đồ</h3>';
            return; 
        <?php endif; ?>

        // 2. Khởi tạo bản đồ (Căn giữa ở vị trí khách)
        var map = L.map('map').setView([customerLat, customerLng], 14);

        // 3. Load lớp nền bản đồ (OpenStreetMap)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© TechFix Map'
        }).addTo(map);

        // 4. Tạo Icon "Cửa hàng" (Cờ lê / TechFix)
        var shopIcon = L.icon({
            iconUrl: 'https://cdn-icons-png.flaticon.com/512/10613/10613919.png', // Icon Shop
            iconSize: [45, 45],
            iconAnchor: [22, 45],
            popupAnchor: [0, -40]
        });

        // 5. Tạo Icon "Khách hàng" (Ngôi nhà)
        var userIcon = L.icon({
            iconUrl: 'https://cdn-icons-png.flaticon.com/512/619/619153.png', // Icon Nhà
            iconSize: [40, 40],
            iconAnchor: [20, 40],
            popupAnchor: [0, -40]
        });

        // 6. Đặt Marker lên bản đồ
        var markerShop = L.marker([shopLat, shopLng], {icon: shopIcon}).addTo(map)
            .bindPopup("<b>🏢 Trụ sở TechFix</b><br>Kỹ thuật viên xuất phát tại đây.");

        var markerUser = L.marker([customerLat, customerLng], {icon: userIcon}).addTo(map)
            .bindPopup("<b>🏠 Nhà khách hàng</b><br><?= htmlspecialchars($booking['customer_name']) ?>")
            .openPopup(); // Tự động mở popup nhà khách

        // 7. Vẽ đường nối (Style Grab - Nét đứt)
        var latlngs = [
            [shopLat, shopLng],
            [customerLat, customerLng]
        ];

        var polyline = L.polyline(latlngs, {
            color: '#0d6efd', // Màu xanh TechFix
            weight: 5,
            opacity: 0.7,
            dashArray: '10, 10', // Tạo nét đứt
            lineJoin: 'round'
        }).addTo(map);

        // 8. Tự động Zoom để thấy cả 2 điểm
        map.fitBounds(polyline.getBounds(), {padding: [50, 50]});
    });
</script>

</body>
</html>