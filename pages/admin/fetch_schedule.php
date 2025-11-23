<?php
// pages/admin/fetch_schedule.php
session_start();

// 1. Check quyền
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo '<p class="text-danger text-center">Không có quyền truy cập.</p>';
    exit;
}

require_once '../../config/db.php';

$tech_id = isset($_GET['technician_id']) ? intval($_GET['technician_id']) : 0;

if ($tech_id == 0) {
    echo '<div class="text-center py-5 text-muted">
            <i class="fa-solid fa-user-clock fa-3x mb-3 opacity-25"></i>
            <p>Vui lòng chọn kỹ thuật viên để xem lịch trình.</p>
          </div>';
    exit;
}

// 2. HÀM LẤY LỊCH TỪ BẢNG BOOKINGS
function getScheduleByDate($conn, $tech_id, $date) {
    // Lấy các đơn hàng đã Confirm hoặc Processing hoặc Completed
    // (Tức là thợ đó đã bị book vào giờ đó)
    $sql = "
        SELECT 
            b.id, 
            b.appointment_time, 
            s.name as service_name, 
            b.status
        FROM bookings b
        LEFT JOIN services s ON b.service_id = s.id
        WHERE 
            b.technician_id = ? 
            AND DATE(b.appointment_time) = ?
            AND b.status IN ('confirmed', 'processing', 'completed')
        ORDER BY b.appointment_time ASC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $tech_id, $date);
    $stmt->execute();
    return $stmt->get_result();
}

// 3. XỬ LÝ HIỂN THỊ CHO 2 NGÀY (Hôm nay & Ngày mai)
$dates = [
    'Hôm nay' => date('Y-m-d'),
    'Ngày mai' => date('Y-m-d', strtotime('+1 day'))
];

?>

<div class="schedule-container">
    <?php foreach ($dates as $label => $date): ?>
        <?php 
            $result = getScheduleByDate($conn, $tech_id, $date);
            $displayDate = date('d/m/Y', strtotime($date));
            $isToday = ($label == 'Hôm nay');
            $cardClass = $isToday ? 'border-primary' : 'border-light';
            $headerClass = $isToday ? 'bg-primary text-white' : 'bg-light text-dark';
        ?>
        
        <div class="card mb-3 shadow-sm <?= $cardClass ?>">
            <div class="card-header <?= $headerClass ?> fw-bold d-flex justify-content-between align-items-center">
                <span><i class="fa-regular fa-calendar"></i> <?= $label ?> (<?= $displayDate ?>)</span>
                <?php if($result->num_rows > 0): ?>
                    <span class="badge bg-warning text-dark"><?= $result->num_rows ?> ca</span>
                <?php else: ?>
                    <span class="badge bg-success">Rảnh</span>
                <?php endif; ?>
            </div>

            <div class="card-body p-0">
                <?php if ($result->num_rows > 0): ?>
                    <table class="table table-striped mb-0 text-center table-sm" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Giờ</th>
                                <th>Mã Đơn</th>
                                <th class="text-start">Công việc</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php 
                                    $start = date('H:i', strtotime($row['appointment_time']));
                                    // Giả định mỗi ca làm 2 tiếng
                                    $end = date('H:i', strtotime($row['appointment_time'] . ' +2 hours'));
                                ?>
                                <tr>
                                    <td class="fw-bold text-primary">
                                        <?= $start ?> - <?= $end ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">#<?= $row['id'] ?></span>
                                    </td>
                                    <td class="text-start text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($row['service_name']) ?>">
                                        <?= htmlspecialchars($row['service_name']) ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fa-regular fa-face-smile mb-2" style="font-size: 2rem; opacity: 0.3;"></i>
                        <p class="mb-0 small">Không có lịch bận. Có thể gán đơn.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    
    <div class="alert alert-info small mt-3 mb-0">
        <i class="fa-solid fa-circle-info"></i> 
        Hệ thống hiển thị các khung giờ thợ <b>đã có đơn</b>. Các giờ còn lại được coi là rảnh.
    </div>
</div>