<?php
include '../../config/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['data']['id']);
    $service_id = intval($data['data']['service']);
    $technical_id = intval($data['data']['technical']);
    $schedule_time = $data['data']['schedule_time'];
    $status = $data['data']['status'];
    $total_price = $data['data']['total_price'];
    $idOrder = intval($data['data']['idOrder']);
    $equipment_arr = $data['data']['equipments'];
    try {
        $query = "INSERT INTO `hometech_db`.`orders`
     (`customer_id`, `service_id`, `technician_id`, `schedule_time`, `status`, `total_price`) 
    VALUES ($id, $service_id, $technical_id,'$schedule_time', '$status',$total_price);";
        mysqli_query($conn, $query);
        $idOrder = mysqli_insert_id($conn);
        foreach ($equipment_arr as $eq) {
            $equipmentId = intval($eq['id']);
            $equipmentQuantity = intval($eq['quantity']);
            $query = "INSERT INTO `orderequipments` (`order_id`, `equipment_id`, `quantity`) 
            VALUES ($idOrder, $equipmentId, $equipmentQuantity);";
            mysqli_query($conn, $query);
        }
        echo json_encode([
            'success' => true,
            'message' => 'Thêm thành công',
            'newIdOrder' => $idOrder
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'query' => $query
        ]);
    }

    exit;
}
$rs_equipments = mysqli_query($conn, "SELECT id, name, img, unit, price, quantity, description FROM equipments ");
$rs_services = mysqli_query($conn, "SELECT id,name,price FROM services order by name ASC ");
$rs_technical = mysqli_query($conn, "SELECT id,name FROM users where role = 'technical' order by name ASC ");
$rs_idOrder = mysqli_query($conn, "SELECT IFNULL(MAX(id),0)+1 AS next_id FROM orders");
$idOrderRow = mysqli_fetch_assoc($rs_idOrder);
$nextOrderId = $idOrderRow['next_id'];

$queryOldOrder = "";
$oldOrder = null;

if (isset($_GET['id'])) {
    $queryOldOrder = "SELECT 
        o.id AS order_id,
        uc.id AS customer_id,
        uc.name AS customer_name,
        uc.phone AS customer_phone,
        uc.address AS customer_address,
        s.id AS service_id,
        s.name AS service_name,
        s.price AS service_price,
        o.schedule_time AS schedule_time,
        ut.id AS technician_id,
        o.status
    FROM orders o
    LEFT JOIN users uc 
        ON o.customer_id = uc.id AND uc.role = 'customer'
    LEFT JOIN users ut 
        ON o.technician_id = ut.id AND ut.role = 'technical'
    LEFT JOIN services s 
        ON o.service_id = s.id
    WHERE o.id = " . intval($_GET['id']);

    $rs_oldOrderInfo = mysqli_query($conn, $queryOldOrder);
    $oldOrder = mysqli_fetch_assoc($rs_oldOrderInfo);
}

$result = mysqli_query($conn, "select * from users where role = 'customer' ");
?>
<?php include 'template/sidebar.php'; ?>
<main>
    <h1 class="mb-3">
        <?php echo isset($_GET['id']) ? 'Cập nhật đơn hàng' : 'Tạo mới đơn hàng'; ?>
    </h1>

    <div class="container-flex">
        <?php if (!isset($_GET['id'])): ?>
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
                                <td onclick="cellClick(
                        '<?= $row['id'] ?>',
                        '<?= $row['name'] ?>',
                        '<?= $row['phone'] ?>',
                        '<?= $row['address'] ?>')">+</td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">Không có dữ liệu</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Mã thiết bị</th>
                    <th>Tên thiết bị</th>
                    <th>Ảnh</th>
                    <th>Đơn vị</th>
                    <th>Đơn giá</th>
                    <th>Số lượng</th>
                    <th>Mô tả</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($rs_equipments) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($rs_equipments)): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= $row['name'] ?></td>
                            <td>
                                <?php if (empty($row['img'])): ?>
                                    Chưa có ảnh
                                <?php else: ?>
                                    <img src="../../assets/image/<?= $row['img'] ?>" alt="" width="70px">
                                <?php endif; ?>
                            </td>
                            <td><?= $row['unit'] ?></td>
                            <td><?= $row['price'] ?></td>
                            <td><?= $row['quantity'] ?></td>
                            <td><?= $row['description'] ?></td>
                            <td onclick="addEquipment
                            (
                            '<?= $row['id'] ?>'
                            ,'<?= $row['name'] ?>'
                            ,'<?= $row['img'] ?>'
                            ,'<?= $row['unit'] ?>'
                            ,'<?= $row['price'] ?>'
                            ,'<?= $row['quantity'] ?>'
                            ,'<?= $row['description'] ?>'
                            )
                            ">+</td>

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
            <div> Mã hóa đơn<input type="text" name="idOrder" id=""
                    value="<?= isset($_GET['id']) ? $_GET['id'] : $nextOrderId ?>">
            </div>
            <div>Mã KH
                <input type="text" name="id"
                    value="<?= isset($oldOrder) ? $oldOrder['customer_id'] : '' ?>">
            </div>

            <div>Họ tên
                <input type="text" name="name"
                    value="<?= isset($oldOrder) ? $oldOrder['customer_name'] : '' ?>">
            </div>

            <div>SĐT
                <input type="text" name="phone"
                    value="<?= isset($oldOrder) ? $oldOrder['customer_phone'] : '' ?>">
            </div>

            <div>Địa chỉ
                <input type="text" name="address"
                    value="<?= isset($oldOrder) ? $oldOrder['customer_address'] : '' ?>">
            </div>
            <div>Dịch vụ
                <select name="services" id="" onchange="selectServiceChange(this)">
                    <option value="">Chọn dịch vụ</option>
                    <?php while ($row = mysqli_fetch_assoc($rs_services)): ?>
                        <option value="<?= $row['id'] ?>"
                            data-price="<?= $row['price'] ?>"
                            <?= isset($oldOrder) && $oldOrder['service_id'] == $row['id'] ? 'selected' : '' ?>>
                            <?= $row['name'] . ' - ' . $row['price'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>Thời gian hẹn
                <input type="date" name="schedule_time"
                    value="<?= isset($oldOrder) ? date('Y-m-d', strtotime($oldOrder['schedule_time'])) : '' ?>">
            </div>
            <div>Kỹ thuật
                <select name="technical" id="">
                    <option value="">Chọn kỹ thuật viên</option>
                    <?php while ($row = mysqli_fetch_assoc($rs_technical)): ?>
                        <option value="<?= $row['id'] ?>"
                            <?= isset($oldOrder) && $oldOrder['technician_id'] == $row['id'] ? 'selected' : '' ?>>
                            <?= $row['name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>Trạng thái
                <select name="status" id="">
                    <option value="pending" <?= isset($oldOrder) && $oldOrder['status'] == 'pending' ? 'selected' : '' ?>>Đang chờ</option>
                    <option value="completed" <?= isset($oldOrder) && $oldOrder['status'] == 'completed' ? 'selected' : '' ?>>Đã xong</option>
                    <option value="cancelled" <?= isset($oldOrder) && $oldOrder['status'] == 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                </select>
            </div>
            <button type="button" onclick="submitOrder()">Lưu đơn hàng</button>
        </div>
        <div>
            <p>Tiền dịch vụ: <span id="total_price_service"><?= isset($oldOrder) ? $oldOrder['service_price'] : '' ?></span></p>
            <p>Tổng tiền sản phẩm: <span id='total_price_equipment'></span></p>
            <p>Tổng tiền: <span id='total_price'></span></p>
            <table class="tableE_C">
                <thead>
                    <tr>
                        <th>Mã thiết bị</th>
                        <th>Tên thiết bị</th>
                        <th>Ảnh</th>
                        <th>Đơn vị</th>
                        <th>Đơn giá</th>
                        <th>Số lượng</th>
                        <th>Mô tả</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>
<style>
    .container-flex {
        display: flex;
        align-items: flex-start;
        gap: 30px;
        margin-top: 20px;
    }

    .infoCustomer {
        display: flex;
        flex-direction: column;
        gap: 10px;
        min-width: 300px;
    }
</style>
<script>
    function cellClick(id, name, phone, address) {
        document.querySelector('input[name="id"]').value = id;
        document.querySelector('input[name="name"]').value = name;
        document.querySelector('input[name="phone"]').value = phone;
        document.querySelector('input[name="address"]').value = address;


    }

    function addEquipment(id, name, img, unit, price, quantity, description) {
        const tbody = document.querySelector('.tableE_C tbody');
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
        <td>${id}</td>
        <td>${name}</td>
        <td>${img ? `<img src='../../assets/image/${img}' width='70px'>` : 'Chưa có ảnh'}</td>
        <td>${unit}</td>
        <td class="price">${price}</td>
        <td><input type="number" value="1" min="1" max="${quantity}" style="width:60px;"></td>
        <td>${description}</td>
        <td><button type="button" onclick="this.parentElement.parentElement.remove()">Xóa</button></td>
    `;
        tbody.appendChild(newRow);
        newRow.querySelector('input[type="number"]').addEventListener('input', updateTotalEquipment);
        updateTotalEquipment();

    }

    function updateTotalEquipment() {
        let sum = 0;
        const rows = document.querySelectorAll('.tableE_C tbody tr');
        rows.forEach(row => {
            const price = parseInt(row.querySelector('.price')?.textContent || 0)
            const qtyInput = row.querySelector('input[type="number"]');
            const qty = parseInt(qtyInput?.value || 0);
            sum += price * qty;
        })
        document.getElementById('total_price_equipment').textContent = sum.toLocaleString() + ' ₫';
        const serviceText = document.getElementById('total_price_service').textContent.replace(/[^\d]/g, '');
        const equipmentText = document.getElementById('total_price_equipment').textContent.replace(/[^\d]/g, '');
        const service_price = parseInt(serviceText || 0);
        const equipment_price = parseInt(equipmentText || 0);
        const total_price = service_price + equipment_price;
        document.getElementById('total_price').textContent = total_price.toLocaleString();

    }

    function selectServiceChange(sel) {
        document.getElementById('total_price_service').textContent =
            parseInt(sel.selectedOptions[0].dataset.price).toLocaleString() + ' ₫';
        updateTotalEquipment();
    }

    function submitOrder() {
        const order = {
            id: document.querySelector('input[name="id"]').value,
            name: document.querySelector('input[name="name"]').value,
            service: document.querySelector('select[name="services"]').value,
            technical: document.querySelector('select[name="technical"]').value,
            schedule_time: document.querySelector('input[name="schedule_time"]').value,
            status: document.querySelector('select[name="status"]').value,
            total_price: parseInt(document.getElementById('total_price').textContent.replace(/[^\d]/g, '')) || 0,
            idOrder: document.querySelector('input[name="idOrder"]').value
        };

        const equipments_arr = [];
        const rows = document.querySelectorAll('.tableE_C tbody tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length > 0) {
                const qtyInput = row.querySelector('input[type="number"]');
                const qty = parseInt(qtyInput.value);
                equipments_arr.push({
                    id: cells[0].textContent,
                    quantity: qty
                });
            }
        });
        const data = {
            id: order.id,
            service: order.service,
            technical: order.technical,
            schedule_time: order.schedule_time,
            status: order.status,
            total_price: order.total_price,
            idOrder: order.idOrder,
            equipments: equipments_arr
        };
        console.log(data);

        fetch('createOrder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    data
                })
            })
            .then(res => res.json())
            .then(result => {
                console.log(result);
                if (result.success) {
                    showToast("Đơn hàng đã được lưu thành công!", "success");
                    document.getElementsByName('idOrder')[0].value = result.newIdOrder;
                } else {
                    showToast("Lỗi:" + result.message, "danger");
                }
            })
            .catch(err => console.error(err));

    }
</script>