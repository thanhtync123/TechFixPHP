<?php
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'technical')) {
    header("Location: /TechFixPHP/pages/public_page/login.php");
    exit();
}
include __DIR__ . '/../../config/db.php';
include __DIR__ . '/../admin/template/sidebar.php'; 

// Lấy vai trò và ID của người dùng
$role = $_SESSION['role'];
$user_id = $_SESSION['user']['id'] ?? null;

// Biến để lưu dữ liệu
$technicians = []; // Cho Admin
$bookings = []; // Cho Technical
$page_title = "Lịch làm việc"; // Tiêu đề mặc định

// ==========================================================
// PHÂN LOẠI XỬ LÝ DỰA TRÊN VAI TRÒ
// ==========================================================

if ($role === 'admin') {
    // 1. NẾU LÀ ADMIN: Lấy danh sách thợ để đưa vào dropdown
    $page_title = "Quản lý Lịch Kỹ thuật viên";
    $tech_query = $conn->query("SELECT id, name FROM users WHERE role = 'technical'");
    if ($tech_query) {
        $technicians = $tech_query->fetch_all(MYSQLI_ASSOC);
    }
} 
elseif ($role === 'technical') {
    // 2. NẾU LÀ KỸ THUẬT VIÊN: Lấy lịch của chính họ
    $page_title = "Lịch làm việc của tôi";
    
    $stmt = $conn->prepare("
        SELECT 
            b.id, b.customer_name, b.phone, b.address, b.appointment_time, b.status,
            s.name AS service_name
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        WHERE b.technician_id = ? AND b.status = 'confirmed'
        ORDER BY b.appointment_time ASC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?> - TECHFIX</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        body { background: #f5f6fa; font-family: 'Poppins', sans-serif; }
        .container { max-width: 1200px; margin: 40px auto; background: white; border-radius: 10px; padding: 20px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        select { padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; font-size: 1em; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #0099ff; color: white; }
        td { vertical-align: middle; }
        .status { padding: 5px 10px; border-radius: 5px; color: white; font-weight: 600; text-align: center; }
        .pending { background-color: #ff9800; }
        .confirmed { background-color: #4caf50; }
        .completed { background-color: #2196f3; }
        .cancelled { background-color: #f44336; }
        .action-btn { background: #e91e63; color: white; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h2><?= $page_title ?></h2>

        <?php // ========================================== ?>
        <?php // PHẦN 1: DÀNH CHO ADMIN ?>
        <?php // ========================================== ?>
        <?php if ($role === 'admin'): ?>
            
            <label for="technician">Chọn kỹ thuật viên: </label>
            <select id="technician" onchange="loadSchedule()">
                <option value="">-- Xem lịch của --</option>
                <?php foreach ($technicians as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <div id="scheduleContainer" style="margin-top: 20px;"></div>

        <?php // ========================================== ?>
        <?php // PHẦN 2: DÀNH CHO KỸ THUẬT VIÊN ?>
        <?php // ========================================== ?>
        <?php elseif ($role === 'technical'): ?>
            
            <?php if (!empty($bookings)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Khách hàng</th>
                            <th>SĐT</th>
                            <th>Địa chỉ</th>
                            <th>Dịch vụ</th>
                            <th>Ngày hẹn</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?= htmlspecialchars($booking['customer_name']) ?></td>
                                <td><?= htmlspecialchars($booking['phone']) ?></td>
                                <td><?= htmlspecialchars($booking['address']) ?></td>
                                <td><?= htmlspecialchars($booking['service_name']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($booking['appointment_time'])) ?></td>
                                <td>
                                    <form action="../admin/api_complete_job.php" method="POST" onsubmit="return confirm('Xác nhận hoàn thành công việc này?')">
                                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                        <button type="submit" class="action-btn" style="background-color: #4caf50;">Hoàn thành</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align:center;">Bạn chưa có lịch làm việc nào được giao (ở trạng thái 'Xác nhận').</p>
            <?php endif; ?>

        <?php endif; ?>

    </div>

    <?php if ($role === 'admin'): ?>
    <script>
        function loadSchedule() {
            const techId = document.getElementById('technician').value;
            const container = document.getElementById('scheduleContainer');
            if (!techId) {
                container.innerHTML = '';
                return;
            }

            // Gọi file fetch_schedule.php để lấy lịch
            container.innerHTML = '<p>Đang tải...</p>';
            fetch('fetch_schedule.php?technician_id=' + techId)
                .then(res => res.text()) // Lấy HTML
                .then(data => {
                    container.innerHTML = data;
                })
                .catch(err => {
                    container.innerHTML = '<p style="color:red;">Lỗi khi tải lịch.</p>';
                });
        }
    </script>
    <?php endif; ?>

</body>
</html>