<?php
session_start();

// 1️⃣ KIỂM TRA QUYỀN
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TechFixPHP/pages/public_page/login.php');
    exit;
}

// 2️⃣ KẾT NỐI DB
require_once '../../config/db.php';

// 3️⃣ XỬ LÝ LOGIC (DELETE)
$msg = '';
$msg_type = ''; // success, danger

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        // Lấy tên ảnh để xóa file vật lý nếu cần (tùy chọn)
        $res = mysqli_query($conn, "SELECT img FROM equipments WHERE id = $id");
        $row = mysqli_fetch_assoc($res);
        
        if(mysqli_query($conn, "DELETE FROM equipments WHERE id = $id")) {
            // Nếu muốn xóa cả file ảnh thì uncomment dòng dưới
            // if(!empty($row['img']) && file_exists("../../assets/image/" . $row['img'])) unlink("../../assets/image/" . $row['img']);
            
            $msg = 'Xóa thiết bị thành công!';
            $msg_type = 'success';
        }
    } catch (Exception $e) {
        if (str_contains($e->getMessage(), "foreign key constraint")) {
            $msg = 'Không thể xóa: Sản phẩm này đang được sử dụng trong đơn hàng.';
        } else {
            $msg = 'Lỗi: ' . $e->getMessage();
        }
        $msg_type = 'danger';
    }
}

// 4️⃣ XỬ LÝ LOGIC (INSERT / UPDATE)
if (isset($_POST['save'])) {
    $id = intval($_POST['id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $unit = mysqli_real_escape_string($conn, $_POST['unit']);
    $quantity = intval($_POST['quantity']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = intval($_POST['price']);
    
    // Upload ảnh
    $img = '';
    if(isset($_FILES['img']) && $_FILES['img']['error'] == 0) {
        $ext = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
        $new_name = time() . '_' . uniqid() . '.' . $ext; // Đổi tên file để tránh trùng
        $target = "../../assets/image/" . $new_name;
        
        if(move_uploaded_file($_FILES['img']['tmp_name'], $target)) {
            $img = $new_name;
        }
    }

    try {
        if ($id > 0) {
            // UPDATE
            if (!empty($img)) {
                $query = "UPDATE equipments SET name=?, img=?, unit=?, price=?, quantity=?, description=? WHERE id=?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'sssiisi', $name, $img, $unit, $price, $quantity, $description, $id);
            } else {
                $query = "UPDATE equipments SET name=?, unit=?, price=?, quantity=?, description=? WHERE id=?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'ssiisi', $name, $unit, $price, $quantity, $description, $id);
            }
            $action_msg = 'Cập nhật';
        } else {
            // INSERT
            $query = "INSERT INTO equipments (name, unit, price, quantity, description, img) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'ssiiss', $name, $unit, $price, $quantity, $description, $img);
            $action_msg = 'Thêm mới';
        }
        
        if (mysqli_stmt_execute($stmt)) {
            // Redirect để tránh gửi lại form khi F5
            header("Location: equipments.php?status=success&action=$action_msg");
            exit;
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } catch (Exception $e) {
        $msg = 'Lỗi: ' . $e->getMessage();
        $msg_type = 'danger';
    }
}

// Nhận thông báo từ Redirect
if(isset($_GET['status']) && $_GET['status'] == 'success') {
    $msg = ($_GET['action'] ?? 'Thao tác') . ' thành công!';
    $msg_type = 'success';
}

// 5️⃣ CHUẨN BỊ DỮ LIỆU FORM (NẾU EDIT)
$edit = ['id' => 0, 'name' => '', 'img' => '', 'unit' => '', 'price' => '', 'quantity' => '', 'description' => ''];
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $res_edit = mysqli_query($conn, "SELECT * FROM equipments WHERE id=$edit_id");
    if($res_edit && mysqli_num_rows($res_edit) > 0){
        $edit = mysqli_fetch_assoc($res_edit);
    }
}

// 6️⃣ LẤY DANH SÁCH HIỂN THỊ
$result = mysqli_query($conn, "SELECT * FROM equipments ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Thiết bị - Admin</title>
    <link href="/TechFixPHP/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { min-height: 100vh; background: #343a40; color: white; }
        .sidebar a { color: rgba(255,255,255,.8); text-decoration: none; padding: 12px 20px; display: block; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar a:hover { background: #0d6efd; color: white; }
        .img-preview { width: 100%; max-width: 150px; height: 150px; object-fit: cover; border-radius: 8px; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; margin-top: 10px; }
        .table img { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
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
                <h1 class="h2"><i class="fa-solid fa-box-open"></i> Quản lý Thiết bị & Vật tư</h1>
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
                            <i class="fa-solid fa-pen-to-square"></i> <?= ($edit['id'] > 0) ? 'Cập nhật thiết bị' : 'Thêm thiết bị mới' ?>
                        </div>
                        <div class="card-body">
                            <form action="equipments.php" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="id" value="<?= $edit['id'] ?>">

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tên thiết bị <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" required placeholder="VD: Dây điện Cadivi" value="<?= htmlspecialchars($edit['name']) ?>">
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label fw-bold">Đơn vị</label>
                                        <input type="text" name="unit" class="form-control" placeholder="VD: Mét, Cái" value="<?= htmlspecialchars($edit['unit']) ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-bold">Số lượng</label>
                                        <input type="number" name="quantity" class="form-control" required value="<?= htmlspecialchars($edit['quantity']) ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Giá nhập/bán (VNĐ)</label>
                                    <input type="number" name="price" class="form-control" required value="<?= htmlspecialchars($edit['price']) ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Hình ảnh</label>
                                    <input type="file" name="img" id="imgInput" class="form-control" accept="image/*">
                                    
                                    <div class="mt-2 text-center">
                                        <?php 
                                            $previewSrc = !empty($edit['img']) ? "../../assets/image/" . $edit['img'] : "";
                                            $display = !empty($edit['img']) ? "block" : "none";
                                        ?>
                                        <img id="preview" src="<?= $previewSrc ?>" class="img-preview mx-auto" style="display: <?= $display ?>;">
                                        <?php if(empty($edit['img'])): ?>
                                            <div id="preview-text" class="text-muted small mt-2">Chưa chọn ảnh</div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Mô tả chi tiết</label>
                                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($edit['description']) ?></textarea>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" name="save" class="btn btn-primary fw-bold">
                                        <i class="fa-solid fa-floppy-disk"></i> Lưu dữ liệu
                                    </button>
                                    <?php if($edit['id'] > 0): ?>
                                        <a href="equipments.php" class="btn btn-outline-secondary">Hủy cập nhật</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <i class="fa-solid fa-list"></i> Danh sách thiết bị hiện có
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="equipmentsTable" class="table table-hover table-bordered align-middle">
                                    <thead class="table-light text-center">
                                        <tr>
                                            <th width="5%">ID</th>
                                            <th width="10%">Ảnh</th>
                                            <th>Tên thiết bị</th>
                                            <th width="10%">ĐVT</th>
                                            <th width="15%">Giá</th>
                                            <th width="10%">SL</th>
                                            <th width="15%">Tác vụ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($result) > 0): ?>
                                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                                <tr>
                                                    <td class="text-center fw-bold"><?= $row['id'] ?></td>
                                                    <td class="text-center">
                                                        <?php if (!empty($row['img'])): ?>
                                                            <img src="../../assets/image/<?= htmlspecialchars($row['img']) ?>" alt="img">
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">No Image</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold"><?= htmlspecialchars($row['name']) ?></div>
                                                        <small class="text-muted text-truncate d-block" style="max-width: 200px;">
                                                            <?= htmlspecialchars($row['description']) ?>
                                                        </small>
                                                    </td>
                                                    <td class="text-center"><?= htmlspecialchars($row['unit']) ?></td>
                                                    <td class="text-end fw-bold text-success">
                                                        <?= number_format($row['price'], 0, ',', '.') ?> đ
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge <?= ($row['quantity'] > 0) ? 'bg-info' : 'bg-danger' ?>">
                                                            <?= $row['quantity'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="equipments.php?edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning" title="Sửa">
                                                            <i class="fa-solid fa-pen"></i>
                                                        </a>
                                                        <a href="equipments.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" title="Xóa" onclick="return confirm('Bạn chắc chắn muốn xóa thiết bị này?');">
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
    // Khởi tạo DataTable
    $(document).ready(function() {
        $('#equipmentsTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json' // Tiếng Việt cho bảng
            }
        });
    });

    // Xem trước ảnh khi upload
    document.getElementById('imgInput').addEventListener('change', function(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('preview');
        const previewText = document.getElementById('preview-text');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                if(previewText) previewText.style.display = 'none';
            }
            reader.readAsDataURL(file);
        } else {
            preview.src = '';
            preview.style.display = 'none';
            if(previewText) previewText.style.display = 'block';
        }
    });
</script>

</body>
</html>