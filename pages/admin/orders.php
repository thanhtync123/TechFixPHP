<?php
// ================== PHẦN XỬ LÝ PHP ==================
include '../../config/db.php'; // Kết nối database
// include 'template/sidebar.php'; // Không cần include sidebar vì đã có trong HTML

// --- Cập nhật trạng thái đơn hàng ---
if (isset($_GET['update']) && isset($_GET['id']) && isset($_GET['status'])) {
    $id = intval($_GET['id']);
    $status = $_GET['status'];
    $query = "UPDATE orders SET status='$status' WHERE id=$id";
    mysqli_query($conn, $query);
}

// --- Xóa đơn hàng ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $query = "DELETE FROM orders WHERE id=$id";
    mysqli_query($conn, $query);
}

// --- Lọc đơn hàng theo trạng thái ---
$where = '';
if (isset($_GET['sort'])) {
    $sort = $_GET['sort'];
    $where = ($sort == 'all' || $sort == '') ? '' : "WHERE o.status = '$sort'";
}

// --- Lấy dữ liệu đơn hàng ---
$query = "SELECT 
        o.id,
        c.name AS customer_name,
        t.name AS technician_name,
        s.name AS service_name,
        DATE_FORMAT(o.schedule_time, '%d/%m/%Y %H:%i') AS schedule_time,
        o.status,
        o.total_price,
        DATE_FORMAT(o.created_at, '%d/%m/%Y %H:%i') AS created_at,
        DATE_FORMAT(o.updated_at, '%d/%m/%Y %H:%i') AS updated_at
    FROM orders o
    JOIN services s ON o.service_id = s.id
    JOIN users c ON o.customer_id = c.id
    LEFT JOIN users t ON o.technician_id = t.id
    $where
    ORDER BY o.id DESC";

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng</title>
    <style>
        /* === TOÀN BỘ CSS GỐC === */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%); color: #e4e4e7; min-height: 100vh; }
        .sidebar { position: fixed; left: 0; top: 0; width: 280px; height: 100vh; background: rgba(22,22,35,0.8); backdrop-filter: blur(20px); border-right: 1px solid rgba(255,255,255,0.05); padding: 2rem 0; z-index: 100; }
        .logo { padding: 0 2rem 2rem; border-bottom: 1px solid rgba(255,255,255,0.05); margin-bottom: 2rem; }
        .logo h1 { font-size: 1.5rem; font-weight: 700; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .nav-menu { list-style: none; padding: 0 1rem; }
        .nav-link { display: flex; align-items: center; padding: 0.875rem 1rem; color: #a1a1aa; text-decoration: none; border-radius: 12px; transition: all 0.3s ease; }
        .nav-link.active, .nav-link:hover { color: #fff; background: rgba(102,126,234,0.1); transform: translateX(4px); }
        .main-content { margin-left: 280px; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header-title h2 { font-size: 2rem; font-weight: 700; background: linear-gradient(135deg, #fff 0%, #a1a1aa 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .btn { padding: 0.75rem 1.5rem; border-radius: 12px; cursor: pointer; transition: 0.3s; text-decoration: none; font-weight: 500; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }
        .btn-secondary { background: rgba(255,255,255,0.05); color: #e4e4e7; border: 1px solid rgba(255,255,255,0.1); }
        .filters { background: rgba(255,255,255,0.03); border-radius: 20px; padding: 1.5rem; margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap; }
        .orders-table { background: rgba(255,255,255,0.03); border-radius: 20px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-top: 1px solid rgba(255,255,255,0.05); }
        tbody tr:hover { background: rgba(102,126,234,0.05); }
        .status-badge { padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.75rem; }
        .status-pending { background: rgba(234,179,8,0.1); color: #fbbf24; }
        .status-completed { background: rgba(34,197,94,0.1); color: #4ade80; }
        .status-cancelled { background: rgba(239,68,68,0.1); color: #f87171; }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">
            <h1>⚡ DASHBOARD</h1>
            <p>Hệ thống quản lý</p>
        </div>
        <nav>
            <ul class="nav-menu">
                <li><a href="dashboard.php" class="nav-link">📊 Dashboard</a></li>
                <li><a href="orders.php" class="nav-link active">📦 Đơn hàng</a></li>
                <li><a href="createorder.php" class="nav-link">➕ Tạo đơn mới</a></li>
                <li><a href="services.php" class="nav-link">⚙️ Dịch vụ</a></li>
                <li><a href="users.php" class="nav-link">👥 Người dùng</a></li>
            </ul>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main-content">
        <header class="header">
            <div class="header-title">
                <h2>Quản lý đơn hàng</h2>
                <p>Theo dõi và xử lý đơn hàng</p>
            </div>
            <div class="header-actions">
                <a href="?sort=all" class="btn btn-secondary">🔄 Làm mới</a>
                <a href="order_detail.php" class="btn btn-primary">➕ Tạo mới</a>
            </div>
        </header>

        <!-- Bộ lọc -->
        <div class="filters">
            <form method="get" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:center;">
                <label>Trạng thái:</label>
                <select name="sort" onchange="this.form.submit()" class="filter-select">
                    <option value="all" <?= (!isset($_GET['sort']) || $_GET['sort'] == 'all') ? 'selected' : '' ?>>Tất cả</option>
                    <option value="pending" <?= (isset($_GET['sort']) && $_GET['sort'] == 'pending') ? 'selected' : '' ?>>Đang chờ</option>
                    <option value="completed" <?= (isset($_GET['sort']) && $_GET['sort'] == 'completed') ? 'selected' : '' ?>>Hoàn thành</option>
                    <option value="cancelled" <?= (isset($_GET['sort']) && $_GET['sort'] == 'cancelled') ? 'selected' : '' ?>>Đã hủy</option>
                </select>
            </form>
        </div>

        <!-- Bảng dữ liệu -->
        <div class="orders-table">
            <table>
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Khách hàng</th>
                        <th>Kỹ thuật viên</th>
                        <th>Dịch vụ</th>
                        <th>Thời gian hẹn</th>
                        <th>Trạng thái</th>
                        <th>Tổng tiền</th>
                        <th>Tạo lúc</th>
                        <th>Cập nhật</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>#<?= $row['id'] ?></td>
                                <td><?= $row['customer_name'] ?></td>
                                <td><?= $row['technician_name'] ?: 'Chưa xác định' ?></td>
                                <td><?= $row['service_name'] ?></td>
                                <td><?= $row['schedule_time'] ?></td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'pending' => 'status-pending',
                                        'completed' => 'status-completed',
                                        'cancelled' => 'status-cancelled'
                                    ][$row['status']] ?? 'status-pending';
                                    ?>
                                    <select onchange="window.location='?update=1&id=<?= $row['id'] ?>&status='+this.value" <?= $row['status'] == 'completed' ? 'disabled' : '' ?>>
                                        <option value="pending" <?= $row['status'] == 'pending' ? 'selected' : '' ?>>Đang chờ</option>
                                        <option value="completed" <?= $row['status'] == 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                                        <option value="cancelled" <?= $row['status'] == 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                                    </select>
                                </td>
                                <td><?= empty($row['total_price']) ? 'Chưa xác định' : number_format($row['total_price']) . 'đ' ?></td>
                                <td><?= $row['created_at'] ?></td>
                                <td><?= $row['updated_at'] ?></td>
                                <td>
                                    <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Bạn có chắc muốn xóa đơn này?')" style="color:#f87171;">🗑️</a>
                                    <a href="order_detail.php?id=<?= $row['id'] ?>">✏️</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="10" style="text-align:center;">Không có dữ liệu</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
