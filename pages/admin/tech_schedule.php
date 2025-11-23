<?php
session_start();

// 1. KIỂM TRA QUYỀN (Chỉ Kỹ thuật viên)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'technical') {
    header('Location: /TechFixPHP/pages/public_page/login.php');
    exit;
}

// 2. KẾT NỐI DB
include '../../config/db.php';

$tech_id = $_SESSION['user']['id'];

// 3. LẤY DANH SÁCH VIỆC CẦN LÀM
// Lấy thêm customer_id để làm link xem lịch sử
$sql = "
    SELECT 
        b.*, 
        s.name AS service_name
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    WHERE 
        b.technician_id = ? 
        AND b.status = 'confirmed'
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch làm việc - TechFix</title>
    
    <link href="/TechFixPHP/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; padding-bottom: 60px; }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0 !important; padding: 15px; }
        }

        /* Card Job Styles */
        .job-card {
            background: white; border-radius: 16px; border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px;
            overflow: hidden; transition: transform 0.2s;
        }
        
        .job-header {
            background: linear-gradient(45deg, #0d6efd, #0a58ca); 
            color: white; padding: 15px;
            display: flex; justify-content: space-between; align-items: center;
        }
        
        .job-body { padding: 20px; }
        
        .info-row { margin-bottom: 12px; display: flex; align-items: flex-start; font-size: 15px; }
        .info-row i { width: 30px; color: #6c757d; margin-top: 3px; font-size: 1.1rem; }
        .info-row span { font-weight: 500; color: #333; word-break: break-word; }
        
        /* Nút bấm Action */
        .job-footer {
            padding: 15px; background: #f8f9fa; border-top: 1px solid #eee;
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
        }
        
        .btn-action { 
            border-radius: 10px; font-weight: 600; font-size: 14px; padding: 12px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        
        .btn-full { grid-column: span 2; } /* Nút dài hết khổ */
        
        /* Class khóa nút */
        .disabled-link { 
            pointer-events: none; 
            opacity: 0.5; 
            background-color: #6c757d !important; 
            border-color: #6c757d !important; 
            cursor: not-allowed;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        
        <div class="col-md-3 col-lg-2 sidebar p-0 collapse d-md-block">
            <?php include __DIR__ . '/../admin/template/sidebar.php'; ?>
        </div>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 main-content">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-clipboard-list text-primary"></i> Công Việc Hôm Nay</h4>
                <div class="badge bg-white text-dark border p-2 shadow-sm">
                    Tech: <b><?= htmlspecialchars($_SESSION['user']['name']) ?></b>
                </div>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <div class="row">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="job-card" id="card-<?= $row['id'] ?>">
                                
                                <div class="job-header">
                                    <span class="fw-bold"><i class="fa-solid fa-hashtag"></i> <?= $row['id'] ?></span>
                                    <span class="badge bg-white text-primary fw-bold">
                                        <i class="fa-regular fa-clock"></i> <?= date('H:i', strtotime($row['appointment_time'])) ?>
                                    </span>
                                </div>

                                <div class="job-body">
                                    <h5 class="text-primary fw-bold mb-3"><?= htmlspecialchars($row['service_name']) ?></h5>
                                    
                                    <div class="info-row">
                                        <i class="fa-solid fa-user"></i>
                                        <span><?= htmlspecialchars($row['customer_name']) ?></span>
                                    </div>
                                    <div class="info-row">
                                        <i class="fa-solid fa-phone"></i>
                                        <a href="tel:<?= htmlspecialchars($row['phone']) ?>" class="text-decoration-none fw-bold text-dark">
                                            <?= htmlspecialchars($row['phone']) ?>
                                        </a>
                                    </div>
                                    <div class="info-row">
                                        <i class="fa-solid fa-location-dot text-danger"></i>
                                        <span>
                                            <?= htmlspecialchars($row['address']) ?>, 
                                            <?= htmlspecialchars($row['district']) ?>
                                        </span>
                                    </div>
                                    
                                    <?php if(!empty($row['note'])): ?>
                                        <div class="alert alert-warning p-2 mt-2 mb-0 small">
                                            <i class="fa-solid fa-note-sticky"></i> <b>Ghi chú:</b> <?= htmlspecialchars($row['note']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="job-footer">
                                    <a href="customer_history.php?customer_id=<?= $row['customer_id'] ?>" 
   class="btn btn-outline-secondary btn-action">
   <i class="fa-solid fa-clock-rotate-left"></i> Lịch sử
</a>

                                    <button onclick="checkInGPS(<?= $row['id'] ?>)" 
                                            id="btn-checkin-<?= $row['id'] ?>"
                                            class="btn btn-info text-white btn-action">
                                        <i class="fa-solid fa-location-crosshairs"></i> Đến nơi
                                    </button>
                                    
                                    <a href="signature.php?id=<?= $row['id'] ?>" 
                                       id="btn-sign-<?= $row['id'] ?>"
                                       class="btn btn-success btn-action btn-full disabled-link">
                                       <i class="fa-solid fa-file-signature"></i> Khách Ký Nghiệm Thu
                                    </a>
                                </div>

                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

            <?php else: ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fa-solid fa-mug-hot fa-4x text-secondary opacity-25"></i>
                    </div>
                    <h5 class="text-muted">Hiện chưa có công việc mới</h5>
                    <p class="text-muted small">Hãy nghỉ ngơi một chút nhé!</p>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<script src="/TechFixPHP/assets/js/bootstrap.bundle.min.js"></script>
<script>
    // LOGIC CHECK-IN GPS
    function checkInGPS(bookingId) {
        if (!navigator.geolocation) {
            Swal.fire('Lỗi thiết bị', 'Trình duyệt của bạn không hỗ trợ GPS.', 'error');
            return;
        }

        Swal.fire({
            title: 'Đang định vị...',
            html: 'Vui lòng đợi hệ thống xác thực vị trí của bạn.<br><b>Đừng tắt màn hình nhé!</b>',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                const formData = new FormData();
                formData.append('booking_id', bookingId);
                formData.append('lat', lat);
                formData.append('lng', lng);

                // Gọi API kiểm tra khoảng cách
                fetch('../api/check_location.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Check-in Thành công!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        // === QUAN TRỌNG: MỞ KHÓA NÚT KÝ ===
                        unlockSignatureButton(bookingId);

                    } else {
                        // Nếu ở xa quá: Cảnh báo nhưng vẫn cho phép làm (để test)
                        // Thực tế thì nên chặn, nhưng mình để nút confirm cho linh hoạt
                        Swal.fire({
                            icon: 'warning',
                            title: 'Vị trí không khớp!',
                            text: data.message,
                            showCancelButton: true,
                            confirmButtonText: 'Vẫn tiếp tục (Test)',
                            cancelButtonText: 'Thử lại'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                unlockSignatureButton(bookingId);
                            }
                        });
                    }
                })
                .catch(err => {
                    Swal.fire('Lỗi Server', 'Không thể kết nối đến máy chủ.', 'error');
                });
            },
            (error) => {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi GPS',
                    text: 'Không lấy được vị trí. Hãy kiểm tra xem bạn đã bật GPS chưa.',
                    footer: '<a href="#">Hướng dẫn bật GPS</a>'
                });
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }

    // Hàm mở khóa nút Ký tên
    function unlockSignatureButton(id) {
        // 1. Ẩn nút Check-in (vì đã check rồi)
        const btnCheckin = document.getElementById('btn-checkin-' + id);
        if(btnCheckin) {
            btnCheckin.innerHTML = '<i class="fa-solid fa-check"></i> Đã đến';
            btnCheckin.classList.remove('btn-info');
            btnCheckin.classList.add('btn-secondary');
            btnCheckin.disabled = true;
        }

        // 2. Sáng nút Ký tên
        const btnSign = document.getElementById('btn-sign-' + id);
        if(btnSign) {
            btnSign.classList.remove('disabled-link'); // Bỏ lớp khóa
            btnSign.classList.add('animate__animated', 'animate__pulse', 'animate__infinite'); // Thêm hiệu ứng nhấp nháy mời gọi
            
            // Sau 3s tắt nhấp nháy cho đỡ rối mắt
            setTimeout(() => {
                btnSign.classList.remove('animate__animated', 'animate__pulse', 'animate__infinite');
            }, 3000);
        }
    }
</script>

</body>
</html>