<?php
// pages/admin/dashboard.php
session_start();

// 1️⃣ KIỂM TRA QUYỀN
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TechFixPHP/pages/public_page/login.php');
    exit;
}

// 2️⃣ INCLUDE SIDEBAR (chỉ 1 lần)
include __DIR__ . '/template/sidebar.php'; 

// 3️⃣ KẾT NỐI DB
$dbPath = __DIR__ . '/../../config/db.php';
if (!file_exists($dbPath)) {
    error_log("DB config not found: $dbPath");
    die('Database configuration missing.');
}
require_once $dbPath;

/* ==========================================================
 * HÀM HELPER
 * ========================================================== */
function has_pdo() {
    return isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO;
}
function has_mysqli() {
    return (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli)
        || (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli);
}
function get_mysqli() {
    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) return $GLOBALS['conn'];
    if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli) return $GLOBALS['mysqli'];
    return null;
}
function fetch_count($sql, $param = []) {
    if (has_pdo()) {
        $stmt = $GLOBALS['pdo']->prepare($sql);
        $stmt->execute($param);
        return (int) $stmt->fetchColumn();
    } elseif (has_mysqli()) {
        $mysqli = get_mysqli();
        if (!$mysqli) return 0;
        $res = $mysqli->query($sql);
        if (!$res) {
            error_log("Query failed: " . $mysqli->error);
            return 0;
        }
        $row = $res->fetch_row();
        return (int) ($row[0] ?? 0);
    }
    return 0;
}
function fetch_all($sql, $param = []) {
    if (has_pdo()) {
        $stmt = $GLOBALS['pdo']->prepare($sql);
        $stmt->execute($param);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (has_mysqli()) {
        $mysqli = get_mysqli();
        if (!$mysqli) return [];
        $res = $mysqli->query($sql);
        if (!$res) {
            error_log("Query failed: " . $mysqli->error);
            return [];
        }
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        return $rows;
    }
    return [];
}

/* ==========================================================
 * TRUY VẤN DỮ LIỆU
 * ========================================================== */
$pending_bookings = fetch_count("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
$confirmed_bookings = fetch_count("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'");
$monthly_completed = fetch_count("
    SELECT COUNT(*) FROM bookings 
    WHERE status = 'completed' AND appointment_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$monthly_revenue_raw = fetch_count("
    SELECT SUM(final_price) FROM bookings 
    WHERE status = 'completed' AND appointment_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$monthly_revenue = number_format($monthly_revenue_raw) . ' đ';

$recent_bookings_sql = "
    SELECT 
        b.id, b.status, b.appointment_time, b.customer_name,
        s.name AS service_name,
        t.name AS technician_name
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    LEFT JOIN users t ON b.technician_id = t.id
    ORDER BY b.created_at DESC
    LIMIT 5
";
$recent_bookings = fetch_all($recent_bookings_sql);
?>

<div class="container-fluid">
    <div class="row">
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Bảng điều khiển</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="/TechFixPHP/pages/admin/admin_dispatch.php" class="btn btn-sm btn-primary">Trung tâm Điều phối</a>
                        <a href="/TechFixPHP/pages/admin/customers.php" class="btn btn-sm btn-outline-secondary">Khách hàng</a>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-sm-6 col-md-3">
                    <div class="card text-white bg-warning mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Đơn chờ (Pending)</h5>
                            <p class="card-text display-6"><?php echo htmlspecialchars($pending_bookings); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Đang xử lý (Confirmed)</h5>
                            <p class="card-text display-6"><?php echo htmlspecialchars($confirmed_bookings); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Hoàn thành (30 ngày)</h5>
                            <p class="card-text display-6"><?php echo htmlspecialchars($monthly_completed); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card text-white bg-dark mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Doanh thu (30 ngày)</h5>
                            <h4 class="card-text mt-3" style="font-size: 1.75rem;"><?php echo htmlspecialchars($monthly_revenue); ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    Lịch đặt gần đây (Bookings)
                    <a href="/TechFixPHP/pages/admin/admin_dispatch.php" class="btn btn-sm btn-link float-end">Xem tất cả</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Dịch vụ</th>
                                    <th>Khách hàng</th>
                                    <th>Kỹ thuật viên</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày hẹn</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_bookings)): ?>
                                    <tr><td colspan="6" class="text-center py-4">Không có lịch đặt nào</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recent_bookings as $b): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($b['id']); ?></td>
                                            <td><?php echo htmlspecialchars($b['service_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($b['customer_name'] ?? '—'); ?></td>
                                            <td><?php echo htmlspecialchars($b['technician_name'] ?? '—'); ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst($b['status'])); ?></td>
                                            <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($b['appointment_time']))); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">Hoạt động 30 ngày (Đơn đang xử lý)</div>
                        <div class="card-body">
                            <canvas id="repairsChart" height="150"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">Thông báo</div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li>Hệ thống khỏe mạnh.</li>
                                <li>Đơn hàng đang chờ: <?php echo htmlspecialchars($pending_bookings); ?></li>
                                <li>Đơn hàng đang xử lý: <?php echo htmlspecialchars($confirmed_bookings); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<link href="/TechFixPHP/assets/css/bootstrap.min.css" rel="stylesheet"> 
<script src="/TechFixPHP/assets/js/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var ctx = document.getElementById('repairsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['-29','-25','-20','-15','-10','-5','Today'],
            datasets: [{
                label: 'Đơn đang xử lý',
                backgroundColor: 'rgba(54,162,235,0.2)',
                borderColor: 'rgba(54,162,235,1)',
                data: [3, 5, 2, 8, 6, 4, <?php echo (int)$confirmed_bookings; ?>] 
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
});
</script>
