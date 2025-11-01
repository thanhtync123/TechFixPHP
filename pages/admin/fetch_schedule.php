<?php
include '../../config/db.php';

// Kiểm tra kết nối
if (!$conn) {
    die("<p style='color:red;'>Kết nối CSDL thất bại.</p>");
}

// Kiểm tra có truyền ID kỹ thuật viên hay không
if (!isset($_GET['technician_id'])) {
    echo "<p style='color:red;'>Thiếu ID kỹ thuật viên.</p>";
    exit;
}

$tech_id = intval($_GET['technician_id']);

// Lấy danh sách lịch làm việc của kỹ thuật viên
$query = "
    SELECT 
        ts.id,
        u.name AS technician_name,
        ts.date,
        ts.start_time,
        ts.end_time,
        ts.status
    FROM technician_schedule ts
    JOIN users u ON ts.technician_id = u.id
    WHERE ts.technician_id = ?
    ORDER BY ts.date ASC, ts.start_time ASC
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("<p style='color:red;'>Lỗi truy vấn: " . htmlspecialchars($conn->error) . "</p>");
}

$stmt->bind_param("i", $tech_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p style='color:#777;text-align:center;'>Không có lịch làm việc nào.</p>";
    exit;
}

echo "
<style>
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    font-family: Arial, sans-serif;
}
thead {
    background: #007bff;
    color: white;
}
th, td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center;
}
tbody tr:nth-child(even) {
    background: #f9f9f9;
}
.status {
    padding: 4px 8px;
    border-radius: 6px;
    color: white;
    font-weight: bold;
}
.status.available { background-color: #28a745; }  /* Rảnh */
.status.busy { background-color: #ffc107; }       /* Bận */
.status.off { background-color: #dc3545; }        /* Nghỉ */
.date-group {
    background: #eee;
    font-weight: bold;
    text-align: left;
    padding: 8px;
}
</style>
";

// Nhóm lịch theo ngày
$currentDate = '';
while ($row = $result->fetch_assoc()) {
    $date = date('d/m/Y', strtotime($row['date']));
    if ($date !== $currentDate) {
        if ($currentDate !== '') echo "</tbody></table><br>";
        echo "<div class='date-group'>📅 Ngày: $date</div>";
        echo "<table><thead>
                <tr>
                    <th>ID</th>
                    <th>Tên kỹ thuật viên</th>
                    <th>Giờ bắt đầu</th>
                    <th>Giờ kết thúc</th>
                    <th>Trạng thái</th>
                </tr>
              </thead><tbody>";
        $currentDate = $date;
    }

    echo "<tr>
            <td>{$row['id']}</td>
            <td>" . htmlspecialchars($row['technician_name']) . "</td>
            <td>" . date('H:i', strtotime($row['start_time'])) . "</td>
            <td>" . date('H:i', strtotime($row['end_time'])) . "</td>
            <td><span class='status {$row['status']}'>" . ucfirst($row['status']) . "</span></td>
          </tr>";
}
echo "</tbody></table>";
?>
