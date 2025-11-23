<?php
// pages/admin/dashboard.php
session_start();

// 1️⃣ KIỂM TRA QUYỀN
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TechFixPHP/pages/public_page/login.php');
    exit;
}

// 2️⃣ KẾT NỐI DB
require_once '../../config/db.php';

/* ==========================================================
 * HELPER FUNCTIONS
 * ========================================================== */
function fetch_value($sql) {
    global $conn;
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_row()) {
        return $row[0]; // Lấy giá trị đầu tiên (COUNT, SUM...)
    }
    return 0;
}

function fetch_all($sql) {
    global $conn;
    $result = $conn->query($sql);
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

/* ==========================================================
 * LẤY SỐ LIỆU THỐNG KÊ (KPIs)
 * ========================================================== */
// Đơn chờ xử lý
$pending_bookings = (int)fetch_value("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");

// Đang thực hiện
$processing_bookings = (int)fetch_value("SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed', 'processing')");

// Hoàn thành tháng này
$monthly_completed = (int)fetch_value("
    SELECT COUNT(*) FROM bookings 
    WHERE status = 'completed' AND appointment_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");

// Doanh thu tháng này
$monthly_revenue = (float)fetch_value("
    SELECT SUM(final_price) FROM bookings 
    WHERE status = 'completed' AND appointment_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");

/* ==========================================================
 * [MỚI] DỮ LIỆU CHO BIỂU ĐỒ (7 NGÀY GẦN NHẤT)
 * ========================================================== */
// Lấy số đơn hàng theo từng ngày trong 7 ngày qua
$chart_sql = "
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM bookings 
    WHERE created_at >= DATE(NOW()) - INTERVAL 6 DAY
    GROUP BY DATE(created_at)
    ORDER BY date ASC
";
$chart_data_raw = fetch_all($chart_sql);

// Chuẩn bị mảng dữ liệu cho JS (Lấp đầy những ngày không có đơn bằng số 0)
$chart_labels = [];
$chart_values = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = 0;
    // Tìm xem ngày này có trong DB không
    foreach ($chart_data_raw as $d) {
        if ($d['date'] == $date) {
            $count = (int)$d['count'];
            break;
        }
    }
    $chart_labels[] = date('d/m', strtotime($date)); // Label: 20/11
    $chart_values[] = $count;
}

// 5 Đơn mới nhất
$recent_bookings = fetch_all("
    SELECT b.*, s.name AS service_name, t.name AS tech_name
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    LEFT JOIN users t ON b.technician_id = t.id
    ORDER BY b.created_at DESC LIMIT 5
");

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TechFix</title>
    <link href="/TechFixPHP/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background-color: #343a40; color: white; }
        .sidebar a { color: rgba(255,255,255,.8); text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover, .sidebar a.active { background-color: #0d6efd; color: white; }
        .card-icon { font-size: 2.5rem; opacity: 0.3; position: absolute; right: 15px; top: 15px; }
        .table-responsive { background: white; border-radius: 8px; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse p-0">
            <?php include __DIR__ . '/template/sidebar.php'; ?>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fa-solid fa-gauge-high"></i> Bảng điều khiển</h1>
                <div class="btn-toolbar mb-2 mb-md-0 gap-2">
                    
                    <form action="export_excel.php" method="GET" class="d-flex align-items-center gap-2">
                        <select name="month" class="form-select form-select-sm" style="width:auto;">
                            <option value="">-- Tháng --</option>
                            <?php for($m=1; $m<=12; $m++) echo "<option value='$m'>Tháng $m</option>"; ?>
                        </select>
                        <select name="year" class="form-select form-select-sm" style="width:auto;">
                            <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                            <option value="<?= date('Y')-1 ?>"><?= date('Y')-1 ?></option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="fa-solid fa-file-excel"></i> Xuất Excel
                        </button>
                    </form>

                    <a href="/TechFixPHP/pages/admin/admin_dispatch.php" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-truck-fast"></i> Điều phối
                    </a>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="card text-white bg-warning h-100 shadow-sm">
                        <div class="card-body position-relative">
                            <h6 class="card-title">Đơn chờ xử lý</h6>
                            <h2 class="display-6 fw-bold"><?= number_format($pending_bookings) ?></h2>
                            <i class="fa-regular fa-clock card-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="card text-white bg-primary h-100 shadow-sm">
                        <div class="card-body position-relative">
                            <h6 class="card-title">Đang thực hiện</h6>
                            <h2 class="display-6 fw-bold"><?= number_format($processing_bookings) ?></h2>
                            <i class="fa-solid fa-screwdriver-wrench card-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="card text-white bg-success h-100 shadow-sm">
                        <div class="card-body position-relative">
                            <h6 class="card-title">Hoàn thành (30 ngày)</h6>
                            <h2 class="display-6 fw-bold"><?= number_format($monthly_completed) ?></h2>
                            <i class="fa-solid fa-check-double card-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="card text-white bg-dark h-100 shadow-sm">
                        <div class="card-body position-relative">
                            <h6 class="card-title">Doanh thu (30 ngày)</h6>
                            <h3 class="mt-2 fw-bold"><?= number_format($monthly_revenue, 0, ',', '.') ?> đ</h3>
                            <i class="fa-solid fa-sack-dollar card-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header fw-bold bg-white">
                            <i class="fa-solid fa-chart-line"></i> Xu hướng đặt lịch (7 ngày qua)
                        </div>
                        <div class="card-body">
                            <canvas id="bookingChart" style="max-height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header fw-bold bg-white">
                            <i class="fa-solid fa-bell"></i> Thông tin hệ thống
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Trạng thái Server
                                <span class="badge bg-success rounded-pill">Online</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                PHP Version
                                <span class="text-muted"><?= phpversion() ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Ngày giờ
                                <span class="text-muted"><?= date('d/m/Y H:i') ?></span>
                            </li>
                            <li class="list-group-item text-center text-muted small mt-3 border-0">
                                <em>TechFix Admin Panel v1.0</em>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-list"></i> Đơn đặt lịch mới nhất</span>
                    <a href="/TechFixPHP/pages/admin/admin_dispatch.php" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#ID</th>
                                <th>Khách hàng</th>
                                <th>Dịch vụ</th>
                                <th>Ngày hẹn</th>
                                <th>Kỹ thuật viên</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($recent_bookings)): ?>
                                <tr><td colspan="6" class="text-center py-3">Chưa có dữ liệu.</td></tr>
                            <?php else: ?>
                                <?php foreach($recent_bookings as $b): ?>
                                <tr>
                                    <td><strong>#<?= $b['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($b['customer_name']) ?></td>
                                    <td style="white-space: normal; max-width: 300px;">
    <?= htmlspecialchars($b['service_name']) ?>
</td>
                                    <td><?= date('d/m H:i', strtotime($b['appointment_time'])) ?></td>
                                    <td>
                                        <?php if($b['tech_name']): ?>
                                            <span class="text-primary"><i class="fa-solid fa-user-gear"></i> <?= htmlspecialchars($b['tech_name']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">Thinking...</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $stt = $b['status'];
                                            $badgeClass = 'bg-secondary';
                                            if($stt=='pending') $badgeClass = 'bg-warning text-dark';
                                            if($stt=='confirmed') $badgeClass = 'bg-primary';
                                            if($stt=='completed') $badgeClass = 'bg-success';
                                            if($stt=='cancelled') $badgeClass = 'bg-danger';
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= ucfirst($stt) ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('bookingChart').getContext('2d');
    
    // Dữ liệu thật từ PHP
    const labels = <?= json_encode($chart_labels) ?>;
    const data = <?= json_encode($chart_values) ?>;

    new Chart(ctx, {
        type: 'bar', // Đổi sang dạng cột nhìn cho khỏe khoắn
        data: {
            labels: labels,
            datasets: [{
                label: 'Số lượng đơn',
                data: data,
                backgroundColor: 'rgba(13, 110, 253, 0.7)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 } // Trục Y chỉ hiện số nguyên
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>

<script src="/TechFixPHP/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>