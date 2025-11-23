<?php
session_start();

// 1️⃣ KIỂM TRA QUYỀN
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TechFixPHP/pages/public_page/login.php');
    exit;
}

// 2️⃣ KẾT NỐI DB & XỬ LÝ LOGIC
include '../../config/db.php';

$msg = '';
$msg_type = ''; // success, danger

try {
    // --- Xử lý LƯU (Thêm/Sửa) ---
    if (isset($_POST['save'])) {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $price = floatval($_POST['price']);
        $unit = trim($_POST['unit']);
        $description = trim($_POST['description']);
        
        // Upload ảnh (nếu có) - Code nâng cao thêm
        // (Ở đây mình giữ logic cơ bản của bạn trước)

        if ($id > 0) {
            // UPDATE
            $stmt = $conn->prepare("UPDATE services SET name=?, description=?, price=?, unit=? WHERE id=?");
            $stmt->bind_param("ssdsi", $name, $description, $price, $unit, $id);
            $action = "Cập nhật";
        } else {
            // INSERT
            $stmt = $conn->prepare("INSERT INTO services (name, description, price, unit) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssds", $name, $description, $price, $unit);
            $action = "Thêm mới";
        }
        
        if ($stmt->execute()) {
            header("Location: services.php?status=success&action=$action");
            exit;
        } else {
            throw new Exception($stmt->error);
        }
        $stmt->close();
    }

    // --- Xử lý XÓA ---
    if (isset($_GET['delete'])) {
        $id = intval($_GET['delete']);
        
        // Kiểm tra ràng buộc khóa ngoại trước (tránh lỗi chết trang)
        $check = $conn->query("SELECT COUNT(*) as cnt FROM bookings WHERE service_id = $id");
        $row = $check->fetch_assoc();
        if($row['cnt'] > 0) {
            $msg = "Không thể xóa: Dịch vụ này đang có đơn hàng!";
            $msg_type = "warning";
        } else {
            $stmt = $conn->prepare("DELETE FROM services WHERE id=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $msg = "Đã xóa dịch vụ thành công!";
                $msg_type = "success";
            } else {
                $msg = "Lỗi xóa: " . $stmt->error;
                $msg_type = "danger";
            }
            $stmt->close();
        }
    }

} catch (Exception $e) {
    $msg = "Lỗi hệ thống: " . $e->getMessage();
    $msg_type = "danger";
}

// Nhận thông báo từ Redirect
if(isset($_GET['status']) && $_GET['status'] == 'success') {
    $msg = ($_GET['action'] ?? 'Thao tác') . ' thành công!';
    $msg_type = 'success';
}

// 3️⃣ LẤY DỮ LIỆU ĐỂ HIỂN THỊ
$edit = ['id' => 0, 'name' => '', 'price' => '', 'unit' => '', 'description' => ''];
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM services WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) $edit = $row;
    $stmt->close();
}

$result = mysqli_query($conn, "SELECT * FROM services ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Dịch vụ - Admin</title>
    <link href="/TechFixPHP/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { min-height: 100vh; background: #343a40; color: white; }
        .sidebar a { color: rgba(255,255,255,.8); text-decoration: none; padding: 12px 20px; display: block; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar a:hover { background: #0d6efd; color: white; }
        .card-header { background: white; font-weight: bold; border-bottom: 2px solid #f0f0f0; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        
        <div class="col-md-3 col-lg-2 sidebar p-0 collapse d-md-block">
            <?php include __DIR__ . '/template/sidebar.php'; ?>
        </div>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fa-solid fa-screwdriver-wrench"></i> Quản lý Dịch vụ Sửa chữa</h1>
            </div>

            <?php if (!empty($msg)): ?>
                <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-info"></i> <?= $msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header text-primary">
                            <i class="fa-solid fa-pen-to-square"></i> <?= ($edit['id'] > 0) ? 'Cập nhật dịch vụ' : 'Thêm dịch vụ mới' ?>
                        </div>
                        <div class="card-body">
                            <form action="services.php" method="post">
                                <input type="hidden" name="id" value="<?= $edit['id'] ?>">

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tên dịch vụ <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" required placeholder="VD: Vệ sinh máy lạnh" value="<?= htmlspecialchars($edit['name']) ?>">
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label fw-bold">Giá tiền (VNĐ)</label>
                                        <input type="number" name="price" class="form-control" required value="<?= htmlspecialchars($edit['price']) ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-bold">Đơn vị tính</label>
                                        <select name="unit" class="form-select" required>
                                            <option value="">-- Chọn --</option>
                                            <?php 
                                            $units = ['cái', 'lần', 'máy', 'bộ', 'giờ', 'm2', 'điểm'];
                                            foreach($units as $u) {
                                                $selected = ($edit['unit'] == $u) ? 'selected' : '';
                                                echo "<option value='$u' $selected>$u</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Mô tả chi tiết</label>
                                    <textarea name="description" class="form-control" rows="4" placeholder="Mô tả quy trình, phạm vi công việc..."><?= htmlspecialchars($edit['description']) ?></textarea>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" name="save" class="btn btn-primary fw-bold">
                                        <i class="fa-solid fa-floppy-disk"></i> Lưu Dịch Vụ
                                    </button>
                                    <?php if($edit['id'] > 0): ?>
                                        <a href="services.php" class="btn btn-outline-secondary">Hủy bỏ</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <i class="fa-solid fa-list-ul"></i> Danh sách dịch vụ hiện có
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="servicesTable" class="table table-hover table-bordered align-middle">
                                    <thead class="table-light text-center">
                                        <tr>
                                            <th width="5%">ID</th>
                                            <th>Tên dịch vụ</th>
                                            <th width="30%">Mô tả</th>
                                            <th width="15%">Giá</th>
                                            <th width="10%">ĐVT</th>
                                            <th width="15%">Tác vụ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($result) > 0): ?>
                                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                                <tr>
                                                    <td class="text-center fw-bold"><?= $row['id'] ?></td>
                                                    <td class="fw-bold text-primary"><?= htmlspecialchars($row['name']) ?></td>
                                                    <td style="min-width: 250px;">
    <small class="text-muted" style="white-space: normal; display: block;">
        <?= htmlspecialchars($row['description']) ?>
    </small>
</td>
                                                    <td class="text-end fw-bold text-success">
                                                        <?= number_format($row['price'], 0, ',', '.') ?> đ
                                                    </td>
                                                    <td class="text-center"><?= htmlspecialchars($row['unit']) ?></td>
                                                    <td class="text-center">
                                                        <a href="services.php?edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning" title="Sửa">
                                                            <i class="fa-solid fa-pen"></i>
                                                        </a>
                                                        <a href="services.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" title="Xóa" onclick="return confirm('Bạn chắc chắn muốn xóa dịch vụ này?');">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div> </main>
    </div>
</div>

<script src="/TechFixPHP/assets/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#servicesTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
            },
            columnDefs: [
                { orderable: false, targets: [5] } // Tắt sắp xếp cột Tác vụ
            ]
        });
    });
</script>

</body>
</html>