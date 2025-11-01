<?php
session_start();
// Check admin authentication
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TechFixPHP/pages/public_page/login.php');
    exit;
}

include '../../config/db.php';

include __DIR__ . '/template/sidebar.php';

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
   <link href="/TechFixPHP/assets/css/orders.css" rel="stylesheet">
</head>
<body>
    <!-- MAIN -->
    <div class="main-content">
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
    </div>
