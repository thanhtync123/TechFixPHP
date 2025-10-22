<?php
include '../../config/db.php';
include 'template/sidebar.php';

$msg = '';
$msgType = ''; 
if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    $msgType = 'success';
}
if (isset($_POST['save'])) {
    $id = intval($_POST['id']); 
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $address = $_POST['address'];
    $role = $_POST['role'];

    if ($id > 0) {
        $query = "UPDATE users 
                  SET name='$name', phone='$phone', password='$password', 
                      address='$address', role='$role', updated_at=NOW()
                  WHERE id=$id";
        mysqli_query($conn, $query);
        header("Location: users.php?msg=" . urlencode("✏️ Đã cập nhật người dùng #$id thành công!"));
        exit;
    } else {
        $query = "INSERT INTO users(name, phone, password, address, role)
                  VALUES ('$name', '$phone', '$password', '$address', '$role')";
        mysqli_query($conn, $query);
        header("Location: users.php?msg=" . urlencode("✅ Đã thêm người dùng mới thành công!"));
        exit;
    }
}
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        mysqli_query($conn, "DELETE FROM users WHERE id = $id");
        $msg = "🗑️ Đã xoá người dùng #$id thành công!";
        $msgType = 'success';
    } catch (mysqli_sql_exception $e) {
        $err = $e->getMessage();
        if (str_contains($err, 'foreign key constraint')) {
            $msg = "⚠️ Không thể xoá người dùng #$id vì đang được sử dụng ở bảng khác!";
            $msgType = 'warning';
        } else {
            $msg = "❌ Lỗi khi xoá người dùng: " . $err;
            $msgType = 'error';
        }
    }
}

$edit = ['id'=>0,'name'=>'','phone'=>'','password'=>'','address'=>'','role'=>''];
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $result = mysqli_query($conn, "SELECT * FROM users WHERE id=$id");
    $edit = mysqli_fetch_assoc($result);
}
$result = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
?>

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