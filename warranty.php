<?php
session_start();
require_once 'config/db.php';

$result = null;
$error = null;
$keyword = '';

if (isset($_GET['keyword'])) {
    $keyword = trim($_GET['keyword']);
    
    // Tìm đơn hàng hoàn thành
    $sql = "
        SELECT 
            b.id, b.customer_name, b.phone, b.appointment_time, 
            s.name as service_name,
            DATE_ADD(b.appointment_time, INTERVAL 90 DAY) as expiry_date
        FROM bookings b
        LEFT JOIN services s ON b.service_id = s.id
        WHERE (b.id = ? OR b.phone = ?) AND b.status = 'completed'
        ORDER BY b.appointment_time DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $keyword, $keyword);
    $stmt->execute();
    $search_res = $stmt->get_result();
    
    if ($search_res->num_rows > 0) {
        $result = $search_res->fetch_all(MYSQLI_ASSOC);
    } else {
        $error = "Không tìm thấy thông tin bảo hành hoặc đơn hàng chưa hoàn thành.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tra cứu bảo hành - TechFix</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary: #0d6efd; --success: #28a745; --danger: #dc3545; --bg: #f0f2f5; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; padding: 0; color: #333; }
        .warranty-page { max-width: 800px; margin: 40px auto; padding: 20px; }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: #666; font-weight: 600; margin-bottom: 20px; }
        
        /* Search Card */
        .search-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center; margin-bottom: 30px; position: relative; overflow: hidden; }
        .search-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 5px; background: linear-gradient(90deg, var(--primary), #00d2ff); }
        .search-form { position: relative; max-width: 500px; margin: 0 auto; }
        .search-form input { width: 100%; padding: 15px 55px 15px 25px; border: 2px solid #eee; border-radius: 50px; font-size: 16px; outline: none; box-sizing: border-box; transition: 0.3s; }
        .search-form input:focus { border-color: var(--primary); }
        .search-form button { position: absolute; right: 6px; top: 6px; bottom: 6px; width: 46px; background: var(--primary); color: white; border: none; border-radius: 50%; cursor: pointer; font-size: 18px; }

        /* Result Card */
        .result-card { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-bottom: 25px; animation: slideUp 0.4s ease-out; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .card-header { padding: 20px; background: #fff; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .card-body { padding: 25px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .info-item label { display: block; font-size: 13px; color: #888; margin-bottom: 4px; }
        .info-item span { font-weight: 600; color: #333; font-size: 15px; }
        
        /* Badge */
        .badge { padding: 8px 15px; border-radius: 30px; font-size: 12px; font-weight: 800; text-transform: uppercase; }
        .badge.active { background: #e6f4ea; color: var(--success); border: 1px solid #bce2c6; }
        .badge.expired { background: #fce8e6; color: var(--danger); border: 1px solid #fad2cf; }

        /* Footer Action */
        .card-footer { background: #f9f9f9; padding: 15px 25px; display: flex; align-items: center; justify-content: space-between; }
        .action-link { cursor: pointer; font-weight: bold; color: var(--primary); text-decoration: none; font-size: 14px; }
        .action-link:hover { text-decoration: underline; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 30px; border-radius: 15px; width: 90%; max-width: 500px; text-align: center; position: relative; animation: zoomIn 0.3s; }
        @keyframes zoomIn { from {transform: scale(0.8); opacity: 0;} to {transform: scale(1); opacity: 1;} }
        .modal textarea { width: 100%; padding: 10px; margin: 15px 0; border-radius: 8px; border: 1px solid #ddd; font-family: inherit; }
        .modal-btn { width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .close { position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; color: #aaa; }

        @media (max-width: 600px) { .info-grid { grid-template-columns: 1fr; } .card-footer { flex-direction: column; gap: 10px; text-align: center; } }
    </style>
</head>
<body>

    <div class="warranty-page">
        <a href="index.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Quay về Trang chủ</a>

        <div class="search-card">
            <h1>Tra Cứu Bảo Hành Điện Tử</h1>
            <p>Nhập <b>Mã đơn hàng</b> hoặc <b>Số điện thoại</b> để kiểm tra.</p>
            <form action="" method="GET" class="search-form">
                <input type="text" name="keyword" placeholder="Nhập mã đơn (VD: 32) hoặc SĐT..." value="<?= htmlspecialchars($keyword) ?>" required>
                <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
            <?php if ($error): ?>
                <div style="margin-top: 20px; color: var(--danger); font-weight: bold;"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div>
            <?php endif; ?>
        </div>

        <?php if ($result): ?>
            <?php foreach ($result as $item): ?>
                <?php 
                    $today = date('Y-m-d');
                    $expiry = date('Y-m-d', strtotime($item['expiry_date']));
                    $isValid = ($today <= $expiry);
                    $diff = (strtotime($expiry) - time()) / (60 * 60 * 24);
                    $daysLeft = round($diff);
                ?>

                <div class="result-card">
                    <div class="card-header">
                        <span class="order-id">Đơn hàng #<?= $item['id'] ?></span>
                        <?php if ($isValid): ?>
                            <span class="badge active"><i class="fa-solid fa-check-circle"></i> Còn bảo hành</span>
                        <?php else: ?>
                            <span class="badge expired"><i class="fa-solid fa-circle-xmark"></i> Hết hạn</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item"><label>Khách hàng</label><span><?= htmlspecialchars($item['customer_name']) ?></span></div>
                            <div class="info-item"><label>Dịch vụ</label><span style="color:var(--primary)"><?= htmlspecialchars($item['service_name']) ?></span></div>
                            <div class="info-item"><label>Ngày hoàn thành</label><span><?= date('d/m/Y', strtotime($item['appointment_time'])) ?></span></div>
                            <div class="info-item"><label>Hết hạn BH</label><span><?= date('d/m/Y', strtotime($expiry)) ?></span></div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <?php if ($isValid): ?>
                            <span style="color: var(--success); font-size: 14px;"><i class="fa-regular fa-clock"></i> Còn lại <b><?= $daysLeft ?></b> ngày bảo hành.</span>
                            
                            <a onclick="openModal(<?= $item['id'] ?>)" class="action-link">Yêu cầu bảo hành <i class="fa-solid fa-arrow-right"></i></a>
                        <?php else: ?>
                            <span style="color: #999; font-size: 14px;">Đã hết hạn vào ngày <?= date('d/m/Y', strtotime($expiry)) ?>.</span>
                            <a href="/TechFixPHP/Customer/book.php" class="action-link">Đặt lịch mới</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="warrantyModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3 style="color: var(--primary);">Yêu cầu bảo hành</h3>
            <p style="color: #666; font-size: 14px;">Vui lòng mô tả vấn đề bạn đang gặp phải.</p>
            <form id="warrantyForm">
                <input type="hidden" id="booking_id" name="booking_id">
                <textarea id="reason" name="reason" rows="4" placeholder="Ví dụ: Máy lại không lạnh, có tiếng kêu lạ..." required></textarea>
                <button type="submit" class="modal-btn">Gửi Yêu Cầu</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('warrantyModal');
        const bookingInput = document.getElementById('booking_id');

        function openModal(id) {
            bookingInput.value = id;
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        // Xử lý gửi Form bằng AJAX
        document.getElementById('warrantyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const id = bookingInput.value;
            const reason = document.getElementById('reason').value;
            const btn = document.querySelector('.modal-btn');
            btn.innerText = "Đang gửi...";
            btn.disabled = true;

            const formData = new FormData();
            formData.append('booking_id', id);
            formData.append('reason', reason);

            fetch('/TechFixPHP/pages/api/send_warranty.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                closeModal();
                btn.innerText = "Gửi Yêu Cầu";
                btn.disabled = false;
                
                if (data.success) {
                    Swal.fire('Thành công!', data.message, 'success');
                    document.getElementById('reason').value = ''; // Reset form
                } else {
                    Swal.fire('Lỗi!', data.message, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Lỗi!', 'Không thể kết nối server.', 'error');
            });
        });

        // Đóng modal khi click ra ngoài
        window.onclick = function(event) {
            if (event.target == modal) closeModal();
        }
    </script>

</body>
</html>