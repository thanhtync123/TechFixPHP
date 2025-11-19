<?php
session_start();

// 🔒 Kiểm tra đăng nhập và quyền
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? null) !== 'customer') {
    header("Location: /TechFixPHP/pages/public_page/login.php");
    exit();
}

include '../config/db.php'; // Đường dẫn tới file db.php
$user = $_SESSION['user'] ?? null;
$customer_id = $user['id'] ?? null;

// 🧩 Biến mặc định
$result = false;

if ($customer_id && isset($conn)) {
    // ===== THAY ĐỔI 1: Thêm 'b.final_price' vào câu SELECT =====
    // (Giả sử cột giá của bạn trong bảng 'bookings' tên là 'final_price')
    $query = "
        SELECT 
            b.id, 
            s.name AS service_name, 
            b.appointment_time, 
            b.final_price,  
            b.status, 
            b.created_at
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        WHERE b.customer_id = ?
        ORDER BY b.created_at DESC
    ";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        die("<pre>Lỗi SQL: " . $conn->error . "</pre>");
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lịch đặt của tôi - TECHFIX</title>
<link rel="stylesheet" href="../../assets/css/customer.css">
<style>
body{background:#f5f6fa;font-family:'Poppins',sans-serif}
.container{max-width:1000px;margin:60px auto;background:#fff;border-radius:10px;padding:30px;box-shadow:0 3px 10px rgba(0,0,0,0.1);position:relative}
h2{text-align:center;color:#333;margin-bottom:20px}
table{width:100%;border-collapse:collapse;text-align:center}
th,td{padding:12px;border-bottom:1px solid #ddd}
th{background:#0099ff;color:#fff}
.status{padding:5px 12px;border-radius:8px;font-weight:600;display:inline-block;min-width:110px}
.pending{background:#ffeb3b;color:#333}
.confirmed{background:#4caf50;color:#fff}
.completed{background:#2196f3;color:#fff}
.cancelled{background:#f44336;color:#fff}
#notificationBell{position:fixed;top:20px;right:30px;font-size:28px;cursor:pointer;color:#0099ff}
#notificationBell .badge{background:red;color:#fff;font-size:12px;padding:2px 6px;border-radius:50%;position:absolute;top:-5px;right:-8px}
#notificationPopup{display:none;position:fixed;top:60px;right:30px;width:320px;background:#fff;border-radius:10px;box-shadow:0 5px 15px rgba(0,0,0,0.1);z-index:999}
#notificationPopup .list{max-height:300px;overflow-y:auto;padding:0;margin:0;list-style:none}
#notificationPopup .list li{padding:10px;border-bottom:1px solid #eee;font-size:14px}
#notificationPopup .list li.unread{background:#f0f8ff;font-weight:700}
.no-booking{text-align:center;padding:30px;color:#777;font-size:16px}
.detail-link{color:#0099ff;text-decoration:none;font-weight:500}
.detail-link:hover{text-decoration:underline}
</style>
</head>
<body>

<div id="notificationBell">
    🔔 <span class="badge" id="notificationCount">0</span>
</div>

<div id="notificationPopup">
    <ul class="list" id="notificationList">
        <li>Đang tải...</li>
    </ul>
</div>

<div class="container">
    <h2>📅 Lịch đặt dịch vụ của tôi</h2>

    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tên dịch vụ</th>
                    <th>Ngày hẹn</th>
                    <th>Chi phí</th>
                    <th>Trạng thái</th>
                    <th>Ngày đặt</th>
                    <th>Chi tiết</th>
                    <th>Đánh giá</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['service_name']) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['appointment_time'])) ?></td>
                        
                        <td style="color: #d9534f; font-weight: bold;">
                            <?= number_format($row['final_price'], 0, ',', '.') ?>đ
                        </td>

                        <td>
                            <span class="status <?= $row['status'] ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                        <td>
                            <a href="booking_detail.php?id=<?= $row['id'] ?>" class="detail-link">Xem</a>
                        </td>
                        <td>
                            <?php if ($row['status'] === 'completed'): ?>
                                <a href="reviews.php?booking_id=<?= $row['id'] ?>" style="color:#ff9800;">⭐ Đánh giá</a>
                            <?php else: ?>
                                <span style="color:#aaa;">---</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-booking">😕 Bạn chưa có lịch đặt nào.</p>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// 🧭 AJAX load notifications
function loadNotifications() {
    $.ajax({
        url: "fetch_notifications.php",
        method: "GET",
        dataType: "json",
        success: function(data) {
            let html = "";
            let unread = 0;
            if (Array.isArray(data) && data.length > 0) {
                data.forEach(n => {
                    const cls = n.status === "unread" ? "unread" : "";
                    html += `<li class="${cls}">${escapeHtml(n.message)}<br><small style="color:gray">${n.created_at}</small></li>`;
                    if (n.status === "unread") unread++;
                });
            } else {
                html = "<li>Không có thông báo nào.</li>";
            }
            $("#notificationList").html(html);
            $("#notificationCount").text(unread);
        },
        error: function() {
            $("#notificationList").html("<li>Không thể tải thông báo.</li>");
        }
    });
}

function escapeHtml(text) {
    if (!text) return "";
    return text.replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
}

$(function() {
    const bell = $("#notificationBell");
    const popup = $("#notificationPopup");

    bell.on("click", function() {
        popup.toggle();
        loadNotifications();
    });

    $(document).on("click", function(e) {
        if (!bell.is(e.target) && bell.has(e.target).length === 0 && !popup.is(e.target) && popup.has(e.target).length === 0) {
            popup.hide();
        }
    });

    loadNotifications();
    setInterval(loadNotifications, 10000);
});
</script>
</body>
</html>