<?php
session_start();

// 1. Kiểm tra quyền (Admin & Technical đều vào được)
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'technical')) {
    header('Location: /TechFixPHP/pages/public_page/login.php'); exit;
}

include '../../config/db.php';

// --- XỬ LÝ CẬP NHẬT TRẠNG THÁI ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action']; // 'accepted' hoặc 'rejected'
    
    if (in_array($action, ['accepted', 'rejected'])) {
        $stmt = $conn->prepare("UPDATE warranty_requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $action, $id);
        $stmt->execute();
        
        // Nếu Chấp nhận -> Có thể tự động tạo một đơn booking mới giá 0đ (Logic nâng cao)
        // Ở đây tạm thời chỉ đổi trạng thái.
        
        header("Location: warranty_claims.php?msg=updated"); exit;
    }
}

// --- LẤY DỮ LIỆU ---
// Join bảng bookings và services để lấy tên khách, sđt, tên dịch vụ
$sql = "
    SELECT 
        r.*, 
        b.customer_name, 
        b.phone, 
        b.address,
        b.appointment_time as original_date,
        s.name as service_name
    FROM warranty_requests r
    JOIN bookings b ON r.booking_id = b.id
    LEFT JOIN services s ON b.service_id = s.id
    ORDER BY r.created_at DESC
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Bảo hành - TechFix</title>
    <link href="/TechFixPHP/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { min-height: 100vh; background: #343a40; color: white; }
        .sidebar a { color: rgba(255,255,255,.8); text-decoration: none; padding: 12px 20px; display: block; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar a:hover { background: #0d6efd; color: white; }
        
        /* Badge trạng thái */
        .badge-pending { background: #ffc107; color: #000; }
        .badge-accepted { background: #28a745; color: #fff; }
        .badge-rejected { background: #dc3545; color: #fff; }
        
        .card-reason { background: #f8f9fa; border-left: 4px solid #0d6efd; padding: 10px; font-style: italic; color: #555; margin-top: 5px; }
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
                <h1 class="h2"><i class="fa-solid fa-shield-cat"></i> Yêu Cầu Bảo Hành</h1>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#ID</th>
                                    <th>Khách hàng</th>
                                    <th>Dịch vụ gốc</th>
                                    <th width="30%">Lý do bảo hành</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày gửi</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?= $row['id'] ?></strong><br><small class="text-muted">Đơn cũ: #<?= $row['booking_id'] ?></small></td>
                                            <td>
                                                <?= htmlspecialchars($row['customer_name']) ?><br>
                                                <small><i class="fa-solid fa-phone"></i> <?= $row['phone'] ?></small>
                                            </td>
                                            <td>
                                                <span class="text-primary"><?= htmlspecialchars($row['service_name']) ?></span><br>
                                                <small class="text-muted">Làm ngày: <?= date('d/m/Y', strtotime($row['original_date'])) ?></small>
                                            </td>
                                            <td>
                                                <div class="card-reason">
                                                    "<?= htmlspecialchars($row['reason']) ?>"
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                    $stt = $row['status'];
                                                    $cls = 'badge-pending';
                                                    $txt = 'Đang chờ';
                                                    if ($stt == 'accepted') { $cls = 'badge-accepted'; $txt = 'Đã duyệt'; }
                                                    if ($stt == 'rejected') { $cls = 'badge-rejected'; $txt = 'Từ chối'; }
                                                ?>
                                                <span class="badge <?= $cls ?>"><?= $txt ?></span>
                                            </td>
                                            <td><?= date('d/m H:i', strtotime($row['created_at'])) ?></td>
                                            <td>
                                                <?php if ($row['status'] == 'pending'): ?>
                                                    <a href="warranty_claims.php?action=accepted&id=<?= $row['id'] ?>" 
                                                       class="btn btn-sm btn-success" title="Chấp nhận"
                                                       onclick="return confirm('Duyệt bảo hành đơn này? Thợ sẽ cần liên hệ khách.')">
                                                        <i class="fa-solid fa-check"></i>
                                                    </a>
                                                    <a href="warranty_claims.php?action=rejected&id=<?= $row['id'] ?>" 
                                                       class="btn btn-sm btn-danger" title="Từ chối"
                                                       onclick="return confirm('Từ chối yêu cầu này?')">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted"><i class="fa-solid fa-lock"></i> Đã xử lý</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center py-4">Chưa có yêu cầu bảo hành nào.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="/TechFixPHP/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>