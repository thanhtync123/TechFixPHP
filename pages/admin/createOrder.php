<?php
include '../../config/db.php';
include 'template/sidebar.php';

$result = mysqli_query($conn, "select * from users where role = 'customer' ");
?>
<main class="p-4">
    <h1 class="mb-3">Tạo mới đơn hàng</h1>
    <table>
        <thead>
            <tr>
                <th>Mã KH</th>
                <th>Tên KH</th>
                <th>SĐT</th>
                <th>Địa chỉ</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= $row['name'] ?></td>
                        <td><?= $row['phone'] ?></td>
                        <td><?= $row['address'] ?></td>
                        <td onclick="cellClick()">Chọn</td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">Không có dữ liệu</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="infoCustomer">
        <div> Mã KH<input type="text" name="id" id=""></div>
        <div> Họ tên<input type="text" name="name" id=""></div>
        <div> SĐT<input type="text" name="phone" id=""></div>
        <div> Địa chỉ<input type="text" name="address" id=""></div>
    </div>


</main>
<style>
    table {
        margin-bottom: 0;
        /* bỏ khoảng trống dưới bảng */
        border-collapse: collapse;
        /* gộp viền cho đẹp */
    }

    .infoCustomer {
        margin-left: 600px;
        margin-top: 0;
        /* cho sát lên trên */
    }
</style>
<script>
    function cellClick() {

    }
</script>