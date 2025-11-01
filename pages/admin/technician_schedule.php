<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public_page/admin/login.php");
    exit();
}
include __DIR__ . '/../../config/db.php';

// Lấy danh sách kỹ thuật viên
$technicians = $conn->query("SELECT id, name FROM users WHERE role = 'technical'");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch làm việc kỹ thuật viên - TECHFIX</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        body { background: #f5f6fa; font-family: 'Poppins', sans-serif; }
        .container { max-width: 1200px; margin: 40px auto; background: white; border-radius: 10px; padding: 20px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        select { padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: center; }
        th { background-color: #0099ff; color: white; }
        .status { padding: 5px 10px; border-radius: 5px; color: white; font-weight: 600; }
        .pending { background-color: #ff9800; }
        .confirmed { background-color: #4caf50; }
        .completed { background-color: #2196f3; }
        .cancelled { background-color: #f44336; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Lịch làm việc của kỹ thuật viên</h2>

        <label for="technician">Chọn kỹ thuật viên: </label>
        <select id="technician" onchange="loadSchedule()">
            <option value="">-- Chọn kỹ thuật viên --</option>
            <?php while ($t = $technicians->fetch_assoc()): ?>
                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
            <?php endwhile; ?>
        </select>

        <div id="scheduleContainer"></div>
    </div>

    <script>
        function loadSchedule() {
            const techId = document.getElementById('technician').value;
            const container = document.getElementById('scheduleContainer');
            if (!techId) {
                container.innerHTML = '';
                return;
            }

            fetch('fetch_schedule.php?technician_id=' + techId)
                .then(res => res.text())
                .then(data => {
                    container.innerHTML = data;
                });
        }
    </script>
</body>
</html>
