<?php
session_start();

require_once '../../config/db.php';

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? null) !== 'technical') {
    die('Bạn không có quyền truy cập trang này.');
}

$message = "";

function uploadImage(array $file, string $dir)
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes, true)) {
        return null;
    }
    if ($file['size'] > 2 * 1024 * 1024) { // 2MB
        return null;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    try {
        $random = bin2hex(random_bytes(4));
    } catch (Throwable $e) {
        $random = uniqid();
    }
    $safeName = time() . '_' . $random . '.' . strtolower($ext);
    $target = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $safeName;
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = (int) ($_POST['booking_id'] ?? 0);
    $status = $_POST['status'] ?? 'confirmed';

    $upload_dir = realpath(__DIR__ . '/../../assets/uploads');
    if ($upload_dir === false) {
        $upload_dir = __DIR__ . '/../../assets/uploads';
        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
            $message = "<div style='color:red'>Lỗi: Không thể tạo thư mục lưu ảnh.</div>";
        }
    }

    $photo_before_path = "";
    $photo_after_path = "";

    if (!empty($_FILES['photo_before']['name'])) {
        $uploaded = uploadImage($_FILES['photo_before'], $upload_dir);
        if ($uploaded) {
            $photo_before_path = $uploaded;
        } else {
            $message .= "<div style='color:red'>Ảnh hiện trạng không hợp lệ.</div>";
        }
    }

    if (!empty($_FILES['photo_after']['name'])) {
        $uploaded = uploadImage($_FILES['photo_after'], $upload_dir);
        if ($uploaded) {
            $photo_after_path = $uploaded;
        } else {
            $message .= "<div style='color:red'>Ảnh sau sửa không hợp lệ.</div>";
        }
    }

    if ($booking_id > 0) {
        $sql = "UPDATE bookings SET status = ?";
        $params = [$status];
        $types = "s";

        if ($photo_before_path) {
            $sql .= ", photo_before = ?";
            $params[] = $photo_before_path;
            $types .= "s";
        }
        if ($photo_after_path) {
            $sql .= ", photo_after = ?";
            $params[] = $photo_after_path;
            $types .= "s";
        }

        $sql .= " WHERE id = ? AND technician_id = ?";
        $params[] = $booking_id;
        $params[] = (int) $_SESSION['user']['id'];
        $types .= "ii";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $message .= "<div style='color:green; font-weight:bold; margin-bottom:15px;'>✅ Cập nhật đơn hàng #$booking_id thành công!</div>";
        } else {
            $message .= "<div style='color:red'>Lỗi SQL: " . $conn->error . "</div>";
        }
        $stmt->close();
    } else {
        $message .= "<div style='color:red'>Mã đơn hàng không hợp lệ.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Cổng thông tin Kỹ thuật viên - TECHFIX</title>
    <link rel="stylesheet" href="../../assets/css/technician_upload.css">
</head>
<body>
    <div class="tech-card">
        <h2>🛠️ Cập nhật Đơn hàng</h2>
        <?= $message ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Mã đơn hàng (ID):</label>
                <input type="number" name="booking_id" required placeholder="Ví dụ: 25">
                <div class="note">Nhập ID đơn hàng cần cập nhật</div>
            </div>
            <div class="form-group">
                <label>Trạng thái mới:</label>
                <select name="status">
                    <option value="confirmed">Đã xác nhận (Confirmed)</option>
                    <option value="completed">Hoàn thành (Completed)</option>
                    <option value="cancelled">Hủy bỏ (Cancelled)</option>
                </select>
            </div>
            <div class="form-group">
                <label>📸 Ảnh Trước khi sửa (Hiện trạng):</label>
                <input type="file" name="photo_before" accept="image/*">
            </div>
            <div class="form-group">
                <label>✨ Ảnh Sau khi sửa (Kết quả):</label>
                <input type="file" name="photo_after" accept="image/*">
            </div>
            <button type="submit">Lưu Báo Cáo</button>
        </form>
    </div>
</body>
</html>