<?php
include '../../config/db.php';
include 'template/sidebar.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = $_POST['orderId'] ?? '';
    $customerName = $_POST['customerName'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $serviceName = $_POST['serviceName'] ?? '';
    $servicePrice = intval($_POST['servicePrice'] ?? 0);
    $technicalName = $_POST['technicalName'] ?? '';
    $scheduleTime = $_POST['scheduleTime'] ?? '';
    $total = intval($_POST['total'] ?? 0);
    $equipments = json_decode($_POST['equipments'] ?? '[]', true);
}
?>

<main class="p-4">
    <h2>HÓA ĐƠN THANH TOÁN</h2>
    <p><strong>Mã đơn hàng:</strong> <?= htmlspecialchars($orderId) ?></p>
    <p><strong>Ngày in:</strong> <?= date("d/m/Y H:i") ?></p>
    <p><strong>Thời gian hẹn:</strong> <?= htmlspecialchars($scheduleTime) ?></p>

    <hr>

    <h3>Thông tin khách hàng</h3>
    <p><strong>Tên khách hàng:</strong> <?= htmlspecialchars($customerName) ?></p>
    <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($phone) ?></p>
    <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($address) ?></p>

    <hr>

    <h3>Thông tin dịch vụ</h3>
    <p><strong>Dịch vụ:</strong> <?= htmlspecialchars($serviceName) ?></p>
    <p><strong>Giá dịch vụ:</strong> <?= number_format($servicePrice) ?> đ</p>

    <hr>

    <h3>Kỹ thuật viên:</h3>
    <p><?= htmlspecialchars($technicalName) ?></p>

    <hr>

    <h3>Danh sách thiết bị</h3>
    <table  cellspacing="0" cellpadding="5">
        <tr>
            <th>Tên thiết bị</th>
            <th>Đơn giá (đ)</th>
            <th>Số lượng</th>
            <th>Thành tiền (đ)</th>
        </tr>
        <?php 
        $totalEquip = 0;
        foreach ($equipments as $eq) {
            $sum = intval($eq['price']) * intval($eq['quantity']);
            $totalEquip += $sum;
            echo "<tr>
                    <td>".htmlspecialchars($eq['name'])."</td>
                    <td>".number_format($eq['price'])."</td>
                    <td>".htmlspecialchars($eq['quantity'])."</td>
                    <td>".number_format($sum)."</td>
                  </tr>";
        }
        ?>
    </table>

    <hr>

    <p><strong>Tổng tiền thiết bị:</strong> <?= number_format($totalEquip) ?> đ</p>
    <p><strong>Tổng tiền dịch vụ:</strong> <?= number_format($servicePrice) ?> đ</p>
    <h3>TỔNG CỘNG: <?= number_format($total) ?> đ</h3>

    <br>
    <p><em>Cảm ơn quý khách đã sử dụng dịch vụ!</em></p>
</main>