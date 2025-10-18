<?php
include '../../config/db.php';
include 'template/sidebar.php';
$msg = '';
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        mysqli_query($conn, "DELETE FROM equipments WHERE id = $id");
        $msg = 'Xóa thành công';
    } catch (Exception $e) {
        if (str_contains($e->getMessage(), "foreign key constraint"))
            $msg = 'Sản phẩm này đang có liên kết không xóa được';
    }
}
$img = '';
if (isset($_POST['save'])) {
    $id = intval($_POST['id']);
    $name = $_POST['name'];
    $unit = $_POST['unit'];
    $quantity = $_POST['quantity'];
    $description = $_POST['description'];
    $price = intval($_POST['price']);
    $img = $_FILES['img']['name'];                     // tên file
    $tmp = $_FILES['img']['tmp_name'];                 // đường dẫn file tạm
    $target = "../../assets/image/" . basename($img);  // nơi lưu trong dự án
    move_uploaded_file($tmp, $target);
    try {
        if ($id > 0) {
            if (!empty($img)) {
                $query = "UPDATE `equipments` 
                    SET `name` = '$name', `img`= '$img', `unit` = '$unit', `price` = $price, `quantity` = $quantity, `description` = '$description' 
                    WHERE (`id` = '$id');";
                mysqli_query($conn, $query);
            } else {
                $query = "UPDATE `equipments` 
                    SET `name` = '$name', `unit` = '$unit', `price` = $price, `quantity` = $quantity, `description` = '$description' 
                    WHERE (`id` = '$id');";
                mysqli_query($conn, $query);
            }
        } else if ($id = 0) {
            $img_sql = !empty($img) ? "'$img'" : "NULL";
            $query = "INSERT INTO `equipments` (`name`, `unit`, `price`, `quantity`, `description`,`img`) 
            VALUES ('$name', '$unit',$price , $quantity, '$description',$img_sql);";
            mysqli_query($conn, $query);
        }
    } catch (mysqli_sql_exception $e) {
        $msg = $e;
    }
    header('Location: equipments.php');
    exit;
}
$edit = ['id' => 0, 'name' => '', 'img' => '', 'unit' => '', 'price' => 0, 'quantity' => '', 'description' => ''];

if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $result = mysqli_query($conn, "SELECT * FROM equipments WHERE id=$id");
    $edit = mysqli_fetch_assoc($result);
}
$result = mysqli_query($conn, "SELECT * FROM equipments ORDER BY id DESC");

?>
<main class="p-4">
    <h1 class="mb-3">Quản lý thiết bị</h1>
    <?php echo $msg ?>
    <?= $img ?>
    <form action="equipments.php" method="post" enctype="multipart/form-data">
        <div><input type="text" name="id" placeholder="ID" readonly value=<?= $edit['id'] ?>></div>
        <div><input type="text" name="name" placeholder="Tên thiết bị" value="<?= htmlspecialchars($edit['name']) ?>"></div>
        <div><input type="file" name="img" id="imgInput" placeholder="Hình ảnh" value=<?= $edit['img'] ?> accept="image/*"></div>
        <img
            id="preview"
            src="<?= !empty($edit['img']) ? '../../assets/image/' . $edit['img'] : '' ?>"
            alt="Xem trước ảnh"
            style="max-width:200px; <?= !empty($edit['img']) ? 'display:block;' : 'display:none;' ?> border:1px solid #ccc; padding:5px; border-radius:8px;">

        <div><input type="text" name="unit" placeholder="Đơn vị" value=<?= $edit['unit'] ?>></div>
        <div><input type="text" name="price" placeholder="Giá" value=<?= $edit['price'] ?>></div>
        <div><input type="text" name="quantity" placeholder="Số lượng" value=<?= $edit['quantity'] ?>></div>
        <div><textarea name="description" placeholder="Mô tả" rows="3" cols="30"><?= $edit['description'] ?></textarea></div>
        <button name='save'>Thêm</button>
    </form>
    <table id="equipmenstTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên thiết bị</th>
                <th>Hình ảnh</th>
                <th>Đơn vị</th>
                <th>Giá</th>
                <th>Số lượng</th>
                <th>Mô tả</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= $row['name'] ?></td>
                        <td>
                            <?php if (empty($row['img'])): ?>
                                Chưa có ảnh
                            <?php else: ?>
                                <img src="../../assets/image/<?= $row['img'] ?>" alt="" width="100px">
                            <?php endif; ?>
                        </td>
                        <td><?= $row['unit'] ?></td>
                        <td><?= $row['price'] ?></td>
                        <td><?= $row['quantity'] ?></td>
                        <td><?= $row['description'] ?></td>
                        <td>
                            <a href="equipments.php?delete=<?= $row['id'] ?>">Xoá</a> |
                            <a href="equipments.php?edit=<?= $row['id'] ?>" onclick="editBtnClick()">Sửa</a>
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
        $('#equipmenstTable').DataTable();
    });
</script>
<script>
    document.getElementById('imgInput').addEventListener('change', function(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('preview');
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';

            }
            reader.readAsDataURL(file);
        } else {
            preview.src = '#';
            preview.style.display = 'none';
        }
    });

</script>