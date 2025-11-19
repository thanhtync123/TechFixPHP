<?php
session_start();

// ✅ 1. KIỂM TRA QUYỀN VÀ INCLUDE DB (PHẢI LÀM TRƯỚC TIÊN)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TechFixPHP/pages/public_page/login.php');
    exit;
}

include '../../config/db.php';

// (Biến này để truyền thông báo xuống JS ở dưới)
$toastMessage = null;
$toastType = 'success';

// ==========================================================
// 2. XỬ LÝ TOÀN BỘ LOGIC (POST/GET) TRƯỚC KHI XUẤT HTML
// ==========================================================

try {
    // --- Xử lý cập nhật/thêm dịch vụ ---
    if (isset($_POST['save'])) {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $price = floatval($_POST['price']);
        $unit = trim($_POST['unit']);
        $description = trim($_POST['description']);

        // ✅ SỬA LỖI BẢO MẬT (Dùng Prepared Statements)
        if ($id > 0) {
            // Cập nhật
            $stmt = $conn->prepare("UPDATE services 
                                   SET name = ?, description = ?, price = ?, unit = ? 
                                   WHERE id = ?");
            $stmt->bind_param("ssdsi", $name, $description, $price, $unit, $id);
        } else {
            // Thêm mới
            $stmt = $conn->prepare("INSERT INTO services (name, description, price, unit) 
                                   VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssds", $name, $description, $price, $unit);
        }
        
        $stmt->execute();
        $stmt->close();
        
        $toastMessage = "Đã lưu dịch vụ thành công!"; // <-- Tạo thông báo

    }

    // --- Xử lý xóa dịch vụ ---
    if (isset($_GET['delete'])) {
        $id = intval($_GET['delete']);
        
        // ✅ SỬA LỖI BẢO MẬT
        $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        $toastMessage = "Đã xóa dịch vụ!"; // <-- Tạo thông báo
    }

} catch (mysqli_sql_exception $e) {
    // Bắt lỗi (ví dụ: trùng tên)
    $toastMessage = "Lỗi: " . $e->getMessage();
    $toastType = "danger"; // (Màu đỏ)
}

// ==========================================================
// 3. CHUẨN BỊ DỮ LIỆU CHO HTML (SAU KHI XỬ LÝ LOGIC)
// ==========================================================

// --- LẤY DỮ LIỆU ĐỂ CHỈNH SỬA ---
$edit = ['id' => 0, 'name' => '', 'price' => 0, 'unit' => '', 'description' => ''];
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    
    // ✅ SỬA LỖI BẢO MẬT
    $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    $edit_data = $result_edit->fetch_assoc();
    if ($edit_data) {
        $edit = $edit_data;
    }
    $stmt->close();
}

// --- LẤY DANH SÁCH DỊCH VỤ ---
$query = "SELECT * FROM services ORDER BY id DESC";
$result = mysqli_query($conn, $query); // (An toàn vì không có input từ user)


// ==========================================================
// 4. BẮT ĐẦU XUẤT HTML (SAU CÙNG)
// (Lỗi của bạn là đặt dòng 'include' này ở trên đầu)
// ==========================================================
include __DIR__ . '/template/sidebar.php'; 
?>

<link href="/TechFixPHP/assets/css/service_ad.css" rel="stylesheet">

<main class="main-content">
    <h1 class="mb-3">Quản lý dịch vụ</h1>

    <form method="post" class="mb-4" action="services.php"> <input type="hidden" name="id" value="<?= $edit['id'] ?>">

        <input type="text" name="name" placeholder="Tên dịch vụ" 
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

        <textarea name="description" placeholder="Mô tả" rows="3" cols="30"><?= htmlspecialchars($edit['description']) ?></textarea>

        <button name="save" type="submit">💾 Lưu</button>
        <?php if ($edit['id'] > 0): ?>
            <a href="services.php" class="btn-cancel">Hủy sửa</a>
        <?php endif; ?>
    </form>

    <table id="servicesTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên Dịch Vụ</th>
                <th>Mô Tả</th>
                <th>Giá</th>
                <th>Đơn Vị</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td><?= number_format($row['price']) ?> đ</td> <td><?= htmlspecialchars($row['unit']) ?></td>
                        <td>
                            <a href="services.php?edit=<?= $row['id'] ?>">✏️ Sửa</a> |
                            <a href="services.php?delete=<?= $row['id'] ?>" 
                               onclick="return confirm('Bạn có chắc chắn muốn xóa dịch vụ này?')">🗑️ Xóa</a>
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
        
        // === HIỂN THỊ THÔNG BÁO (TỰ ĐỘNG) ===
        <?php if ($toastMessage): ?>
            // Dùng hàm showToast() từ file sidebar.php của bạn
            showToast(<?php echo json_encode($toastMessage); ?>, <?php echo json_encode($toastType); ?>);
        <?php endif; ?>
    });
</script>