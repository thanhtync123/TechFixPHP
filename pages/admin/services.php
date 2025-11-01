<?php
include '../../config/db.php';
include 'template/sidebar.php';
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
if (isset($_POST['save'])) {
    $id = intval($_POST['id']);
    $name = $_POST['name'];
    $price = $_POST['price'];
    $unit = $_POST['unit'];
    $description = $_POST['description'];
    try {
        if ($id > 0)
            $query = "UPDATE `hometech_db`.`services` 
       SET `name` = '$name', `description` = '$description', `price` = $price, `unit` = '$unit' 
       WHERE (`id` = $id);  ";
        else
            $query = "INSERT INTO `services` (`name`, `description`, `price`, `unit`) 
                VALUES ('$name', '$description', $price, '$unit');";
        mysqli_query($conn, $query);
        header('Location: services.php');
        exit;
    } catch (mysqli_sql_exception $e) {
        echo '<script>alert(' . $e->getMessage(). ')</script>';
    }
}
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $query = "DELETE FROM services where id=$id";
    mysqli_query($conn, $query);

    header('Location: services.php');
    exit;
    echo '<script>alert(' . $query . ');</script>';
}
$edit = ['id' => 0, 'name' => '', 'price' => 0, 'unit' => '', 'description' => ''];
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $result = mysqli_query($conn, "SELECT * FROM services where id=$id");
    $edit = mysqli_fetch_assoc($result);
}


$query = "SELECT * FROM services order by id desc";
$result = mysqli_query($conn, $query);
?>
<link href="/TechFixPHP/assets/css/service_ad.css" rel="stylesheet">
<main class="p-4">
    <h1 class="mb-3">Quản lý dịch vụ</h1>
    <form method="post">
        <input type="text" name='id' value="<?= $edit['id'] ?>">
        <input type="text" name='name' placeholder='tên dịch vụ' value="<?= $edit['name'] ?>">
        <input type="text" name='price' placeholder='giá' value="<?= empty($edit['price']) ? '0' : $edit['price']  ?>">
        <select name="unit" required>
            <option value="">-- Chọn đơn vị --</option>
            <option value="cái" <?= $edit['unit'] == 'cái' ? 'selected' : '' ?>>cái</option>
            <option value="lần" <?= $edit['unit'] == 'lần' ? 'selected' : '' ?>>lần</option>
            <option value="giờ" <?= $edit['unit'] == 'giờ' ? 'selected' : '' ?>>giờ</option>
            <option value="điểm" <?= $edit['unit'] == 'điểm' ? 'selected' : '' ?>>điểm</option>
            <option value="bộ" <?= $edit['unit'] == 'bộ' ? 'selected' : '' ?>>bộ</option>

        </select>
        <textarea name="description" id="" placeholder="mô tả" rows="3" cols="30"><?= $edit['description'] ?></textarea>
        <button name="save">lưu</button>
    </form>
    <table id='servicesTable'>
        <thead>
            <tr>
                <th>id</th>
                <th>Tên Dịch Vụ</th>
                <th>Mô Tả</th>
                <th>Giá</th>
                <th>Đơn Vị</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= $row['name'] ?></td>
                        <td><?= $row['description'] ?></td>
                        <td><?= $row['price'] ?></td>
                        <td><?= $row['unit'] ?></td>
                        <td>
                            <a href="services.php?delete=<?= $row['id'] ?>">xóa</a>
                            <a href="services.php?edit=<?= $row['id'] ?>">sửa</a>
                        </td>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">Không có dữ liệu</td>
                    </tr>
                <?php endif; ?>
                </tr>
        </tbody>
    </table>
</main>
<script src="../../assets/js/datatable-vn.js"></script>
<script>
    $(function() {
        $('#servicesTable').DataTable();
    });
</script>