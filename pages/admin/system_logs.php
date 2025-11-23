<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TechFixPHP/pages/public_page/login.php'); exit;
}
include '../../config/db.php';

// Phân trang
$limit = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Lấy dữ liệu log
$sql = "SELECT * FROM system_logs ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Đếm tổng
$total = $conn->query("SELECT COUNT(*) FROM system_logs")->fetch_row()[0];
$pages = ceil($total / $limit);

include __DIR__ . '/template/sidebar.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nhật ký hệ thống - TechFix</title>
    <link href="/TechFixPHP/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .main-content { margin-left: 250px; padding: 20px; }
        .table th { background: #343a40; color: white; }
        .log-admin { color: #dc3545; font-weight: bold; }
        .log-tech { color: #0d6efd; font-weight: bold; }
        .log-customer { color: #198754; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 sidebar p-0 collapse d-md-block">
                <?php include __DIR__ . '/template/sidebar.php'; ?>
            </div>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 main-content">
                <h2 class="mb-4"><i class="fa-solid fa-shield-halved"></i> Nhật Ký Hoạt Động Hệ Thống</h2>
                
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Thời gian</th>
                                    <th>Người thực hiện</th>
                                    <th>Vai trò</th>
                                    <th>Hành động</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= date('H:i:s d/m/Y', strtotime($row['created_at'])) ?></td>
                                        <td><?= htmlspecialchars($row['user_name']) ?> (ID: <?= $row['user_id'] ?>)</td>
                                        <td>
                                            <?php if($row['role']=='admin') echo '<span class="log-admin">ADMIN</span>'; ?>
                                            <?php if($row['role']=='technical') echo '<span class="log-tech">KỸ THUẬT</span>'; ?>
                                            <?php if($row['role']=='customer') echo '<span class="log-customer">KHÁCH</span>'; ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($row['action']) ?>
                                            <?php if($row['target_id'] > 0) echo " <b>[Mục tiêu #{$row['target_id']}]</b>"; ?>
                                        </td>
                                        <td class="text-muted small"><?= $row['ip_address'] ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if($pages > 1): ?>
                    <div class="card-footer">
                        <ul class="pagination justify-content-center mb-0">
                            <?php for($i=1; $i<=$pages; $i++): ?>
                                <li class="page-item <?= ($i==$page)?'active':'' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>