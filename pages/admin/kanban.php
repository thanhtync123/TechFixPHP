<?php
session_start();
// 1. Check quyền
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: /TechFixPHP/pages/public_page/login.php"); exit();
}

include '../../config/db.php';

// 2. API XỬ LÝ CẬP NHẬT TRẠNG THÁI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $id = intval($_POST['booking_id']);
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

// 3. LẤY DỮ LIỆU
$sql = "SELECT b.*, s.name as service_name FROM bookings b LEFT JOIN services s ON b.service_id = s.id ORDER BY b.appointment_time ASC";
$result = $conn->query($sql);

$tasks = ['pending' => [], 'confirmed' => [], 'completed' => [], 'cancelled' => []];
while ($row = $result->fetch_assoc()) {
    if (isset($tasks[$row['status']])) $tasks[$row['status']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kanban Board - TechFix</title>
    <link href="/TechFixPHP/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; overflow-y: hidden; } /* Ẩn cuộn trang chính */
        
        .wrapper { display: flex; height: 100vh; }
        .sidebar { width: 250px; background: #343a40; color: white; flex-shrink: 0; }
        .sidebar a { color: rgba(255,255,255,.8); text-decoration: none; display: block; padding: 12px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar a:hover { background: #0d6efd; }

        .main-content { flex-grow: 1; padding: 20px; overflow-x: auto; display: flex; flex-direction: column; }
        
        /* Header */
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }

        /* Kanban Container */
        .kanban-board {
            display: flex; gap: 24px; height: 100%; padding-bottom: 10px;
            overflow-x: auto; /* Cuộn ngang nếu nhiều cột */
        }

        /* Cột (Column) */
        .kanban-col {
            min-width: 320px; width: 320px;
            background: #ebecf0; /* Màu xám Trello */
            border-radius: 12px;
            display: flex; flex-direction: column;
            max-height: 100%;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .kanban-header {
            padding: 16px; font-weight: 700; font-size: 0.95rem;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 2px solid rgba(0,0,0,0.05);
            color: #44546f; text-transform: uppercase;
        }

        /* Màu header từng cột */
        .head-pending { border-top: 4px solid #ffc107; }
        .head-confirmed { border-top: 4px solid #0d6efd; }
        .head-completed { border-top: 4px solid #198754; }
        .head-cancelled { border-top: 4px solid #dc3545; }

        .kanban-body {
            padding: 12px; flex-grow: 1; overflow-y: auto;
            min-height: 150px; /* Để dễ thả vào khi trống */
        }

        /* Card (Thẻ đơn hàng) */
        .kanban-card {
            background: white; border-radius: 8px; padding: 16px; margin-bottom: 12px;
            box-shadow: 0 1px 4px rgba(9, 30, 66, 0.25);
            cursor: grab; position: relative;
            border-left: 4px solid transparent;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .kanban-card:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(9, 30, 66, 0.3); }
        .kanban-card:active { cursor: grabbing; }

        /* Border màu theo trạng thái */
        .col-pending .kanban-card { border-left-color: #ffc107; }
        .col-confirmed .kanban-card { border-left-color: #0d6efd; }
        .col-completed .kanban-card { border-left-color: #198754; }

        .card-id { font-size: 0.75rem; font-weight: 700; color: #888; margin-bottom: 4px; display: block; }
        .card-customer { font-weight: 600; color: #172b4d; font-size: 1rem; margin-bottom: 4px; }
        .card-service { font-size: 0.85rem; color: #44546f; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
        .card-footer {
            display: flex; justify-content: space-between; align-items: center; margin-top: 12px; padding-top: 10px; border-top: 1px solid #f4f5f7;
        }
        .card-time { font-size: 0.75rem; font-weight: 600; color: #dc3545; background: #fff5f5; padding: 2px 6px; border-radius: 4px; }
        .card-price { font-weight: 700; color: #0d6efd; font-size: 0.9rem; }

        /* Scrollbar tùy chỉnh */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="sidebar">
        <?php include __DIR__ . '/template/sidebar.php'; ?>
    </div>

    <div class="main-content">
        <div class="header-bar">
            <h2 class="h4 fw-bold m-0 text-dark"><i class="fa-brands fa-trello text-primary"></i> Bảng Điều Phối (Kanban)</h2>
            <a href="orders.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-list"></i> Xem dạng danh sách</a>
        </div>

        <div class="kanban-board">
            
            <div class="kanban-col head-pending col-pending">
                <div class="kanban-header">
                    <span><i class="fa-regular fa-clock"></i> Chờ Xử Lý</span>
                    <span class="badge bg-warning text-dark rounded-pill"><?= count($tasks['pending']) ?></span>
                </div>
                <div class="kanban-body" id="pending">
                    <?php foreach($tasks['pending'] as $item): renderCard($item); endforeach; ?>
                </div>
            </div>

            <div class="kanban-col head-confirmed col-confirmed">
                <div class="kanban-header">
                    <span><i class="fa-solid fa-user-check"></i> Đã Tiếp Nhận</span>
                    <span class="badge bg-primary rounded-pill"><?= count($tasks['confirmed']) ?></span>
                </div>
                <div class="kanban-body" id="confirmed">
                    <?php foreach($tasks['confirmed'] as $item): renderCard($item); endforeach; ?>
                </div>
            </div>

            <div class="kanban-col head-completed col-completed">
                <div class="kanban-header">
                    <span><i class="fa-solid fa-check-circle"></i> Hoàn Thành</span>
                    <span class="badge bg-success rounded-pill"><?= count($tasks['completed']) ?></span>
                </div>
                <div class="kanban-body" id="completed">
                    <?php foreach($tasks['completed'] as $item): renderCard($item); endforeach; ?>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php
function renderCard($row) {
    $time = date('d/m H:i', strtotime($row['appointment_time']));
    echo '
    <div class="kanban-card" data-id="'.$row['id'].'">
        <span class="card-id">#'.$row['id'].'</span>
        <div class="card-customer">'.$row['customer_name'].'</div>
        <div class="card-service">
            <i class="fa-solid fa-wrench text-secondary"></i> '.$row['service_name'].'
        </div>
        
        <div class="card-footer">
            <span class="card-time"><i class="fa-regular fa-clock"></i> '.$time.'</span>
            <span class="card-price">'.number_format($row['final_price']).'đ</span>
        </div>
        
        <div style="position:absolute; top:10px; right:10px;">
             <a href="admin_order_detail.php?id='.$row['id'].'" class="text-secondary" title="Xem chi tiết"><i class="fa-solid fa-eye"></i></a>
        </div>
    </div>';
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const columns = document.querySelectorAll('.kanban-body');
    columns.forEach(col => {
        new Sortable(col, {
            group: 'kanban',
            animation: 200,
            ghostClass: 'bg-light',
            delay: 100, // Tránh click nhầm khi chạm trên mobile
            delayOnTouchOnly: true,
            
            onEnd: function (evt) {
                const itemEl = evt.item;
                const newStatus = evt.to.id;
                const oldStatus = evt.from.id;
                const bookingId = itemEl.getAttribute('data-id');

                if (newStatus === oldStatus) return;

                // Gửi AJAX
                const formData = new FormData();
                formData.append('booking_id', bookingId);
                formData.append('status', newStatus);

                fetch('kanban.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const Toast = Swal.mixin({
                            toast: true, position: 'top-end', showConfirmButton: false, timer: 1500,
                            timerProgressBar: false,
                            didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                            }
                        });
                        Toast.fire({ icon: 'success', title: 'Đã cập nhật!' });
                    } else {
                        Swal.fire('Lỗi', 'Cập nhật thất bại', 'error');
                        evt.from.appendChild(itemEl); // Hoàn tác nếu lỗi
                    }
                });
            }
        });
    });
</script>

</body>
</html>