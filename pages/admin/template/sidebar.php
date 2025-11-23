<?php
// templates/sidebar.php

// KHÔNG GỌI session_start() ở đây (đúng như bạn ghi chú).
// File cha phải gọi trước.

$role = $_SESSION['role'] ?? null;
$name = $_SESSION['name'] ?? 'Khách';

// Lấy tên file hiện tại để active menu
$current_page = basename($_SERVER['PHP_SELF']);
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">
            <i class="fa-solid fa-screwdriver-wrench"></i>
        </div>
        
        <?php if ($role === 'admin'): ?>
            <h3>ADMIN PANEL</h3>
        <?php elseif ($role === 'technical'): ?>
            <h3>KỸ THUẬT VIÊN</h3>
        <?php else: ?>
            <h3>TECHFIX</h3>
        <?php endif; ?>

        <div class="user-info">
            <span class="status-dot"></span>
            <?php
            if (!isset($_SESSION['name']) || empty($_SESSION['name'])) {
                echo '<a href="/TechFixPHP/pages/public_page/login.php" class="login-link">Đăng nhập</a>';
            } else {
                echo '<span>' . htmlspecialchars($name) . '</span>';
            }
            ?>
        </div>
    </div>

    <ul class="sidebar-menu">
        
        <?php if ($role === 'admin'): ?>
            <li>
                <a href="/TechFixPHP/pages/admin/dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="/TechFixPHP/pages/admin/admin_dispatch.php" class="<?= $current_page == 'admin_dispatch.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-truck-fast"></i> Điều Phối
                </a>
            </li>
            <li>
                <a href="/TechFixPHP/pages/admin/admin_calendar.php" class="<?= $current_page == 'admin_calendar.php' ? 'active' : '' ?>">
                    <i class="fa-regular fa-calendar-days"></i> Lịch Làm Việc
                </a>
            </li>
            <li>
                <a href="/TechFixPHP/pages/admin/users.php" class="<?= $current_page == 'users.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-users"></i> Người Dùng
                </a>
            </li>
            <li>
                <a href="/TechFixPHP/pages/admin/equipments.php" class="<?= $current_page == 'equipments.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-box-open"></i> Kho Thiết Bị
                </a>
            </li>
            <li>
                <a href="/TechFixPHP/pages/admin/kanban.php" class="<?= $current_page == 'orders.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-file-invoice"></i> Đơn Hàng
                </a>
            </li>
            <li>
                <a href="/TechFixPHP/pages/admin/services.php" class="<?= $current_page == 'services.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-list-check"></i> Dịch Vụ
                </a>
            </li>
            <li>
                <a href="/TechFixPHP/pages/admin/warranty_claims.php" class="<?= $current_page == 'warranty_claims.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-shield-cat"></i> Yêu Cầu Bảo Hành
                </a>
            </li>
<li class="nav-item">
    <a href="/TechFixPHP/pages/admin/system_logs.php" class="nav-link text-white">
        <i class="fa-solid fa-clock-rotate-left me-2"></i> Nhật Ký Hệ Thống
    </a>
</li>
        <?php elseif ($role === 'technical'): ?>
            <li>
                <a href="/TechFixPHP/pages/admin/tech_schedule.php" class="<?= $current_page == 'tech_schedule.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-calendar-check"></i> Lịch Của Tôi
                </a>
            </li>
            <li>
                <a href="/TechFixPHP/pages/admin/tech_market.php" class="<?= $current_page == 'tech_market.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-calendar-check"></i> Nhận lịch
                </a>
            </li>
            <li>
                <a href="/TechFixPHP/pages/admin/tech_history.php" class="<?= $current_page == 'tech_history.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-clock-rotate-left"></i> Lịch Sử
                </a>
            </li>
            <?php endif; ?>

        <li class="menu-divider"></li>
        
        <li>
            <a href="/TechFixPHP/index.php">
                <i class="fa-solid fa-house"></i> Trang Chủ
            </a>
        </li>
        <li>
            <a href="/TechFixPHP/pages/public_page/logout.php?action=logout" class="text-danger">
                <i class="fa-solid fa-right-from-bracket"></i> Đăng Xuất
            </a>
        </li>
    </ul>
</aside>

<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
    <div id="appToast" class="toast align-items-center text-bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage">Thành công!</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    function showToast(message = "Thành công!", type = "success") {
        const toastEl = document.getElementById("appToast");
        const toastBody = document.getElementById("toastMessage");
        
        // Đổi màu toast theo type (success/danger/warning)
        let bgClass = 'text-bg-success';
        if(type === 'error' || type === 'danger') bgClass = 'text-bg-danger';
        if(type === 'warning') bgClass = 'text-bg-warning text-dark';

        toastEl.className = `toast align-items-center border-0 ${bgClass}`;
        toastBody.textContent = message;

        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    }
</script>

<style>
    /* Reset & Font */
    @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');
    
    .sidebar {
        position: fixed;
        left: 0; top: 0; bottom: 0;
        width: 240px; /* Tăng chiều rộng xíu cho thoáng */
        background: #1e293b; /* Màu xám xanh đậm hiện đại */
        color: #fff;
        display: flex; flex-direction: column;
        z-index: 1000;
        box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        font-family: 'Roboto', sans-serif;
    }

    .sidebar-logo {
        text-align: center;
        padding: 25px 0;
        background: #0f172a;
        border-bottom: 1px solid #334155;
    }
    
    .logo-icon { font-size: 2rem; color: #3b82f6; margin-bottom: 5px; }
    
    .sidebar-logo h3 {
        font-size: 1.1rem; font-weight: 700; margin: 0;
        letter-spacing: 1px; color: #f8fafc;
    }
    
    .user-info {
        margin-top: 8px; font-size: 0.85rem; color: #94a3b8;
        display: flex; align-items: center; justify-content: center; gap: 6px;
    }
    .status-dot {
        width: 8px; height: 8px; background: #22c55e;
        border-radius: 50%; display: inline-block;
    }
    .login-link { color: #3b82f6; text-decoration: none; }

    .sidebar-menu {
        list-style: none; padding: 15px 0; margin: 0;
        flex-grow: 1; overflow-y: auto;
    }

    .sidebar-menu li a {
        display: flex; align-items: center; gap: 12px;
        color: #cbd5e1; padding: 12px 20px;
        text-decoration: none; font-size: 0.95rem;
        transition: all 0.2s ease;
        border-left: 4px solid transparent;
    }
    
    .sidebar-menu li a i { width: 20px; text-align: center; font-size: 1.1rem; }

    .sidebar-menu li a:hover,
    .sidebar-menu li a.active { 
        background: #334155;
        color: #fff;
        border-left-color: #3b82f6;
    }

    .menu-divider {
        height: 1px; background: #334155; margin: 10px 20px;
    }

    /* Utility Class cho nội dung chính */
    .main-content, main {
        margin-left: 240px; /* Khớp với width sidebar */
        padding: 30px;
        background-color: #f1f5f9; /* Nền xám nhạt cho nội dung */
        min-height: 100vh;
    }
    
    /* Responsive Mobile (Ẩn sidebar khi màn hình nhỏ) */
    @media (max-width: 768px) {
        .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
        .sidebar.show { transform: translateX(0); }
        .main-content, main { margin-left: 0; }
    }
</style>