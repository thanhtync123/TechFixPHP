<?php
session_start();
include '../config/db.php';

// ✅ Kiểm tra đăng nhập
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'customer') {
    header("Location: /TechFixPHP/pages/public_page/login.php");
    exit();
}

$customer_id = $_SESSION['user']['id'];
$booking_id = $_GET['booking_id'] ?? 0;

// ✅ Kiểm tra đơn hàng hợp lệ & thuộc khách hàng này
$query = "SELECT * FROM bookings WHERE id = ? AND customer_id = ? AND status = 'completed'";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $customer_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("<p style='text-align:center;color:red;'>Không tìm thấy đơn hàng hoặc chưa hoàn thành.</p>");
}

// ✅ Kiểm tra đã đánh giá chưa
$check = $conn->prepare("SELECT * FROM reviews WHERE booking_id = ? AND customer_id = ?");
$check->bind_param("ii", $booking_id, $customer_id);
$check->execute();
$review = $check->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$review) {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    $insert = $conn->prepare("INSERT INTO reviews (booking_id, customer_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
    $insert->bind_param("iiis", $booking_id, $customer_id, $rating, $comment);
    $insert->execute();

    echo "<script>alert('Cảm ơn bạn đã đánh giá!'); window.location='my_booking.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Đánh giá dịch vụ - TECHFIX</title>
<link rel="stylesheet" href="../../assets/css/customer.css">
<style>
body {font-family:'Poppins',sans-serif;background:#f4f6fa;margin:0;padding:40px;}
.container {
    max-width:600px;margin:auto;background:white;padding:30px;
    border-radius:10px;box-shadow:0 3px 10px rgba(0,0,0,0.1);
}
h2{text-align:center;margin-bottom:20px;color:#333;}
.stars input {display:none;}
.stars label {font-size:30px;color:#ccc;cursor:pointer;}
.stars input:checked ~ label, .stars label:hover, .stars label:hover ~ label {color:#ffcc00;}
textarea {
    width:100%;height:100px;border:1px solid #ccc;border-radius:5px;
    padding:10px;font-family:inherit;resize:none;
}
button {
    background:#0099ff;color:white;border:none;padding:10px 20px;
    border-radius:5px;margin-top:10px;cursor:pointer;
}
button:hover {background:#007acc;}
.note{text-align:center;color:#555;margin-top:10px;}
</style>
</head>
<body>
<div class="container">
    <h2>Đánh giá dịch vụ</h2>

    <?php if ($review): ?>
        <p class="note">Bạn đã đánh giá đơn hàng này rồi:</p>
        <p>⭐ <?= str_repeat('⭐', $review['rating']) ?></p>
        <p><?= htmlspecialchars($review['comment']) ?></p>
    <?php else: ?>
    <form method="post">
        <div class="stars" style="text-align:center;">
            <input type="radio" name="rating" value="5" id="star5" required><label for="star5">⭐</label>
            <input type="radio" name="rating" value="4" id="star4"><label for="star4">⭐</label>
            <input type="radio" name="rating" value="3" id="star3"><label for="star3">⭐</label>
            <input type="radio" name="rating" value="2" id="star2"><label for="star2">⭐</label>
            <input type="radio" name="rating" value="1" id="star1"><label for="star1">⭐</label>
        </div>

        <textarea name="comment" placeholder="Nhận xét của bạn..."></textarea>
        <button type="submit">Gửi đánh giá</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
