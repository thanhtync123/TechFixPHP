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
$msg = '';
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        mysqli_query($conn, "DELETE FROM equipments WHERE id = $id");
        $msg = 'Xóa thành công';
    } catch (Exception $e) {
        if (str_contains($e->getMessage(), "foreign key constraint"))
            $msg = 'Sản phẩm này đang có liên kết không xóa được';
    }//121212
}
$img = '';
if (isset($_POST['save'])) {
    $id = intval($_POST['id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $unit = mysqli_real_escape_string($conn, $_POST['unit']);
    $quantity = intval($_POST['quantity']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = intval($_POST['price']);
    
    // Xử lý upload ảnh
    $img = '';
    if(isset($_FILES['img']) && $_FILES['img']['error'] == 0) {
        $img = $_FILES['img']['name'];
        $tmp = $_FILES['img']['tmp_name'];
        $target = "../../assets/image/" . basename($img);
        move_uploaded_file($tmp, $target);
    }

    try {
        if ($id > 0) {
            // Update existing product
            if (!empty($img)) {
                $query = "UPDATE equipments 
                         SET name = ?, img = ?, unit = ?, price = ?, quantity = ?, description = ? 
                         WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'sssiisi', $name, $img, $unit, $price, $quantity, $description, $id);
            } else {
                $query = "UPDATE equipments 
                         SET name = ?, unit = ?, price = ?, quantity = ?, description = ? 
                         WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'ssiisi', $name, $unit, $price, $quantity, $description, $id);
            }
        } else {
            // Insert new product
            $query = "INSERT INTO equipments (name, unit, price, quantity, description, img) 
                     VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'ssiiss', $name, $unit, $price, $quantity, $description, $img);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $msg = ($id > 0) ? 'Cập nhật thành công' : 'Thêm mới thành công';
            header('Location: equipments.php?msg=' . urlencode($msg));
            exit;
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } catch (Exception $e) {
        $msg = 'Lỗi: ' . $e->getMessage();
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

// Ensure $edit is always an array (prevent null/undefined warnings)
if (!is_array($edit)) {
    $edit = ['id' => 0, 'name' => '', 'img' => '', 'unit' => '', 'price' => 0, 'quantity' => '', 'description' => ''];
}
?>
<link href="/TechFixPHP/assets/css/equipments.css" rel="stylesheet">
<main class="p-4">
    <h1 class="mb-3">Quản lý thiết bị</h1>
    <?php if (!empty($msg)): ?>
        <p class="msg"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <form action="equipments.php" method="post" enctype="multipart/form-data">
        <div>
            <input type="text" name="id" placeholder="ID" readonly value="<?= htmlspecialchars($edit['id'] ?? 0) ?>">
        </div>

        <div>
            <input type="text" name="name" placeholder="Tên thiết bị" value="<?= htmlspecialchars($edit['name'] ?? '') ?>">
        </div>

        <div>
            <!-- remove value attribute for file input -->
            <input type="file" name="img" id="imgInput" placeholder="Hình ảnh" accept="image/*">
        </div>

        <?php
        $previewSrc = '';
        if (!empty($edit['img'])) {
            $previewSrc = htmlspecialchars('../../assets/image/' . $edit['img']);
        }
        ?>
        <img
            id="preview"
            src="<?= $previewSrc ?>"
            alt="Xem trước ảnh"
            style="max-width:200px; <?= $previewSrc ? 'display:block;' : 'display:none;' ?> border:1px solid #ccc; padding:5px; border-radius:8px;">

        <div>
            <input type="text" name="unit" placeholder="Đơn vị" value="<?= htmlspecialchars($edit['unit'] ?? '') ?>">
        </div>

        <div>
            <input type="text" name="price" placeholder="Giá" value="<?= htmlspecialchars($edit['price'] ?? 0) ?>">
        </div>

        <div>
            <input type="text" name="quantity" placeholder="Số lượng" value="<?= htmlspecialchars($edit['quantity'] ?? '') ?>">
        </div>

        <div>
            <textarea name="description" placeholder="Mô tả" rows="3" cols="30"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
        </div>

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