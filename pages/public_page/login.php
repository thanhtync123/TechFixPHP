<?php
include "../../config/db.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $rs_query = mysqli_query($conn, "SELECT * FROM users WHERE phone = '$phone'
    and password = '$password' ");
    $rs_query_arr = mysqli_fetch_assoc($rs_query);
    if (!$rs_query_arr)
        echo "Sai tài khoản hoặc mật khẩu";

    else {
        session_start();
        $_SESSION['phone'] = $phone;
        $_SESSION['name'] = $rs_query_arr['name'];
        $_SESSION['role'] = $rs_query_arr['role'];
        if ($_SESSION['role'] == 'admin') {
            header('Location: /TechFixPHP/pages/admin/dashboard.php');
            exit;
        }
    }
}


?>
<form action="login.php" method="POST">
    Tên đăng nhập<input type="text" name="phone">
    Mật khẩu <input type="text" name="password">
    <button type="submit">Đăng nhập</button>
</form>