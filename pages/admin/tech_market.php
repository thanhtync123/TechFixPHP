<?php
session_start();

// 1. KIỂM TRA QUYỀN
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'technical') {
    header("Location: /TechFixPHP/pages/public_page/login.php");
    exit();
}

include '../../config/db.php';
$tech_id = $_SESSION['user']['id'];

// ====================================================
// 2. XỬ LÝ KHI BẤM "NHẬN VIỆC"
// ====================================================
$alert_script = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grab_job_id'])) {
    $booking_id = intval($_POST['grab_job_id']);

    // Kiểm tra đơn còn trống không
    $check = $conn->query("SELECT id FROM bookings WHERE id = $booking_id AND (technician_id IS NULL OR technician_id = 0) AND status = 'pending'");
    
    if ($check->num_rows > 0) {
        // Chốt đơn
        $stmt = $conn->prepare("UPDATE bookings SET technician_id = ?, status = 'confirmed' WHERE id = ?");
        $stmt->bind_param("ii", $tech_id, $booking_id);
        
        if ($stmt->execute()) {
            $alert_script = "
                Swal.fire({
                    icon: 'success',
                    title: 'Nhận việc thành công!',
                    text: 'Đơn hàng #$booking_id đã được chuyển vào Lịch của tôi.',
                    confirmButtonText: 'Xem lịch ngay',
                    confirmButtonColor: '#0d6efd'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'tech_schedule.php';
                    }
                });
            ";
            
            // (Optional) Gửi mail thông báo cho thợ và admin tại đây nếu cần
        }
    } else {
        $alert_script = "
            Swal.fire({
                icon: 'error',
                title: 'Chậm chân rồi!',
                text: 'Đơn hàng này vừa có người khác nhận trước bạn.',
                confirmButtonColor: '#d33'
            });
        ";
    }
}

// ====================================================
// 3. LẤY DANH SÁCH ĐƠN CHỜ (SÀN VIỆC)
// ====================================================
$sql = "
    SELECT 
        b.*, 
        s.name AS service_name, s.image AS service_image
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    WHERE b.status = 'pending' AND (b.technician_id IS NULL OR b.technician_id = 0)
    ORDER BY b.created_at DESC
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Săn Việc - TechFix</title>
    
    <link href="/TechFixPHP/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; padding-bottom: 20px; }
        
        /* Header Gradient */
        .market-header {
            background: linear-gradient(135deg, #ff6b6b, #ee5253);
            color: white; padding: 15px;
            border-bottom-left-radius: 25px; border-bottom-right-radius: 25px;
            box-shadow: 0 4px 15px rgba(238, 82, 83, 0.3);
            position: sticky; top: 0; z-index: 1000;
            display: flex; align-items: center; justify-content: space-between;
        }
        
        .btn-back {
            color: white; font-size: 1.2rem; text-decoration: none;
            background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 10px;
            transition: 0.2s;
        }
        .btn-back:hover { background: rgba(255,255,255,0.4); }

        /* Card Job Style */
        .job-card {
            background: white; border-radius: 16px; border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 15px;
            position: relative; overflow: hidden; transition: transform 0.2s;
        }
        .job-card:active { transform: scale(0.98); }
        
        .job-card::before {
            content: ''; position: absolute; top: 0; left: 0; bottom: 0; width: 5px;
            background: #ff6b6b;
        }

        .card-content { padding: 15px 15px 15px 20px; }
        
        .price-tag {
            position: absolute; top: 15px; right: 15px;
            background: #fff0f0; color: #d63031;
            padding: 5px 12px; border-radius: 20px;
            font-weight: 800; font-size: 14px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .job-title { font-size: 16px; font-weight: 700; color: #2d3436; margin-bottom: 5px; width: 75%; }
        .job-meta { font-size: 13px; color: #636e72; margin-bottom: 10px; display: flex; gap: 10px; align-items: center; }
        
        .job-location {
            background: #f1f2f6; padding: 8px; border-radius: 8px;
            font-size: 13px; color: #2d3436; display: flex; align-items: flex-start; gap: 8px;
        }

        .btn-grab {
            width: 100%; margin-top: 15px; padding: 12px;
            background: linear-gradient(to right, #ff6b6b, #ee5253);
            border: none; border-radius: 10px;
            color: white; font-weight: 700; text-transform: uppercase;
            box-shadow: 0 4px 10px rgba(238, 82, 83, 0.3);
            transition: 0.3s;
        }
        .btn-grab:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(238, 82, 83, 0.4); }
    </style>
</head>
<body>

    <div class="market-header">
        <a href="tech_schedule.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i></a>
        <h5 class="m-0 fw-bold flex-grow-1 text-center" style="padding-right: 40px;">SĂN VIỆC NGAY</h5>
    </div>

    <div class="container mt-3">
        
        <?php if ($result->num_rows > 0): ?>
            <div class="d-flex justify-content-between align-items-center mb-3 px-1">
                <span class="text-muted small fw-bold">ĐANG CÓ <?= $result->num_rows ?> ĐƠN HÀNG</span>
                <button onclick="location.reload()" class="btn btn-sm btn-light text-primary rounded-pill"><i class="fa-solid fa-rotate"></i> Làm mới</button>
            </div>

            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="job-card animate__animated animate__fadeInUp">
                    <div class="card-content">
                        <span class="price-tag"><?= number_format($row['final_price']) ?> đ</span>
                        
                        <div class="job-title text-truncate"><?= htmlspecialchars($row['service_name']) ?></div>
                        
                        <div class="job-meta">
                            <span><i class="fa-regular fa-clock"></i> <?= date('H:i - d/m', strtotime($row['appointment_time'])) ?></span>
                            <span class="badge bg-light text-dark border">Đơn mới</span>
                        </div>

                        <div class="job-location">
                            <i class="fa-solid fa-map-location-dot text-danger mt-1"></i>
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($row['district']) ?></div>
                                <div class="small text-muted text-truncate" style="max-width: 250px;">
                                    <?= htmlspecialchars($row['address']) ?>
                                </div>
                            </div>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="grab_job_id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn-grab">
                                <i class="fa-solid fa-hand-point-up"></i> NHẬN VIỆC NÀY
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>

        <?php else: ?>
            <div class="text-center py-5 mt-4">
                <div class="mb-3">
                    <i class="fa-solid fa-box-open fa-4x text-secondary opacity-25"></i>
                </div>
                <h5 class="text-muted">Hết đơn hàng rồi!</h5>
                <p class="text-muted small px-4">Hiện tại chưa có khách đặt đơn mới. Hãy quay lại sau hoặc kiểm tra lịch của bạn.</p>
                <a href="tech_schedule.php" class="btn btn-outline-primary rounded-pill mt-2">Quay lại Lịch làm</a>
            </div>
        <?php endif; ?>

    </div>

    <script src="/TechFixPHP/assets/js/bootstrap.bundle.min.js"></script>
    
    <script>
        <?= $alert_script ?>
    </script>

</body>
</html>