<?php
// pages/admin/dashboard.php
// GitHub Copilot

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TechFixPHP/pages/public_page/login.php');
    exit;
}
include __DIR__ . '/template/sidebar.php';


// Simple admin auth check (adjust to your auth system)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header('Location: /TechFixPHP/pages/public_page/login.php');
  exit;
}

// includes

include __DIR__ . '../template/sidebar.php';

// DB connection (shared config)
$dbPath = __DIR__ . '/../../config/db.php';
if (!file_exists($dbPath)) {
  error_log("DB config not found: $dbPath");
  die('Database configuration missing.');
}
require_once $dbPath;

/*
  This project may provide a PDO instance ($pdo) or mysqli instance ($conn / $mysqli).
  The helper functions below try to work with either.
*/

function has_pdo()
{
  return isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO;
}
function has_mysqli()
{
  return (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) ||
       (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli);
}
function get_mysqli()
{
  if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) return $GLOBALS['conn'];
  if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli) return $GLOBALS['mysqli'];
  return null;
}

function fetch_count($sql, $param = [])
{
  if (has_pdo()) {
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute($param);
    return (int) $stmt->fetchColumn();
  } elseif (has_mysqli()) {
    $mysqli = get_mysqli();
    if (!$mysqli) return 0;
    $res = $mysqli->query($sql);
    if (!$res) return 0;
    $row = $res->fetch_row();
    return (int) $row[0];
  }
  return 0;
}

function fetch_all($sql, $param = [])
{
  if (has_pdo()) {
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute($param);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  } elseif (has_mysqli()) {
    $mysqli = get_mysqli();
    if (!$mysqli) return [];
    $res = $mysqli->query($sql);
    if (!$res) return [];
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    return $rows;
  }
  return [];
}

// Stats
$total_customers = fetch_count("SELECT COUNT(*) FROM users WHERE role = 'customer'");
$total_technicians = fetch_count("SELECT COUNT(*) FROM users WHERE role = 'technician'");
$total_repairs = fetch_count("SELECT COUNT(*) FROM repairs");
$open_repairs = fetch_count("SELECT COUNT(*) FROM repairs WHERE status != 'completed'");

// Recent repairs
$recent_repairs_sql = "
  SELECT r.id, r.device, r.problem, r.status, r.created_at,
       u.name AS customer_name,
       t.name AS technician_name
  FROM repairs r
  LEFT JOIN users u ON r.customer_id = u.id
  LEFT JOIN users t ON r.technician_id = t.id
  ORDER BY r.created_at DESC
  LIMIT 5
";
$recent_repairs = fetch_all($recent_repairs_sql);

?>
<!-- Main admin dashboard content -->
<div class="container-fluid">
  <div class="row">
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Bảng điều khiển</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
          <div class="btn-group me-2">
            <a href="/TechFixPHP/pages/admin/new-repair.php" class="btn btn-sm btn-primary">Thêm phiếu sửa</a>
            <a href="/TechFixPHP/pages/admin/customers.php" class="btn btn-sm btn-outline-secondary">Khách hàng</a>
          </div>
        </div>
      </div>

      <!-- summary cards -->
      <div class="row mb-4">
        <div class="col-sm-6 col-md-3">
          <div class="card text-white bg-primary mb-3">
            <div class="card-body">
              <h5 class="card-title">Khách hàng</h5>
              <p class="card-text display-6"><?php echo htmlspecialchars($total_customers); ?></p>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-md-3">
          <div class="card text-white bg-success mb-3">
            <div class="card-body">
              <h5 class="card-title">Kỹ thuật viên</h5>
              <p class="card-text display-6"><?php echo htmlspecialchars($total_technicians); ?></p>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-md-3">
          <div class="card text-white bg-info mb-3">
            <div class="card-body">
              <h5 class="card-title">Tổng phiếu</h5>
              <p class="card-text display-6"><?php echo htmlspecialchars($total_repairs); ?></p>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-md-3">
          <div class="card text-white bg-warning mb-3">
            <div class="card-body">
              <h5 class="card-title">Đang xử lý</h5>
              <p class="card-text display-6"><?php echo htmlspecialchars($open_repairs); ?></p>
            </div>
          </div>
        </div>
      </div>

      <!-- recent repairs table -->
      <div class="card mb-4">
        <div class="card-header">
          Phiếu sửa gần đây
          <a href="/TechFixPHP/pages/admin/repairs.php" class="btn btn-sm btn-link float-end">Xem tất cả</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Thiết bị</th>
                  <th>Vấn đề</th>
                  <th>Khách hàng</th>
                  <th>Kỹ thuật viên</th>
                  <th>Trạng thái</th>
                  <th>Ngày tạo</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($recent_repairs)): ?>
                  <tr><td colspan="7" class="text-center py-4">Không có phiếu sửa nào</td></tr>
                <?php else: ?>
                  <?php foreach ($recent_repairs as $r): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($r['id']); ?></td>
                      <td><?php echo htmlspecialchars($r['device']); ?></td>
                      <td><?php echo htmlspecialchars($r['problem']); ?></td>
                      <td><?php echo htmlspecialchars($r['customer_name'] ?? '—'); ?></td>
                      <td><?php echo htmlspecialchars($r['technician_name'] ?? '—'); ?></td>
                      <td><?php echo htmlspecialchars(ucfirst($r['status'])); ?></td>
                      <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- placeholder for charts or widgets -->
      <div class="row">
        <div class="col-md-6">
          <div class="card mb-4">
            <div class="card-header">Hoạt động 30 ngày</div>
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
                <li>Cập nhật bảo trì tiếp theo: <?php echo date('Y-m-d', strtotime('+7 days')); ?>.</li>
                <li>Người dùng mới: <?php echo htmlspecialchars($total_customers); ?></li>
              </ul>
            </div>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- Minimal scripts (adjust asset paths if needed) -->
<link href="/TechFixPHP/assets/css/bootstrap.min.css" rel="stylesheet">
<script src="/TechFixPHP/assets/js/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Example chart data (replace with real data via AJAX if desired)
  var ctx = document.getElementById('repairsChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['-29','-25','-20','-15','-10','-5','Today'],
      datasets: [{
        label: 'Phiếu sửa',
        backgroundColor: 'rgba(54,162,235,0.2)',
        borderColor: 'rgba(54,162,235,1)',
        data: [3,5,2,8,6,4,<?php echo (int)$open_repairs; ?>]
      }]
    },
    options: { responsive: true, maintainAspectRatio: false }
  });
});
</script>

<?php
// These paths should exist:
include __DIR__ . '../template/sidebar.php';
