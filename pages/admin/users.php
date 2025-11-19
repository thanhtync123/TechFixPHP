<?php
session_start();
include '../../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TechFixPHP/pages/public_page/login.php');
    exit;
}

$msg = '';
$msgType = '';
if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8');
    $msgType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $role = $_POST['role'] ?? 'customer';

    if ($id > 0) {
        if ($password !== '') {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, password = ?, address = ?, role = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param('sssssi', $name, $phone, $hashed, $address, $role, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ?, role = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param('ssssi', $name, $phone, $address, $role, $id);
        }
        $stmt->execute();
        $stmt->close();
        header("Location: users.php?msg=" . urlencode("✏️ Đã cập nhật người dùng #$id thành công!"));
        exit;
    } else {
        if ($password === '') {
            $msg = "❌ Vui lòng nhập mật khẩu.";
            $msgType = 'error';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, phone, password, address, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssss', $name, $phone, $hashed, $address, $role);
            $stmt->execute();
            $newId = $stmt->insert_id;
            $stmt->close();
            header("Location: users.php?msg=" . urlencode("✅ Đã thêm người dùng mới thành công! (ID #{$newId})"));
            exit;
        }
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    try {
        $stmt->execute();
        $msg = "🗑️ Đã xoá người dùng #$id thành công!";
        $msgType = 'success';
    } catch (mysqli_sql_exception $e) {
        if (str_contains($e->getMessage(), 'foreign key constraint')) {
            $msg = "⚠️ Không thể xoá người dùng #$id vì đang được sử dụng ở bảng khác!";
            $msgType = 'warning';
        } else {
            $msg = "❌ Lỗi khi xoá người dùng: " . $e->getMessage();
            $msgType = 'error';
        }
    }
    $stmt->close();
}

$edit = ['id'=>0,'name'=>'','phone'=>'','password'=>'','address'=>'','role'=>'customer'];
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT id, name, phone, address, role FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $edit = $row + ['password' => ''];
    }
    $stmt->close();
}

$result = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");

include __DIR__ . '/template/sidebar.php';
?>
<link href="/TechFixPHP/assets/css/users.css" rel="stylesheet">
<main class="p-4">
  <h1 class="mb-3">Quản lý người dùng</h1>

  <?php if ($msg): ?>
    <?php
    $styles = [
        'success' => 'background:#d4edda;color:#155724;',  
        'warning' => 'background:#fff3cd;color:#856404;',  
        'error'   => 'background:#f8d7da;color:#721c24;' 
    ];
    ?>
    <div style="padding:10px;border-radius:6px;margin-bottom:10px;<?= $styles[$msgType] ?? '' ?>">
      <?= $msg ?>
    </div>
  <?php endif; ?>

  <form method="post" class="mb-4">
    <div><input name="id" readonly value="<?= $edit['id'] ?>"></div>
    <div><input name="name" placeholder="Họ tên" value="<?= $edit['name'] ?>"></div>
    <div><input name="phone" placeholder="SĐT" value="<?= $edit['phone'] ?>"></div>
    <div><input name="password" placeholder="Mật khẩu" value="<?= $edit['password'] ?>"></div>
    <div><input name="address" placeholder="Địa chỉ" value="<?= $edit['address'] ?>"></div>
    <div>
      <select name="role">
        <option value="customer" <?= $edit['role']=='customer' ? 'selected':'' ?>>Khách hàng</option>
        <option value="admin" <?= $edit['role']=='admin' ? 'selected':'' ?>>Quản trị viên</option>
        <option value="technical" <?= $edit['role']=='technical' ? 'selected':'' ?>>Kỹ thuật</option>
      </select>
    </div>
    <button name="save">Lưu</button>
  </form>

  <table id="userTable">
    <thead>
      <tr>
        <th>ID</th><th>Họ tên</th><th>SĐT</th><th>Mật khẩu</th>
        <th>Địa chỉ</th><th>Vai trò</th><th>Ngày tạo</th><th>Cập nhật</th><th>Hành động</th>
      </tr>
    </thead>
    <tbody>
      <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td><?= $row['name'] ?></td>
          <td><?= $row['phone'] ?></td>
          <td><?= $row['password'] ?></td>
          <td><?= $row['address'] ?></td>
          <td><?= $row['role'] ?></td>
          <td><?= $row['created_at'] ?></td>
          <td><?= $row['updated_at'] ?></td>
          <td>
            <a href="users.php?delete=<?= $row['id'] ?>">Xoá</a> |
            <a href="users.php?edit=<?= $row['id'] ?>">Sửa</a>
          </td>
        </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="9" class="text-center">Không có dữ liệu</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</main>

<script src="../../assets/js/datatable-vn.js"></script>
<script>
    $(function() {
        $('#userTable').DataTable();
    });
</script>