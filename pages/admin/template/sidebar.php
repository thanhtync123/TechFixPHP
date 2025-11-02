<?php
// templates/sidebar.php

// KHÔNG GỌI session_start() ở đây.
// File cha (dashboard.php, admin_dispatch.php...) phải gọi session_start() TRƯỚC KHI include file này.

// Lấy vai trò (role) và tên
$role = $_SESSION['role'] ?? null;
$name = $_SESSION['name'] ?? 'Guest';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>


<aside class="sidebar">
    <div class="sidebar-logo">
        
        <?php if ($role === 'admin'): ?>
            <h2>Admin Panel</h2>
        <?php elseif ($role === 'technical'): ?>
            <h2>Technician</h2>
        <?php else: ?>
            <h2>TECHFIX</h2>
        <?php endif; ?>

        <?php
        if (!isset($_SESSION['name']) || empty($_SESSION['name'])) {
            echo '<p><a href="/TechFixPHP/pages/public_page/login.php" class="text-light">Đăng nhập</a></p>';
        } else {
            echo '<p>Chào ' . htmlspecialchars($name) . '</p>';
        }
        ?>
    </div>

    <ul class="sidebar-menu">
        
        <?php if ($role === 'admin'): ?>
            <li><a href="/TechFixPHP/pages/admin/dashboard.php">📊 Dashboard</a></li>
            <li><a href="/TechFixPHP/pages/admin/admin_dispatch.php">🚀 Phân Công Việc</a></li>
            <li><a href="/TechFixPHP/pages/admin/admin_calendar.php">🗓️ Lịch làm việc</a></li>
            <li><a href="/TechFixPHP/pages/admin/users.php">👥 Người dùng</a></li>
            <li><a href="/TechFixPHP/pages/admin/equipments.php">📦 Sản phẩm </a></li>
            <li><a href="/TechFixPHP/pages/admin/orders.php">🧾 Đơn hàng </a></li>
          
            <li><a href="/TechFixPHP/pages/admin/services.php">🛠️ Dịch vụ</a></li>

        <?php elseif ($role === 'technical'): ?>
            <li>
                <a href="/TechFixPHP/pages/technical/tech_schedule.php">
                    📅 Lịch làm việc
                </a>
            </li>
            <li>
                <a href="/TechFixPHP/pages/technical/tech_history.php">
                    📚 Lịch sử công việc
                </a>
            </li>
        <?php endif; ?>

        <hr style="border-color: #334155; margin: 10px;">
        <li><a href="/TechFixPHP/pages/public_page/logout.php?action=logout">⚙️ Đăng xuất</a></li>
        <li><a href="/TechFixPHP/index.php">🏠 Về trang chủ</a></li>
    </ul>
</aside>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="appToast" class="toast align-items-center text-bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage">Lưu thành công!</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
    function showToast(message = "Thành công!", type = "success") {
        const toastEl = document.getElementById("appToast");
        const toastBody = document.getElementById("toastMessage");
        toastEl.className = `toast align-items-center text-bg-${type} border-0`;
        toastBody.textContent = message;

        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    }
</script>

<style>
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: 220px;
        height: 100vh;
        background: #1e293b;
        color: #fff;
        display: flex;
        flex-direction: column;
        z-index: 1000;
    }

    .sidebar-logo {
        text-align: center;
        padding: 20px 0;
        font-size: 1.3rem;
        font-weight: bold;
        background: #111827;
        border-bottom: 1px solid #334155;
    }
    
    .sidebar-logo p {
        font-size: 0.9rem;
        font-weight: normal;
        color: #cbd5e1;
        margin: 5px 0 0;
    }

    .sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
        flex-grow: 1; /* Cho menu lấp đầy */
        overflow-y: auto; /* Cho phép cuộn nếu menu quá dài */
    }

    .sidebar-menu li a {
        display: block;
        color: #cbd5e1;
        padding: 14px 20px;
        text-decoration: none;
        transition: background 0.2s, color 0.2s;
        border-left: 4px solid transparent;
    }

    .sidebar-menu li a:hover,
    .sidebar-menu li a.active { 
        background: #334155;
        color: #fff;
        border-left-color: #3b82f6; /* Màu xanh highlight */
    }

    /* Đẩy 3 link cuối (hr, logout, home) xuống dưới */
    .sidebar-menu hr {
         margin-top: auto; /* Đẩy HR xuống cuối */
    }
    .sidebar-menu li:nth-last-child(-n+2) {
         margin-top: 0;
         border-top: 1px solid #334155;
    }


    /* Quan trọng: CSS này phải được áp dụng cho nội dung chính
      của BẤT KỲ trang nào include sidebar này.
      (Ví dụ: dashboard.php nên có <main class="main-content">)
    */
    .main-content, main {
        margin-left: 220px;
        padding: 20px;
    }
</style>