<?php
session_start();
require_once '../../config/db.php';

// Check quyền
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'technical'])) {
    die("Access Denied");
}

$cus_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

// Lấy thông tin khách
$cus = $conn->query("SELECT name, phone, address FROM users WHERE id = $cus_id")->fetch_assoc();

if (!$cus) die("Không tìm thấy khách hàng.");

// Lấy lịch sử đơn hàng
$sql = "
    SELECT b.*, s.name as service_name, u.name as tech_name
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    LEFT JOIN users u ON b.technician_id = u.id
    WHERE b.customer_id = ?
    ORDER BY b.appointment_time DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cus_id);
$stmt->execute();
$history = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử khách hàng</title>
    <link href="/TechFixPHP/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .history-container { max-width: 600px; margin: 0 auto; padding: 20px; }
        
        /* Card Profile */
        .profile-card {
            background: white; border-radius: 15px; padding: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 25px; text-align: center;
        }
        .avatar {
            width: 60px; height: 60px; background: #0d6efd; color: white;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 24px; font-weight: bold; margin: 0 auto 10px;
        }
        
        /* Timeline Style */
        .timeline { position: relative; border-left: 2px solid #e9ecef; margin-left: 20px; padding-left: 20px; }
        .timeline-item { position: relative; margin-bottom: 30px; }
        
        .timeline-dot {
            position: absolute; left: -26px; top: 5px;
            width: 14px; height: 14px; border-radius: 50%;
            background: #fff; border: 3px solid #0d6efd;
        }
        .timeline-dot.completed { border-color: #198754; background: #198754; }
        .timeline-dot.cancelled { border-color: #dc3545; background: #dc3545; }
        
        .timeline-content {
            background: white; padding: 15px; border-radius: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        }
        .time-label { font-size: 0.85rem; color: #888; margin-bottom: 5px; }
        .service-title { font-weight: 600; color: #333; margin-bottom: 5px; font-size: 1rem; }
        .tech-name { font-size: 0.85rem; color: #666; }
        .price-tag { font-weight: bold; color: #0d6efd; float: right; }
    </style>
</head>
<body>

<div class="history-container">
    
    <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="fa-solid fa-arrow-left"></i> Quay lại
    </a>

    <div class="profile-card">
        <div class="avatar"><?= strtoupper(substr($cus['name'], 0, 1)) ?></div>
        <h5><?= htmlspecialchars($cus['name']) ?></h5>
        <p class="text-muted mb-1"><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($cus['phone']) ?></p>
        <p class="small text-muted"><i class="fa-solid fa-map-marker-alt"></i> <?= htmlspecialchars($cus['address']) ?></p>
    </div>

    <h6 class="text-uppercase text-muted ms-2 mb-3 fw-bold">Lịch sử sửa chữa</h6>

    <div class="timeline">
        <?php if ($history->num_rows > 0): ?>
            <?php while ($row = $history->fetch_assoc()): ?>
                <?php 
                    $statusClass = '';
                    if($row['status'] == 'completed') $statusClass = 'completed';
                    if($row['status'] == 'cancelled') $statusClass = 'cancelled';
                ?>
                <div class="timeline-item">
                    <div class="timeline-dot <?= $statusClass ?>"></div>
                    <div class="timeline-content">
                        <div class="time-label">
                            <?= date('d/m/Y - H:i', strtotime($row['appointment_time'])) ?>
                            <span class="badge bg-secondary float-end" style="font-size:0.7rem">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </div>
                        <div class="service-title">
                            <?= htmlspecialchars($row['service_name']) ?>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-end mt-2">
                            <div>
                                <div class="tech-name"><i class="fa-solid fa-wrench"></i> <?= $row['tech_name'] ?? 'Chưa gán' ?></div>
                                <?php if(!empty($row['note'])): ?>
                                    <small class="text-danger">"<?= htmlspecialchars($row['note']) ?>"</small>
                                <?php endif; ?>
                            </div>
                            <div class="price-tag">
                                <?= number_format($row['final_price']) ?>đ
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-muted ms-3">Khách hàng này chưa có lịch sử đơn hàng nào.</p>
        <?php endif; ?>
    </div>

</div>

</body>
</html>