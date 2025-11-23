<?php
session_start();

// 1. KIỂM TRA QUYỀN (Chỉ Kỹ thuật viên mới cần vào đây)
// Nếu là Admin, tự động chuyển sang trang điều phối xịn hơn
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_dispatch.php");
    exit();
}

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'technical') {
    header("Location: /TechFixPHP/pages/public_page/login.php");
    exit();
}

// 2. KẾT NỐI DB
include '../../config/db.php';

$tech_id = $_SESSION['user']['id'];

// 3. LẤY DANH SÁCH CÔNG VIỆC CỦA THỢ
// Chỉ lấy đơn 'confirmed' (được giao) và 'processing' (đang làm)
$sql = "
    SELECT 
        b.*, 
        s.name AS service_name
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    WHERE 
        b.technician_id = ? 
        AND b.status IN ('confirmed', 'processing')
    ORDER BY b.appointment_time ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tech_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Lịch làm việc - TechFix Driver</title>
    
    <link href="/TechFixPHP/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; padding-bottom: 80px; }
        
        /* Header Mobile */
        .mobile-header {
            background: #0d6efd; color: white; padding: 15px;
            position: sticky; top: 0; z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex; justify-content: space-between; align-items: center;
        }
        .mobile-header h2 { margin: 0; font-size: 18px; font-weight: 700; }
        
        /* Card Job (Thẻ công việc) */
        .job-card {
            background: white; border-radius: 15px; border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 15px;
            overflow: hidden; transition: transform 0.2s;
        }
        .job-card:active { transform: scale(0.98); }
        
        .job-time {
            background: #e7f1ff; color: #0d6efd; padding: 8px 15px;
            font-weight: bold; font-size: 14px; display: flex; justify-content: space-between;
        }
        .job-body { padding: 15px; }
        
        .job-title { font-size: 16px; font-weight: 700; color: #333; margin-bottom: 8px; }
        
        .info-row { display: flex; align-items: flex-start; margin-bottom: 6px; font-size: 14px; color: #555; }
        .info-row i { width: 25px; margin-top: 3px; color: #999; }
        
        .job-actions {
            padding: 10px 15px; border-top: 1px solid #f0f0f0;
            display: flex; gap: 10px;
        }
        .btn-action { flex: 1; font-weight: 600; font-size: 14px; padding: 10px; border-radius: 8px; }
        
        /* Bottom Nav (Menu dưới cùng cho Mobile) */
        .bottom-nav {
            position: fixed; bottom: 0; left: 0; width: 100%; background: white;
            border-top: 1px solid #eee; display: flex; justify-content: space-around;
            padding: 10px 0; z-index: 1000;
        }
        .nav-item { text-align: center; color: #999; text-decoration: none; font-size: 12px; }
        .nav-item i { font-size: 20px; display: block; margin-bottom: 2px; }
        .nav-item.active { color: #0d6efd; }
    </style>
</head>
<body>

    <div class="mobile-header">
        <h2><i class="fa-solid fa-helmet-safety"></i> TechFix Partner</h2>
        <div class="user-info" style="font-size: 13px;">
            Xin chào, <b><?= htmlspecialchars($_SESSION['user']['name']) ?></b>
        </div>
    </div>

    <div class="container mt-3">
        <h6 class="text-muted text-uppercase mb-3 ms-1" style="font-size: 12px; font-weight: 700;">
            Công việc hôm nay (<?= $result->num_rows ?>)
        </h6>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="job-card">
                    <div class="job-time">
                        <span><i class="fa-regular fa-clock"></i> <?= date('H:i', strtotime($row['appointment_time'])) ?></span>
                        <span class="text-dark">#<?= $row['id'] ?></span>
                    </div>
                    
                    <div class="job-body">
                        <div class="job-title"><?= htmlspecialchars($row['service_name']) ?></div>
                        
                        <div class="info-row">
                            <i class="fa-solid fa-user"></i>
                            <span><?= htmlspecialchars($row['customer_name']) ?></span>
                        </div>
                        <div class="info-row">
                            <i class="fa-solid fa-phone"></i>
                            <a href="tel:<?= htmlspecialchars($row['phone']) ?>" class="text-decoration-none fw-bold text-primary">
                                <?= htmlspecialchars($row['phone']) ?>
                            </a>
                        </div>
                        <div class="info-row">
                            <i class="fa-solid fa-location-dot text-danger"></i>
                            <span><?= htmlspecialchars($row['address']) ?>, <?= htmlspecialchars($row['district']) ?></span>
                        </div>
                    </div>

                    <div class="job-actions">
                        <a href="admin_order_detail.php?id=<?= $row['id'] ?>" class="btn btn-light text-primary btn-action">
                            <i class="fa-solid fa-map-location-dot"></i> Chi tiết
                        </a>
                        
                        <form action="api_complete_job.php" method="POST" class="w-100" style="flex: 1;" onsubmit="return confirm('Bạn chắc chắn đã làm xong đơn này?')">
                            <input type="hidden" name="booking_id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn btn-success text-white btn-action w-100">
                                <i class="fa-solid fa-check"></i> Hoàn thành
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="fa-solid fa-mug-hot fa-3x mb-3 opacity-50"></i>
                <p>Tuyệt vời! Bạn đã hoàn thành hết công việc.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="bottom-nav">
        <a href="#" class="nav-item active">
            <i class="fa-solid fa-list-check"></i> Lịch làm
        </a>
        <a href="tech_history.php" class="nav-item">
            <i class="fa-solid fa-clock-rotate-left"></i> Lịch sử
        </a>
        <a href="/TechFixPHP/pages/public_page/logout.php" class="nav-item text-danger">
            <i class="fa-solid fa-right-from-bracket"></i> Thoát
        </a>
    </div>

    <script src="/TechFixPHP/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>