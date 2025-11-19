<?php
header('Content-Type: application/json');
require_once '../config/db.php'; // Đảm bảo $conn được khởi tạo

$province_id = $_GET['province_id'] ?? 0;
$districts = [];

if ($province_id > 0) {
    try {
        // Sử dụng prepared statement để chống SQL Injection
        $stmt = $conn->prepare("SELECT name FROM districts WHERE province_id = ? ORDER BY name ASC");
        $stmt->bind_param("i", $province_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $districts[] = $row; // Trả về mảng các object, ví dụ: [{"name": "Quận 1"}, {"name": "Quận 7"}]
        }
        $stmt->close();
        
    } catch (Exception $e) {
        // Xử lý lỗi
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode($districts);
?>