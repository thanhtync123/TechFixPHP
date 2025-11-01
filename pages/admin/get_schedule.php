<?php
include '../../config/db.php';

if (isset($_GET['technician_id'])) {
    $technician_id = intval($_GET['technician_id']);

    $sql = "SELECT date, start_time, end_time, status 
            FROM technician_schedule 
            WHERE technician_id = ? 
            ORDER BY date ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $technician_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }

    echo json_encode($schedules);
} else {
    echo json_encode(['error' => 'Thiếu ID kỹ thuật viên']);
}
?>
