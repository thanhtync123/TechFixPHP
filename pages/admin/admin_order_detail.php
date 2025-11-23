<?php
session_start();

// 1. KIỂM TRA QUYỀN (Chỉ Admin & Tech)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'technical'])) {
    header('Location: /TechFixPHP/pages/public_page/login.php');
    exit;
}

include '../../config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$role = $_SESSION['role'];

// --- XỬ LÝ: CẬP NHẬT TRẠNG THÁI & GÁN THỢ (Chỉ Admin) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'admin') {
    if (isset($_POST['update_status'])) {
        $status = $_POST['status'];
        $tech_id = !empty($_POST['technician_id']) ? intval($_POST['technician_id']) : null; // Cho phép null nếu chưa gán

        $stmt = $conn->prepare("UPDATE bookings SET status = ?, technician_id = ? WHERE id = ?");
        $stmt->bind_param("sii", $status, $tech_id, $id);
        
        if ($stmt->execute()) {
            $msg = "Cập nhật đơn hàng thành công!";
            $msg_type = "success";
        } else {
            $msg = "Lỗi: " . $conn->error;
            $msg_type = "danger";
        }
    }
}

// --- LẤY DỮ LIỆU CHI TIẾT ---
$sql = "
    SELECT 
        b.*, 
        s.name as service_name, s.image as service_img,
        c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
        t.name as tech_name
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    LEFT JOIN users c ON b.customer_id = c.id
    LEFT JOIN users t ON b.technician_id = t.id
    WHERE b.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) die("Không tìm thấy đơn hàng.");

// Lấy danh sách thợ để Admin chọn (Dropdown)
$techs = [];
if ($role === 'admin') {
    $tech_res = $conn->query("SELECT id, name FROM users WHERE role = 'technical'");
    while ($t = $tech_res->fetch_assoc()) {
        $techs[] = $t;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn #<?= $id ?> - Admin</title>
    <link href="/TechFixPHP/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        body { background-color: #f4f6f9; }
        .sidebar { min-height: 100vh; background: #343a40; color: white; }
        .sidebar a { color: rgba(255,255,255,.8); text-decoration: none; display: block; padding: 12px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar a:hover { background: #0d6efd; }
        
        /* Card Style */
        .card-box { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .section-title { font-weight: 700; color: #495057; margin-bottom: 15px; border-bottom: 2px solid #f1f1f1; padding-bottom: 10px; }
        
        /* Status Badge */
        .badge-lg { font-size: 1rem; padding: 8px 15px; }
        
        /* Map */
        #map { height: 250px; width: 100%; border-radius: 8px; margin-top: 10px; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 sidebar p-0 collapse d-md-block">
            <?php include __DIR__ . '/template/sidebar.php'; ?>
        </div>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="orders.php" class="btn btn-outline-secondary btn-sm mb-2">⬅ Quay lại danh sách</a>
                    <h2 class="fw-bold">Chi tiết đơn hàng #<?= $id ?></h2>
                </div>
                <div>
                    <?php if($order['status'] == 'completed'): ?>
                        <a href="invoice_order.php?id=<?= $id ?>" target="_blank" class="btn btn-success"><i class="fa-solid fa-print"></i> In Hóa Đơn</a>
                    <?php endif; ?>
                    
                    <?php if($role === 'technical' && $order['status'] == 'confirmed'): ?>
                        <a href="/TechFixPHP/pages/technical/signature.php?id=<?= $id ?>" class="btn btn-warning"><i class="fa-solid fa-signature"></i> Nghiệm thu</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($msg)): ?>
                <div class="alert alert-<?= $msg_type ?>"><?= $msg ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    
                    <div class="card-box">
                        <h5 class="section-title"><i class="fa-solid fa-box-open"></i> Thông tin dịch vụ</h5>
                        <div class="d-flex align-items-center">
                            <img src="<?= $order['service_img'] ?? '/TechFixPHP/assets/image/default.jpg' ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; margin-right: 20px;">
                            <div>
                                <h4 class="mb-1 text-primary"><?= htmlspecialchars($order['service_name']) ?></h4>
                                <p class="mb-0 text-muted">Thời gian hẹn: <strong><?= date('d/m/Y H:i', strtotime($order['appointment_time'])) ?></strong></p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-6">
                                <span class="text-muted">Giá dịch vụ:</span><br>
                                <span class="fs-5 fw-bold text-danger"><?= number_format($order['final_price'], 0, ',', '.') ?> đ</span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted">Trạng thái hiện tại:</span><br>
                                <?php 
                                    $stt = $order['status'];
                                    $color = ($stt=='completed')?'success':(($stt=='confirmed')?'primary':(($stt=='pending')?'warning':'danger'));
                                ?>
                                <span class="badge bg-<?= $color ?> badge-lg"><?= ucfirst($stt) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="card-box">
                        <h5 class="section-title"><i class="fa-solid fa-map-location-dot"></i> Địa điểm làm việc</h5>
                        <p class="mb-1"><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['district']) ?></p>
                        <p class="text-muted fst-italic">Ghi chú: <?= htmlspecialchars($order['note'] ?? 'Không có') ?></p>
                        
                        <?php if (!empty($order['lat']) && !empty($order['lng'])): ?>
                            <div id="map"></div>
                        <?php else: ?>
                            <div class="alert alert-warning mt-2">Khách hàng không cung cấp tọa độ GPS.</div>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="col-md-4">
                    
                    <?php if($role === 'admin'): ?>
                    <div class="card-box bg-light border">
                        <h5 class="section-title text-dark"><i class="fa-solid fa-gear"></i> Điều phối đơn hàng</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Trạng thái đơn</label>
                                <select name="status" class="form-select">
                                    <option value="pending" <?= $stt=='pending'?'selected':'' ?>>Đang chờ (Pending)</option>
                                    <option value="confirmed" <?= $stt=='confirmed'?'selected':'' ?>>Đã xác nhận (Confirmed)</option>
                                    <option value="completed" <?= $stt=='completed'?'selected':'' ?>>Hoàn thành (Completed)</option>
                                    <option value="cancelled" <?= $stt=='cancelled'?'selected':'' ?>>Hủy đơn (Cancelled)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Gán Kỹ thuật viên</label>
                                <select name="technician_id" class="form-select">
                                    <option value="">-- Chọn thợ --</option>
                                    <?php foreach ($techs as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= ($order['technician_id'] == $t['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['name']) ?> (ID: <?= $t['id'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" name="update_status" class="btn btn-primary w-100 fw-bold">
                                <i class="fa-solid fa-floppy-disk"></i> Lưu Thay Đổi
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <div class="card-box">
                        <h5 class="section-title"><i class="fa-solid fa-user"></i> Khách hàng</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2"><strong>Tên:</strong> <?= htmlspecialchars($order['customer_name']) ?></li>
                            <li class="mb-2">
                                <strong>SĐT:</strong> 
                                <a href="tel:<?= $order['customer_phone'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($order['customer_phone']) ?>
                                </a>
                            </li>
                            <li class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($order['customer_email'] ?? 'Chưa cập nhật') ?></li>
                        </ul>
                        <a href="mailto:<?= $order['customer_email'] ?>" class="btn btn-outline-primary btn-sm w-100">
                            <i class="fa-solid fa-envelope"></i> Gửi Email
                        </a>
                    </div>

                    <?php if($role === 'technical'): ?>
                        <div class="card-box bg-info bg-opacity-10 border border-info">
                            <h6 class="text-info fw-bold"><i class="fa-solid fa-user-gear"></i> Kỹ thuật viên</h6>
                            <p class="mb-0">Bạn đang phụ trách đơn này.</p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    <?php if (!empty($order['lat']) && !empty($order['lng'])): ?>
        var map = L.map('map').setView([<?= $order['lat'] ?>, <?= $order['lng'] ?>], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: 'TechFix Map'
        }).addTo(map);
        L.marker([<?= $order['lat'] ?>, <?= $order['lng'] ?>]).addTo(map)
            .bindPopup('<b>Nhà khách hàng</b><br><?= htmlspecialchars($order['address']) ?>')
            .openPopup();
    <?php endif; ?>
</script>

<script src="/TechFixPHP/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>