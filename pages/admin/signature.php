<?php
session_start();
require_once '../../config/db.php';

// 1. Kiểm tra quyền (Admin hoặc Tech)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'technical'])) {
    header('Location: /TechFixPHP/pages/public_page/login.php');
    exit;
}

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';

// 2. XỬ LÝ LƯU CHỮ KÝ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sig_data = $_POST['signature'];
    
    if (empty($sig_data)) {
        $error = "Vui lòng ký tên trước khi xác nhận.";
    } else {
        // Xử lý ảnh base64
        $img = str_replace('data:image/png;base64,', '', $sig_data);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        
        // Tạo tên file và thư mục
        $fileName = 'sig_' . $booking_id . '_' . time() . '.png';
        $uploadDir = '../../assets/upload/signatures/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        file_put_contents($uploadDir . $fileName, $data);
        
        // Cập nhật Database: Lưu đường dẫn ảnh + Đổi trạng thái -> Completed
        $dbPath = 'assets/upload/signatures/' . $fileName;
        
        $stmt = $conn->prepare("UPDATE bookings SET customer_signature = ?, status = 'completed', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $dbPath, $booking_id);
        
        if ($stmt->execute()) {
            // Chuyển hướng sang trang Hóa đơn để in
            header("Location: /TechFixPHP/pages/admin/invoice_order.php?id=$booking_id");
            exit;
        } else {
            $error = "Lỗi lưu dữ liệu: " . $conn->error;
        }
    }
}

// Lấy thông tin đơn hàng để hiện tên khách
$q = $conn->query("SELECT customer_name FROM bookings WHERE id = $booking_id");
$order = $q->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Ký Nghiệm Thu - TechFix</title>
    <link href="/TechFixPHP/assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; height: 100vh; display: flex; flex-direction: column; }
        .sig-container { 
            flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 20px; 
        }
        .card { width: 100%; max-width: 500px; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        
        canvas {
            border: 2px dashed #ccc; border-radius: 10px; background: #fff;
            touch-action: none; /* Chặn cuộn trang khi ký trên mobile */
            width: 100%; height: 250px; cursor: crosshair;
        }
    </style>
</head>
<body>

<div class="sig-container">
    <div class="card">
        <div class="card-header bg-primary text-white text-center py-3">
            <h5 class="mb-0">XÁC NHẬN NGHIỆM THU</h5>
            <small>Đơn hàng #<?= $booking_id ?></small>
        </div>
        <div class="card-body text-center">
            <p class="text-muted">Khách hàng: <strong><?= htmlspecialchars($order['customer_name'] ?? 'Khách') ?></strong></p>
            <p class="small text-danger mb-2">Vui lòng ký tên vào khung bên dưới để xác nhận hoàn thành dịch vụ.</p>
            
            <canvas id="signature-pad"></canvas>

            <?php if ($error): ?>
                <div class="alert alert-danger mt-2 p-2"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" id="sigForm" class="mt-3">
                <input type="hidden" name="signature" id="signature-data">
                
                <div class="d-grid gap-2 d-flex justify-content-center">
                    <button type="button" class="btn btn-outline-secondary w-50" id="clear">
                        🗑️ Ký lại
                    </button>
                    <button type="submit" class="btn btn-success w-50 fw-bold">
                        ✅ Xác nhận
                    </button>
                </div>
            </form>
            
            <div class="mt-3">
                <a href="tech_schedule.php" class="text-decoration-none text-muted small">← Quay lại lịch</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    var canvas = document.getElementById('signature-pad');
    
    // Hàm chỉnh lại kích thước canvas để nét vẽ không bị mờ trên màn hình Retina/Mobile
    function resizeCanvas() {
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
    }
    window.onresize = resizeCanvas;
    resizeCanvas(); // Gọi ngay khi load

    var signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgb(255, 255, 255)',
        penColor: 'rgb(0, 0, 100)'
    });

    document.getElementById('clear').addEventListener('click', function () {
        signaturePad.clear();
    });

    document.getElementById('sigForm').addEventListener('submit', function (e) {
        if (signaturePad.isEmpty()) {
            alert("Vui lòng ký tên trước khi xác nhận!");
            e.preventDefault();
        } else {
            // Lưu dữ liệu ảnh vào input ẩn
            var data = signaturePad.toDataURL('image/png');
            document.getElementById('signature-data').value = data;
        }
    });
</script>

</body>
</html>