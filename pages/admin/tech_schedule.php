<?php
session_start();
// (Đảm bảo đường dẫn config CSDL đúng)
include '../../config/db.php';

// 1. (QUAN TRỌNG) Include sidebar ở TRÊN CÙNG
include __DIR__ . '/../admin/template/sidebar.php'; 

// 🔒 2. KIỂM TRA VAI TRÒ
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'technical') {
    header("Location: /TechFixPHP/pages/public_page/login.php");
    exit();
}

// 3. LẤY ID CỦA KỸ THUẬT VIÊN ĐANG ĐĂNG NHẬP
$tech_id = $_SESSION['user']['id']; 

// 4. TRUY VẤN CÁC ĐƠN HÀNG ĐANG CHỜ LÀM (status = 'confirmed')
$bookings_query = $conn->prepare("
    SELECT 
        b.id, 
        b.customer_name, 
        b.phone, 
        b.address, 
        b.appointment_time, 
        b.status,
        b.district,
        s.name AS service_name
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    WHERE 
        b.technician_id = ? 
        AND b.status = 'confirmed'
    ORDER BY b.appointment_time ASC
");
$bookings_query->bind_param("i", $tech_id);
$bookings_query->execute();
$result = $bookings_query->get_result();
$bookings = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch làm việc của tôi - TECHFIX</title>
    
    <style>
        body {
    font-family: "Segoe UI", sans-serif;
    background: #f4f7ff;
    margin: 0;
    padding: 0;
}

/* Container lớn */
.container-widget {
    max-width: 1200px;
    margin: 20px auto;
    background: #ffffff;
    border-radius: 14px;
    padding: 30px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
    animation: fadeUp .4s ease;
}

@keyframes fadeUp {
    from {opacity: 0; transform: translateY(10px);}
    to {opacity: 1; transform: translateY(0);}
}

/* Title */
.container-widget h2 {
    text-align: center;
    color: #222;
    font-size: 26px;
    font-weight: 700;
    margin-bottom: 20px;
}

/* Bảng */
table {
    width: 100%;
    border-collapse: collapse;
    overflow: hidden;
    border-radius: 10px;
}

table thead {
    background: linear-gradient(45deg, #007bff, #0056d6);
    color: white;
}

table th, table td {
    padding: 14px 12px;
    text-align: center;
    border-bottom: 1px solid #eaeaea;
    font-size: 15px;
}

table tbody tr:nth-child(even) {
    background: #f9fbff;
}

table tbody tr:hover {
    background: #eef5ff;
    transition: .2s;
}

/* Phần thông tin nhỏ */
table td small {
    font-size: 13px;
    color: #555;
}

/* Button hoàn thành */
.action-btn {
    background: #0cbc3c;
    color: white;
    border: none;
    padding: 9px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: .2s;
}

.action-btn:hover {
    background: #07a532;
    transform: translateY(-2px);
}

/* Responsive fix cho mobile */
@media (max-width: 768px) {
    table, thead, tbody, th, td, tr {
        display: block;
    }
    thead {
        display: none;
    }
    tbody tr {
        margin-bottom: 12px;
        background: white;
        border-radius: 10px;
        padding: 10px;
        box-shadow: 0 1px 6px rgba(0, 0, 0, 0.08);
    }
    tbody td {
        text-align: left;
        padding: 8px 10px;
        border: none;
        display: flex;
        justify-content: space-between;
    }
    tbody td:before {
        content: attr(data-label);
        font-weight: 700;
        color: #222;
        padding-right: 10px;
    }
}
        
        .container-widget { /* Đổi tên class để tránh xung đột với bootstrap */
            max-width: 1200px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }
        .container-widget h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .container-widget th { background: #007bff; color: #fff; } /* Màu xanh cho việc sắp tới */
        .action-btn {
            background: #28a745; /* Màu xanh lá */
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .action-btn:hover {
             background: #218838;
        }
    </style>
</head>
<body>

<main class="main-content">

    <div class="container-widget">
        <h2>Lịch làm việc (Việc mới)</h2>
        <p style="text-align: center; font-size: 1.1rem;">
            Kỹ thuật viên: <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>
        </p>

        <?php if (!empty($bookings)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Mã Đơn</th>
                        <th>Khách hàng</th>
                        <th>SĐT / Địa chỉ</th>
                        <th>Dịch vụ / Quận</th>
                        <th>Ngày hẹn</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td>#<?= $booking['id'] ?></td>
                            <td><?= htmlspecialchars($booking['customer_name']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($booking['phone']) ?></strong><br>
                                <small><?= htmlspecialchars($booking['address']) ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($booking['service_name']) ?></strong><br>
                                <small>Quận: <?= htmlspecialchars($booking['district']) ?></small>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($booking['appointment_time'])) ?></td>
                            <td>
                                <form action="api_complete_job.php" method="POST" onsubmit="return confirm('Xác nhận hoàn thành công việc này?')">
                                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                    <button type="submit" class="action-btn">✅ Hoàn thành</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align:center; color: #777; font-size: 1.1rem; padding: 20px;">
                Bạn không có công việc mới nào (ở trạng thái 'xác nhận').
            </p>
        <?php endif; ?>
    </div>

</main> </body>
</html>