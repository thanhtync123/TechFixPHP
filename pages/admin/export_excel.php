<?php
session_start();
require_once '../../config/db.php';

// 1. Check quyền
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Access Denied");
}

// 2. Nhận dữ liệu lọc từ URL (GET)
$filterMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$filterYear  = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// 3. Tạo tên file thông minh (Ví dụ: Bao_Cao_Thang_11_2025.xls)
if ($filterMonth) {
    $timeLabel = "Tháng $filterMonth/$filterYear";
    $fileName = "Doanh_Thu_T{$filterMonth}_{$filterYear}.xls";
} else {
    $timeLabel = "Cả năm $filterYear";
    $fileName = "Doanh_Thu_Nam_{$filterYear}.xls";
}

// 4. Header Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$fileName\"");
header("Pragma: no-cache");
header("Expires: 0");
echo "\xEF\xBB\xBF"; // BOM UTF-8
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; }
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #2E7D32; color: white; border: 1px solid #000; padding: 10px; vertical-align: middle;}
        td { border: 1px solid #000; padding: 8px; vertical-align: middle; }
        .center { text-align: center; }
        .money { text-align: right; mso-number-format:"\#\,\#\#0"; }
        .text-string { mso-number-format:"\@"; } /* Định dạng văn bản cho mã đơn */
        .date-col { mso-number-format:"Short Date"; text-align: center; }
    </style>
</head>
<body>

    <h2 style="text-align: center; color: #1B5E20; text-transform: uppercase;">
        BÁO CÁO DOANH THU & LƯƠNG THỢ - TECHFIX
    </h2>
    <h3 style="text-align: center; color: #555;">
        Thời gian: <?= $timeLabel ?>
    </h3>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 50px;">STT</th>
                <th rowspan="2" style="width: 100px;">Mã Đơn</th>
                
                <th colspan="4" style="background-color: #1565C0;">Thời Gian Hoàn Thành</th>
                
                <th rowspan="2" style="width: 150px;">Khách Hàng</th>
                <th rowspan="2" style="width: 200px;">Dịch Vụ</th>
                <th rowspan="2" style="width: 150px;">Thợ Phụ Trách</th>
                <th rowspan="2" style="width: 120px;">Doanh Thu<br>(VNĐ)</th>
                <th rowspan="2" style="width: 120px;">Cty Giữ<br>(30%)</th>
                <th rowspan="2" style="width: 120px;">Lương Thợ<br>(70%)</th>
            </tr>
            <tr>
                <th style="background-color: #42A5F5; width: 100px;">Ngày</th>
                <th style="background-color: #42A5F5; width: 50px;">Tháng</th>
                <th style="background-color: #42A5F5; width: 50px;">Năm</th>
                <th style="background-color: #42A5F5; width: 80px;">Giờ</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Xây dựng câu SQL động theo bộ lọc
            $sql = "
                SELECT 
                    b.id, b.customer_name, b.final_price, b.appointment_time,
                    s.name as service_name,
                    COALESCE(u.name, 'Chưa phân công') as tech_name,
                    
                    -- Tách ngày tháng bằng SQL luôn cho nhanh
                    DAY(b.appointment_time) as day_val,
                    MONTH(b.appointment_time) as month_val,
                    YEAR(b.appointment_time) as year_val,
                    DATE_FORMAT(b.appointment_time, '%H:%i') as time_val,
                    DATE_FORMAT(b.appointment_time, '%d/%m/%Y') as full_date
                    
                FROM bookings b
                LEFT JOIN services s ON b.service_id = s.id
                LEFT JOIN users u ON b.technician_id = u.id
                
                WHERE b.status = 'completed' 
                AND YEAR(b.appointment_time) = $filterYear
            ";

            // Nếu có chọn tháng thì thêm điều kiện lọc tháng
            if ($filterMonth) {
                $sql .= " AND MONTH(b.appointment_time) = $filterMonth";
            }

            $sql .= " ORDER BY b.appointment_time DESC";

            $result = $conn->query($sql);
            $stt = 1;
            $sumRevenue = 0;
            $sumSalary = 0;

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $revenue = (float)$row['final_price'];
                    // Fix lỗi giá tiền nhỏ
                    if ($revenue < 10000) $revenue = $revenue * 1000;
                    
                    $commission = $revenue * 0.3;
                    $salary = $revenue * 0.7;

                    $sumRevenue += $revenue;
                    $sumSalary += $salary;

                    echo "<tr>";
                    echo "<td class='center'>" . $stt++ . "</td>";
                    echo "<td class='center text-string'>#TC-" . $row['id'] . "</td>";
                    
                    // --- CÁC CỘT THỜI GIAN CHI TIẾT ---
                    echo "<td class='date-col'>" . $row['full_date'] . "</td>"; // Cột Ngày Full
                    echo "<td class='center'>" . $row['month_val'] . "</td>";  // Cột Tháng riêng
                    echo "<td class='center'>" . $row['year_val'] . "</td>";   // Cột Năm riêng
                    echo "<td class='center'>" . $row['time_val'] . "</td>";   // Cột Giờ riêng
                    // ----------------------------------

                    echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['service_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['tech_name']) . "</td>";
                    
                    echo "<td class='money'>" . $revenue . "</td>";
                    echo "<td class='money' style='color:#d32f2f;'>" . $commission . "</td>";
                    echo "<td class='money' style='color:#1565C0; font-weight:bold;'>" . $salary . "</td>";
                    echo "</tr>";
                }
                
                // Tổng kết
                echo "<tr style='background-color: #FFF9C4; font-weight: bold;'>";
                echo "<td colspan='9' style='text-align: right; padding-right:20px;'>TỔNG CỘNG ($timeLabel):</td>";
                echo "<td class='money'>" . $sumRevenue . "</td>";
                echo "<td></td>";
                echo "<td class='money' style='color:#1565C0; font-size:1.1em;'>" . $sumSalary . "</td>";
                echo "</tr>";

            } else {
                echo "<tr><td colspan='12' class='center' style='padding:20px;'>Không có dữ liệu cho thời gian này.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>