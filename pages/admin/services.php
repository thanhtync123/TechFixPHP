<?php
session_start();

// ✅ Kiểm tra đăng nhập & quyền admin trước khi xuất HTML
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TechFixPHP/pages/public_page/login.php');
    exit;
}

include '../../config/db.php';
include __DIR__ . '/template/sidebar.php';

// ===================== XỬ LÝ THÊM / SỬA DỊCH VỤ =====================
if (isset($_POST['save'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $unit = trim($_POST['unit']);
    $description = trim($_POST['description']);

    try {
        if ($id > 0) {
            $query = "UPDATE `hometech_db`.`services` 
                      SET `name` = '$name', `description` = '$description', 
                          `price` = $price, `unit` = '$unit' 
                      WHERE (`id` = $id)";
        } else {
            $query = "INSERT INTO `services` (`name`, `description`, `price`, `unit`) 
                      VALUES ('$name', '$description', $price, '$unit')";
        }
        mysqli_query($conn, $query);
        header('Location: services.php');
        exit;
    } catch (mysqli_sql_exception $e) {
        echo '<script>alert("Lỗi: ' . addslashes($e->getMessage()) . '")</script>';
    }
}

// ===================== XỬ LÝ XÓA DỊCH VỤ =====================
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $query = "DELETE FROM services WHERE id = $id";
    mysqli_query($conn, $query);
    header('Location: services.php');
    exit;
}

// ===================== LẤY DỮ LIỆU ĐỂ CHỈNH SỬA =====================
$edit = ['id' => 0, 'name' => '', 'price' => 0, 'unit' => '', 'description' => ''];
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $result = mysqli_query($conn, "SELECT * FROM services WHERE id = $id");
    $edit = mysqli_fetch_assoc($result);
}

// ===================== LẤY DANH SÁCH DỊCH VỤ =====================
$query = "SELECT * FROM services ORDER BY id DESC";
$result = mysqli_query($conn, $query);
?>

<!-- ===================== GIAO DIỆN ===================== -->
<link href="/TechFixPHP/assets/css/service_ad.css" rel="stylesheet">

<main class="p-4">
    <h1 class="mb-3">Quản lý dịch vụ</h1>

    <!-- Form thêm/sửa -->
    <form method="post" class="mb-4">
        <input type="hidden" name="id" value="<?= $edit['id'] ?>">

        <input type="text" name="name" placeholder="Tên dịch vụ" 
               value="<?= htmlspecialchars($edit['name']) ?>" required>

        <input type="number" name="price" placeholder="Giá" 
               value="<?= htmlspecialchars($edit['price'] ?: 0) ?>" required>

        <select name="unit" required>
            <option value="">-- Chọn đơn vị --</option>
            <option value="cái" <?= $edit['unit'] == 'cái' ? 'selected' : '' ?>>cái</option>
            <option value="lần" <?= $edit['unit'] == 'lần' ? 'selected' : '' ?>>lần</option>
            <option value="giờ" <?= $edit['unit'] == 'giờ' ? 'selected' : '' ?>>giờ</option>
            <option value="điểm" <?= $edit['unit'] == 'điểm' ? 'selected' : '' ?>>điểm</option>
            <option value="bộ" <?= $edit['unit'] == 'bộ' ? 'selected' : '' ?>>bộ</option>
        </select>

        <textarea name="description" placeholder="Mô tả" rows="3" cols="30"><?= htmlspecialchars($edit['description']) ?></textarea>

        <button name="save" type="submit">💾 Lưu</button>
    </form>

    <!-- Bảng dịch vụ -->
    <table id="servicesTable">
        <thead>
            <tr>
                <th>ID</th>
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
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td><?= htmlspecialchars($row['price']) ?></td>
                        <td><?= htmlspecialchars($row['unit']) ?></td>
                        <td>
                            <a href="services.php?edit=<?= $row['id'] ?>">✏️ Sửa</a> |
                            <a href="services.php?delete=<?= $row['id'] ?>" 
                               onclick="return confirm('Bạn có chắc chắn muốn xóa dịch vụ này?')">🗑️ Xóa</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">Không có dữ liệu</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</main>

<script src="../../assets/js/datatable-vn.js"></script>
<script>
    $(function() {
        $('#servicesTable').DataTable();
    });
</script>
