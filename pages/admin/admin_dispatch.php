<?php
session_start();
include '../../config/db.php'; // Đảm bảo đường dẫn đúng

// 🔒 1. KIỂM TRA QUYỀN ADMIN
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: /TechFixPHP/pages/public_page/login.php");
    exit();
}

// ==========================================================
// TRUY VẤN DỮ LIỆU
// ==========================================================

// 2. LẤY DANH SÁCH KỸ THUẬT VIÊN (Dùng cho cả 2 tính năng)
$tech_list = [];
$tech_query = $conn->query("SELECT id, name FROM users WHERE role = 'technical'");
if ($tech_query) {
    $tech_list = $tech_query->fetch_all(MYSQLI_ASSOC);
}

// 3. XỬ LÝ TÌM KIẾM (GỢI Ý 2)
$search_term = $_GET['search'] ?? '';
$sql_where = ""; // Mệnh đề WHERE
$params = []; // Tham số cho truy vấn

if (!empty($search_term)) {
    // %...% nghĩa là tìm kiếm gần đúng
    $like_term = "%" . $search_term . "%";
    
    // Tìm ở Tên, SĐT, hoặc ID (số)
    $sql_where = "
        WHERE (b.customer_name LIKE ? OR b.phone LIKE ? OR b.id = ?)
    ";
    
    // Thêm 3 tham số vào mảng
    $params[] = $like_term;
    $params[] = $like_term;
    $params[] = $search_term;
}

// 4. LẤY DANH SÁCH BOOKINGS (ĐÃ KẾT HỢP TÌM KIẾM)
$query_sql = "
    SELECT 
        b.id, b.customer_name, b.address, b.appointment_time, b.status, b.technician_id,
        b.final_price, b.district, b.phone,
        IFNULL(s.name, '[Dịch vụ đã xóa]') AS service_name,
        t.name AS tech_name
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    LEFT JOIN users t ON b.technician_id = t.id
    $sql_where  -- <-- MỆNH ĐỀ TÌM KIẾM ĐƯỢC THÊM VÀO ĐÂY
    ORDER BY 
        CASE WHEN b.status = 'pending' THEN 1 ELSE 2 END, 
        b.appointment_time ASC
";

$stmt = $conn->prepare($query_sql);

// Gắn tham số (nếu có)
if (!empty($params)) {
    // 'ssi' = string, string, integer
    $stmt->bind_param('ssi', ...$params);
}

$stmt->execute();
$bookings_query = $stmt->get_result();
$bookings = $bookings_query ? $bookings_query->fetch_all(MYSQLI_ASSOC) : [];

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trung tâm Điều phối - TECHFIX</title>
    <link rel="stylesheet" href="../../assets/css/admin_dispatch.css">
</head>
<body>
    <div class="container">
        <h2>🚀 Trung tâm Điều phối Đơn hàng</h2>
        
        <div class="widget" style="margin-bottom: 25px; padding: 20px;">
            <form method="GET" action="admin_dispatch.php">
                <div style="display:flex; gap: 10px;">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Tìm theo Tên Khách, SĐT, hoặc Mã Đơn hàng..." 
                        value="<?php echo htmlspecialchars($search_term); ?>" 
                        style="flex: 1; padding: 12px; border-radius: 8px; border: 1px solid #ccc; font-size: 1rem;">
                    
                    <button type="submit" 
                            style="background: #007bff; color: white; border: none; padding: 0 25px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 1rem;">
                        🔍 Tìm kiếm
                    </button>
                     <a href="admin_dispatch.php" style="background: #6c757d; color: white; border: none; padding: 0 25px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 1rem; text-decoration: none; display: flex; align-items: center;">Xóa lọc</a>
                </div>
            </form>
        </div>

        <div class="dispatch-layout">

            <div class="main-column widget">
                <h3>Danh Sách Đơn hàng (Bookings)</h3>
                <?php if (!empty($bookings)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Khách hàng / Địa chỉ</th>
                                <th>Dịch vụ / Quận</th>
                                <th>Ngày hẹn / Giá</th>
                                <th>Trạng thái</th>
                                <th>Phân công (Kỹ thuật viên)</th>
                                <th>In</th> </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><?= $booking['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($booking['customer_name']) ?></strong><br>
                                        <small><?= htmlspecialchars($booking['address']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($booking['service_name']) ?></strong><br>
                                        <small>Quận: <?= htmlspecialchars($booking['district']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= date('d/m/Y H:i', strtotime($booking['appointment_time'])) ?></strong><br>
                                        <small><?= number_format($booking['final_price']) ?>đ</small>
                                    </td>
                                    <td>
                                        <span class="status <?= $booking['status'] ?>">
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed'): ?>
                                            <form class="assign-form" action="assign_technician.php" method="POST"> 
                                                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                                <select name="technician_id" required>
                                                    <option value="">-- Chọn Kỹ thuật viên --</option>
                                                    <?php foreach ($tech_list as $tech): ?>
                                                        <option value="<?= $tech['id'] ?>" <?= ($booking['technician_id'] == $tech['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($tech['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit">Lưu</button>
                                            </form>
                                        <?php else: ?>
                                            <strong><?= htmlspecialchars($booking['tech_name'] ?? 'N/A') ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td style="text-align:center;">
                                        <?php if ($booking['status'] === 'completed' || $booking['status'] === 'confirmed'): ?>
                                            <a href="booking_invoice.php?id=<?= $booking['id'] ?>" target="_blank" style="font-size: 1.5rem; text-decoration:none;">
                                                🖨️
                                            </a>
                                        <?php else: ?>
                                            <span style="color:#ccc;">—</span>
                                        <?php endif; ?>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align:center;">Không có lịch đặt nào (hoặc không tìm thấy kết quả).</p>
                <?php endif; ?>
            </div>

            <div class="sidebar-column widget">
                <h3>Kiểm tra Lịch rảnh (Technician Schedule)</h3>
                
                <label for="technician_select">Chọn kỹ thuật viên: </label>
                <select id="technician_select" onchange="loadSchedule()">
                    <option value="">-- Chọn kỹ thuật viên --</option>
                    <?php foreach ($tech_list as $tech): ?>
                        <option value="<?= $tech['id'] ?>"><?= htmlspecialchars($tech['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <div id="scheduleContainer" style="margin-top: 20px;">
                    <p style="color:#777;text-align:center;">Chọn một kỹ thuật viên để xem lịch rảnh/bận.</p>
                </div>
            </div>

        </div> </div>

    <script>
        function loadSchedule() {
            const techId = document.getElementById('technician_select').value;
            const container = document.getElementById('scheduleContainer');
            
            if (!techId) {
                container.innerHTML = '<p style="color:#777;text-align:center;">Chọn một kỹ thuật viên...</p>';
                return;
            }

            container.innerHTML = '<p>Đang tải lịch...</p>';
            
            // Đảm bảo file này nằm đúng đường dẫn
            fetch('fetch_schedule.php?technician_id=' + techId)
                .then(res => res.text())
                .then(html_data => {
                    container.innerHTML = html_data;
                })
                .catch(err => {
                    container.innerHTML = '<p style="color:red;">Lỗi! Không thể tải lịch rảnh.</p>';
                });
        }
    </script>
</body>
</html>