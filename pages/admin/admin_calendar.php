<?php
session_start();
// Kiểm tra Admin (giống các trang admin khác)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TechFixPHP/pages/public_page/login.php');
    exit;
}

// Include sidebar
include __DIR__ . '/template/sidebar.php'; 
?>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.js'></script>
<style>
    /* CSS tùy chỉnh cho Lịch */
    #calendar {
        max-width: 1100px;
        margin: 20px auto;
        padding: 20px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    /* Sửa màu chữ tiêu đề lịch cho dễ đọc */
    .fc-event-title {
        color: white;
        font-weight: 500;
    }
</style>

<main class="main-content">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Lịch làm việc (Dạng Lịch)</h1>
    </div>

    <div id='calendar'></div>

</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');

        var calendar = new FullCalendar.Calendar(calendarEl, {
            // Giao diện
            initialView: 'dayGridMonth', // Hiển thị dạng tháng
            locale: 'vi', // Ngôn ngữ Tiếng Việt
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek' // Các nút chuyển đổi view
            },
            
            // Dữ liệu (Quan trọng nhất)
            events: '../api/get_calendar_events.php', // Đường dẫn tới file API ta tạo ở Bước 1
            
            // Sự kiện
            eventTimeFormat: { // Hiển thị giờ (VD: 09:00)
                hour: '2-digit',
                minute: '2-digit',
                meridiem: false
            }
        });

        // Vẽ cái lịch ra
        calendar.render();
    });
</script>