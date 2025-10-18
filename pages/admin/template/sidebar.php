<!-- templates/sidebar.php -->
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<aside class="sidebar">
  <div class="sidebar-logo">
    <h2>Admin</h2>
  </div>

  <ul class="sidebar-menu">
    <li><a href="dashboard.php" >📊 Dashboard</a></li>
    <li><a href="users.php">👥 Người dùng</a></li>
    <li><a href="equipments.php">📦 Sản phẩm</a></li>
    <li><a href="orders.php">🧾 Đơn hàng</a></li>
       <li><a href="services.php">🧾 Dịch vụ</a></li>
    <li><a href="settings.php">⚙️ Cài đặt</a></li>
    <li><a href="../index.php">🏠 Về trang chủ</a></li>
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
    background: #1e293b; /* xanh đậm */
    color: #fff;
    display: flex;
    flex-direction: column;
  }

  .sidebar-logo {
    text-align: center;
    padding: 20px 0;
    font-size: 1.3rem;
    font-weight: bold;
    background: #111827;
    border-bottom: 1px solid #334155;
  }

  .sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .sidebar-menu li a {
    display: block;
    color: #cbd5e1;
    padding: 12px 20px;
    text-decoration: none;
    transition: background 0.2s, color 0.2s;
  }

  .sidebar-menu li a:hover,
  .sidebar-menu li a.active {
    background: #334155;
    color: #fff;
  }

  /* Để main lệch sang phải */
  main {
    margin-left: 220px;
    padding: 20px;
  }
</style>
<script>

</script>