<?php
include '../../config/db.php';
include 'template/sidebar.php';

if (isset($_GET['update']) && isset($_GET['id']) && isset($_GET['status'])) {
    $id = intval($_GET['id']);
    $status = $_GET['status'];
    $query = "UPDATE orders 
               SET status='$status'
               WHERE id=$id";
    mysqli_query($conn, $query);
}
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $query = "DELETE FROM orders 
               WHERE id=$id";
    mysqli_query($conn, $query);
}
$where = '';
if (isset($_GET['sort'])) {
    $sort = $_GET['sort'];
    $sort == 'all' ?   $where = '' : $where =  "WHERE o.status = '$sort'";
    // echo "<script>alert('$sort');</script>";
}
$query = " SELECT 
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
<main class="p-4">
    <h1 class="mb-3">Quản lý đơn hàng</h1>
<a href="createOrder.php">Tạo mới đơn hàng</a>


    Lọc:
    <select onchange="window.location='?sort='+this.value">
        <option value="all" <?= (isset($_GET['sort']) && $_GET['sort'] == 'all') ? 'selected' : '' ?>>Tất cả</option>
        <option value="pending" <?= (isset($_GET['sort']) && $_GET['sort'] == 'pending') ? 'selected' : '' ?>>Đang chờ</option>
        <option value="completed" <?= (isset($_GET['sort']) && $_GET['sort'] == 'completed') ? 'selected' : '' ?>>Đã hoàn thành</option>
        <option value="cancelled" <?= (isset($_GET['sort']) && $_GET['sort'] == 'cancelled') ? 'selected' : '' ?>>Đã hủy</option>
    </select>
    <table id='ordersTable'>
        <thead>
            <th>Mã đơn hàng</th>
            <th>Tên KH</th>
            <th>Tên KTV</th>
            <th>Dịch vụ</th>
            <th>Thời gian hẹn</th>
            <th>Trạng thái</th>
            <th>Tổng tiền</th>
            <th>Thời gian tạo</th>
            <th>Thời gian cập nhật</th>
            <th>Thao tác</th>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= $row['customer_name'] ?></td>
                        <td><?= empty($row['technician_name']) ? 'Chưa xác định' : $row['technician_name'] ?></td>
                        <td><?= $row['service_name'] ?></td>
                        <td><?= $row['schedule_time'] ?></td>
                        <td>
                            <p style="display: none;"><?= $row['status'] ?></p>
                            <select onchange="window.location='?update=1&id=<?= $row['id'] ?>&status='+this.value" <?= $row['status'] == 'completed' ? 'disabled' : '' ?>>
                                <option value="pending" <?= $row['status'] == 'pending'   ? 'selected' : '' ?>>Đang chờ</option>
                                <option value="completed" <?= $row['status'] == 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                                <option value="cancelled" <?= $row['status'] == 'cancelled' ? 'selected' : '' ?>>Đã hủy đơn</option>
                            </select>
                        </td>
                        <td><?= empty($row['total_price']) ? 'Chưa xác định' : $row['total_price'] ?></td>
                        <td><?= $row['created_at'] ?></td>
                        <td><?= $row['updated_at'] ?></td>
                        <td>
                            <a href="orders.php?delete=<?= $row['id'] ?>"
                                style="<?= $row['status'] == 'completed' ? 'pointer-events: none; color: gray;' : '' ?> ">
                                Xoá
                            </a> |
                            <a href="" onclick="">Sửa</a>
                        </td>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">Không có dữ liệu</td>
                    </tr>
                <?php endif; ?>

        </tbody>
    </table>
</main>
<script src="../../assets/js/datatable-vn.js"></script>
<script>
    $(function() {
        $('#ordersTable').DataTable();
    });
</script>