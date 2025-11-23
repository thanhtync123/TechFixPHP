<?php
session_start();
require_once '../config/db.php';

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'customer') {
    header("Location: /TechFixPHP/pages/public_page/login.php");
    exit();
}

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$customer_id = $_SESSION['user']['id'];

// 2. LẤY THÔNG TIN CHI TIẾT
// Lấy thêm cột payment_status (nếu có) để check kỹ hơn
$query = "
    SELECT 
        b.*, 
        s.name AS service_name, 
        s.image AS service_image,
        s.price as base_price,
        u.name AS tech_name, u.phone AS tech_phone
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    LEFT JOIN users u ON b.technician_id = u.id
    WHERE b.id = ? AND b.customer_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    die("<div style='text-align:center; padding:50px;'><h3>Không tìm thấy đơn hàng!</h3><a href='my_booking.php'>Quay lại</a></div>");
}

// Logic kiểm tra đã thanh toán chưa
// Nếu có cột payment_status = 'paid' HOẶC status != 'pending' (tức là đã confirm/completed) thì coi như đã xử lý xong phần thanh toán ban đầu
$is_paid = (isset($booking['payment_status']) && $booking['payment_status'] == 'paid') || ($booking['status'] != 'pending' && $booking['status'] != 'cancelled');

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theo dõi đơn hàng #<?= $booking['id'] ?> - TECHFIX</title>
    
    <link href="/TechFixPHP/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; padding-bottom: 60px; }
        
        /* --- SỬA LỖI CĂN GIỮA --- */
        .container { 
            max-width: 1000px; 
            margin: 30px auto; /* 'auto' sẽ căn giữa trái phải */
            padding: 0 15px;
        }
        
        /* Status Banner */
        .status-banner {
            border-radius: 16px; padding: 30px; color: white; margin-bottom: 25px; position: relative; overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex; justify-content: space-between; align-items: center;
        }
        .status-pending { background: linear-gradient(135deg, #ff9f43, #ee5253); }
        .status-confirmed { background: linear-gradient(135deg, #0984e3, #74b9ff); }
        .status-completed { background: linear-gradient(135deg, #00b894, #55efc4); }
        .status-cancelled { background: linear-gradient(135deg, #636e72, #b2bec3); }
        
        .status-icon { font-size: 3rem; opacity: 0.3; position: absolute; right: 20px; top: 50%; transform: translateY(-50%); }

        /* Cards */
        .custom-card {
            background: white; border-radius: 16px; border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 20px; overflow: hidden;
        }
        .card-head { padding: 15px 20px; border-bottom: 1px solid #f1f1f1; font-weight: 700; color: #2d3436; }
        .card-body { padding: 20px; }

        /* Timeline Vertical */
        .timeline { border-left: 2px solid #e9ecef; margin-left: 10px; padding-left: 25px; position: relative; }
        .timeline-item { position: relative; margin-bottom: 25px; }
        .timeline-dot {
            width: 14px; height: 14px; background: #e9ecef; border-radius: 50%;
            position: absolute; left: -33px; top: 5px; border: 2px solid white; box-shadow: 0 0 0 2px #e9ecef;
        }
        .timeline-item.active .timeline-dot { background: #0984e3; box-shadow: 0 0 0 3px #74b9ff; }
        .timeline-item.active .time-text { color: #0984e3; font-weight: bold; }
        .time-text { font-size: 0.85rem; color: #636e72; margin-bottom: 2px; display: block; }
        .timeline-title { font-weight: 600; font-size: 1rem; color: #2d3436; }

        /* Receipt Style (Cột phải) */
        .receipt-box {
            background: #fff; padding: 20px; border-radius: 16px;
            border: 1px dashed #b2bec3; position: relative;
        }
        .receipt-box::before, .receipt-box::after {
            content: ''; position: absolute; bottom: -10px; width: 20px; height: 20px;
            background: #f0f2f5; border-radius: 50%;
        }
        .receipt-box::before { left: -10px; }
        .receipt-box::after { right: -10px; }
        
        .price-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.95rem; color: #636e72; }
        .price-total { border-top: 2px dashed #eee; padding-top: 15px; margin-top: 15px; font-size: 1.2rem; font-weight: 800; color: #d63031; display: flex; justify-content: space-between; }

        /* Map */
        #map { height: 200px; width: 100%; border-radius: 12px; }
    </style>
</head>
<body>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="my_booking.php" class="btn btn-light rounded-pill fw-bold"><i class="fa-solid fa-chevron-left"></i> Quay lại</a>
        <span class="text-muted small">Mã đơn: #<?= $booking['id'] ?></span>
    </div>

    <?php 
        $stt = $booking['status'];
        $cls = 'status-pending'; $txt = 'Đang chờ xử lý';
        $icon = 'fa-clock';
        
        if($stt == 'confirmed') { $cls = 'status-confirmed'; $txt = 'Kỹ thuật viên đang đến'; $icon = 'fa-truck-fast'; }
        if($stt == 'completed') { $cls = 'status-completed'; $txt = 'Hoàn thành công việc'; $icon = 'fa-check-circle'; }
        if($stt == 'cancelled') { $cls = 'status-cancelled'; $txt = 'Đơn hàng đã hủy'; $icon = 'fa-circle-xmark'; }
    ?>
    <div class="status-banner <?= $cls ?>">
        <div>
            <h2 class="m-0 fw-bold"><?= $txt ?></h2>
            <p class="m-0 opacity-75">Cập nhật lúc: <?= date('H:i - d/m/Y', strtotime($booking['created_at'])) ?></p>
        </div>
        <i class="fa-solid <?= $icon ?> status-icon"></i>
    </div>

    <div class="row">
        <div class="col-lg-8">
            
            <div class="custom-card">
                <div class="card-body d-flex gap-3">
                    <img src="<?= $booking['service_image'] ?? '/TechFixPHP/assets/image/default.jpg' ?>" style="width: 80px; height: 80px; border-radius: 10px; object-fit: cover;">
                    <div>
                        <h5 class="fw-bold text-dark"><?= $booking['service_name'] ?></h5>
                        <div class="text-muted small mb-2"><i class="fa-solid fa-location-dot"></i> <?= $booking['address'] ?>, <?= $booking['district'] ?></div>
                        <div class="badge bg-light text-dark border"><i class="fa-regular fa-calendar"></i> Ngày hẹn: <?= date('d/m/Y - H:i', strtotime($booking['appointment_time'])) ?></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="custom-card h-100">
                        <div class="card-head"><i class="fa-solid fa-list-ol"></i> Tiến độ</div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item <?= ($stt != 'cancelled') ? 'active' : '' ?>">
                                    <div class="timeline-dot"></div>
                                    <span class="time-text"><?= date('H:i d/m', strtotime($booking['created_at'])) ?></span>
                                    <div class="timeline-title">Đặt lịch thành công</div>
                                </div>
                                <div class="timeline-item <?= ($stt == 'confirmed' || $stt == 'completed') ? 'active' : '' ?>">
                                    <div class="timeline-dot"></div>
                                    <span class="time-text"><?= ($stt == 'confirmed' || $stt == 'completed') ? 'Đã thanh toán/xác nhận' : '---' ?></span>
                                    <div class="timeline-title">Kỹ thuật viên tiếp nhận</div>
                                </div>
                                <div class="timeline-item <?= ($stt == 'completed') ? 'active' : '' ?>">
                                    <div class="timeline-dot"></div>
                                    <span class="time-text"><?= ($stt == 'completed') ? 'Hoàn tất' : '---' ?></span>
                                    <div class="timeline-title">Hoàn thành & Nghiệm thu</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="custom-card h-100">
                        <div class="card-head"><i class="fa-solid fa-map-location-dot"></i> Vị trí</div>
                        <div class="card-body p-2">
                            <?php if(!empty($booking['lat'])): ?>
                                <div id="map"></div>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted bg-light rounded">
                                    <i class="fa-solid fa-map-pin mb-2"></i><br>Chưa có dữ liệu bản đồ
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            
            <div class="receipt-box mb-4">
                <h5 class="fw-bold text-center mb-3 text-uppercase">Chi tiết thanh toán</h5>
                <div class="price-row">
                    <span>Giá dịch vụ:</span>
                    <span><?= number_format($booking['base_price']) ?> đ</span>
                </div>
                <?php if($booking['final_price'] > $booking['base_price']): ?>
                <div class="price-row">
                    <span>Vật tư/Phát sinh:</span>
                    <span><?= number_format($booking['final_price'] - $booking['base_price']) ?> đ</span>
                </div>
                <?php endif; ?>
                
                <div class="price-total">
                    <span>TỔNG CỘNG</span>
                    <span><?= number_format($booking['final_price']) ?> đ</span>
                </div>

                <div class="mt-4 d-grid gap-2">
                    
                    <?php if ($stt == 'cancelled'): ?>
                        <button class="btn btn-secondary" disabled>Đơn hàng đã bị hủy</button>

                    <?php elseif ($is_paid): ?>
                        <div class="alert alert-success text-center mb-0 py-2">
                            <i class="fa-solid fa-check-circle"></i> Đã thanh toán
                        </div>
                        
                        <?php if ($stt == 'completed'): ?>
                             <a href="/TechFixPHP/Customer/export_invoice.php?id=<?= $booking['id'] ?>" target="_blank" class="btn btn-success fw-bold">
                                 <i class="fa-solid fa-file-invoice"></i> Tải Hóa Đơn
                             </a>
                        <?php endif; ?>

                    <?php else: ?>
                        <button onclick="processPayment(<?= $booking['id'] ?>)" class="btn btn-warning fw-bold text-dark py-2 shadow-sm">
                            <i class="fa-regular fa-credit-card"></i> THANH TOÁN NGAY
                        </button>
                        <div class="text-center small text-muted">Hỗ trợ: MoMo, VNPAY, Banking</div>

                        <a href="cancel_booking.php?id=<?= $booking['id'] ?>" onclick="return confirm('Bạn muốn hủy đơn này?')" class="btn btn-outline-danger btn-sm mt-2">
                            Hủy Đơn Hàng
                        </a>
                    <?php endif; ?>

                </div>
            </div>

            <?php if ($booking['tech_name']): ?>
            <div class="custom-card">
                <div class="card-head">Kỹ thuật viên phụ trách</div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-light rounded-circle p-3 me-3"><i class="fa-solid fa-user-gear fa-lg text-primary"></i></div>
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($booking['tech_name']) ?></div>
                            <a href="tel:<?= $booking['tech_phone'] ?>" class="text-decoration-none small text-primary"><?= $booking['tech_phone'] ?></a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="/TechFixPHP/assets/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // 1. Map (Nếu có)
    <?php if(!empty($booking['lat']) && !empty($booking['lng'])): ?>
        var map = L.map('map').setView([<?= $booking['lat'] ?>, <?= $booking['lng'] ?>], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: 'TechFix' }).addTo(map);
        L.marker([<?= $booking['lat'] ?>, <?= $booking['lng'] ?>]).addTo(map).bindPopup('Vị trí làm việc').openPopup();
    <?php endif; ?>

    // 2. Giả lập Thanh toán & Gửi mail
    function processPayment(id) {
        Swal.fire({
            title: 'Cổng thanh toán TechFix',
            html: '<p>Đang kết nối đến ngân hàng...</p><div class="spinner-border text-primary"></div>',
            showConfirmButton: false,
            timer: 2000
        }).then(() => {
            // Gọi API giả lập
            fetch('/TechFixPHP/pages/api/simulate_payment.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ booking_id: id })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Thanh toán thành công!',
                        text: 'Hóa đơn đã được gửi về email của bạn.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload(); // Load lại trang để mất nút thanh toán
                    });
                } else {
                    Swal.fire('Lỗi', data.message, 'error');
                }
            });
        });
    }
</script>

</body>
</html>