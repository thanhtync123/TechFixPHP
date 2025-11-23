<?php
// api_get_districts.php
header('Content-Type: application/json');
require_once '../config/db.php'; 

// Nhận mã code gửi lên (đổi tên biến cho dễ hiểu)
$province_code = $_GET['province_id'] ?? ''; 

$districts = [];

if (!empty($province_code)) { // Kiểm tra không rỗng
    try {
        // SỬA 1: WHERE province_code (đúng tên cột trong ảnh)
        // SỬA 2: Lấy cột 'name' (đúng tên cột trong ảnh)
        $sql = "SELECT name FROM districts WHERE province_code = ? ORDER BY name ASC";
        
        $stmt = $conn->prepare($sql);
        
        // SỬA 3: Đổi "i" (integer) thành "s" (string) vì mã tỉnh là varchar
        $stmt->bind_param("s", $province_code);
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $districts[] = $row; 
        }
        $stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode($districts);
?>