<?php
session_start();

// ✅ Kiểm tra quyền admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TechFixPHP/pages/public_page/login.php');
    exit;
}

include '../../config/db.php';
include __DIR__ . '/template/sidebar.php';

// --- Xử lý cập nhật trạng thái đơn hàng (Đã bảo mật) ---
if (isset($_GET['update']) && isset($_GET['id']) && isset($_GET['status'])) {
    $id = intval($_GET['id']);
    $status = $_GET['status'];
    
    // Chỉ cho phép các trạng thái hợp lệ
    $allowed_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (in_array($status, $allowed_statuses)) {
        $query = "UPDATE bookings SET status=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
    }
    // Chuyển hướng về trang sạch (không có ?update=)
    header("Location: orders.php");
    exit;
}

// --- Xử lý xóa đơn hàng ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $query = "DELETE FROM bookings WHERE id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    header("Location: orders.php");
    exit;
}

// --- Lọc theo trạng thái (Đã bảo mật) ---
$params = [];
$where = '';
$sort = $_GET['sort'] ?? 'all'; // Lấy trạng thái lọc

if ($sort !== 'all' && $sort !== '') {
    $where = "WHERE b.status = ?";
    $params[] = $sort; // Thêm tham số cho bind_param
}

// --- Lấy dữ liệu đơn hàng (ĐÃ SỬA SANG LEFT JOIN) ---
// LEFT JOIN đảm bảo đơn hàng vẫn hiển thị ngay cả khi khách hàng hoặc dịch vụ đã bị xóa
$query = "
    SELECT 
        b.id,
        IFNULL(u.name, '[Khách đã xóa]') AS customer_name,
        IFNULL(s.name, '[Dịch vụ đã xóa]') AS service_name,
        DATE_FORMAT(b.appointment_time, '%d/%m/%Y %H:%i') AS appointment_time,
        b.status,
        b.created_at
    FROM bookings b
    LEFT JOIN users u ON b.customer_id = u.id
    LEFT JOIN services s ON b.service_id = s.id
    $where
    ORDER BY b.id DESC
";

$stmt = $conn->prepare($query);

// Gắn tham số (nếu có)
if (!empty($params)) {
    // 's' vì status là string (varchar/enum)
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng</title>
    <link href="/TechFixPHP/assets/css/orders.css" rel="stylesheet">
    <style>
        body {
            font-family: "Poppins", sans-serif;
            background-color: #f7f9fc;
        }
        .main-content {
            margin-left: 250px; /* Độ rộng của sidebar */
            padding: 20px;
        }
        h2 {
            color: #333;
        }
        .filters select {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 14px; /* Tăng padding */
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #0099ff;
            color: #fff;
            text-transform: uppercase;
            font-size: 13px;
        }
        tr:hover {
            background: #f1f7ff;
        }
        select {
            padding: 8px 12px; /* Thống nhất padding */
            border-radius: 6px;
            border: 1px solid #ccc;
            background: #fff;
        }
        .btn {
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
        }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn:hover { opacity: 0.85; }

        /* CSS cho dropdown trạng thái */
        .status-select {
            font-weight: bold;
            border-radius: 20px; /* Bo tròn */
            padding: 5px 10px;
            border: 1px solid #ccc;
        }
        .status-pending { background: #fff3cd; color: #664d03; border-color: #ffe69c; }
        .status-confirmed { background: #cce5ff; color: #004085; border-color: #b8daff; }
        .status-completed { background: #d4edda; color: #155724; border-color: #c3e6cb; }
        .status-cancelled { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        
        .header {
             display: flex;
             justify-content: space-between;
             align-items: center;
             margin-bottom: 20px;
        }

    </style>
</head>
<body>
    <div class="main-content">
        <header class="header">
            <div class="header-title">
                <h2>📦 Quản lý đơn hàng (Bookings)</h2>
                <p>Theo dõi và xử lý các đơn đặt dịch vụ</p>
            </div>
            <div class="header-actions">
                <a href="orders.php?sort=all" class="btn btn-secondary">🔄 Làm mới</a>
            </div>
        </header>

        <div class="filters">
            <form method="get" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:center;">
                <label>Trạng thái:</label>
                <select name="sort" onchange="this.form.submit()">
                    <option value="all" <?= ($sort == 'all') ? 'selected' : '' ?>>Tất cả</option>
                    <option value="pending" <?= ($sort == 'pending') ? 'selected' : '' ?>>Đang chờ</option>
                    <option value="confirmed" <?= ($sort == 'confirmed') ? 'selected' : '' ?>>Đã xác nhận</option>
                    <option value="completed" <?= ($sort == 'completed') ? 'selected' : '' ?>>Hoàn thành</option>
                    <option value="cancelled" <?= ($sort == 'cancelled') ? 'selected' : '' ?>>Đã hủy</option>
                </select>
            </form>
        </div>

        <div class="orders-table">
            <table>
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Khách hàng</th>
                        <th>Dịch vụ</th>
                        <th>Ngày hẹn</th>
                        <th>Trạng thái</th>
                        <th>Ngày đặt</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td><?= htmlspecialchars($row['service_name']) ?></td>
                                <td><?= $row['appointment_time'] ?></td>
                                <td>
                                    <select 
                                        class="status-select status-<?= htmlspecialchars($row['status']) ?>" 
                                        onchange="window.location='orders.php?update=1&id=<?= $row['id'] ?>&status='+this.value" 
                                        <?= $row['status'] == 'completed' || $row['status'] == 'cancelled' ? 'disabled' : '' ?>
                                    >
                                        <option value="pending" <?= $row['status'] == 'pending' ? 'selected' : '' ?>>Đang chờ</option>
                                        <option value="confirmed" <?= $row['status'] == 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                                        <option value="completed" <?= $row['status'] == 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                                        <option value="cancelled" <?= $row['status'] == 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                                    </select>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <a href="orders.php?delete=<?= $row['id'] ?>" 
                                       onclick="return confirm('Bạn có chắc muốn xóa đơn này?')" 
                                       style="color:#e74c3c; text-decoration: none; font-size: 1.2rem;">
                                       🗑️
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; padding: 20px;">Không có đơn hàng nào khớp với bộ lọc.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>