<?php
session_start();
// 1. Check quyền Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /TechFixPHP/pages/public_page/login.php');
    exit;
}

require_once '../../config/db.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Điều Phối - TechFix Admin</title>
    
    <link href="/TechFixPHP/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        
        /* Sidebar Style */
        .sidebar { min-height: 100vh; background: #343a40; color: white; }
        .sidebar a { color: rgba(255,255,255,.8); text-decoration: none; padding: 12px 20px; display: block; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar a:hover { background: #0d6efd; color: white; }

        /* Calendar Style */
        #calendar {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            min-height: 800px;
        }
        .fc-toolbar-title { font-size: 1.5rem !important; text-transform: uppercase; color: #2c3e50; }
        .fc-button-primary { background-color: #0d6efd !important; border-color: #0d6efd !important; }
        .fc-event { cursor: pointer; border: none; padding: 2px 5px; border-radius: 4px; font-size: 0.9rem; }
        .fc-day-today { background-color: #e8f4ff !important; }
        
        /* Làm mờ các ngày quá khứ để dễ nhìn hơn */
        .fc-day-past { background-color: #f9f9f9; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        
        <div class="col-md-3 col-lg-2 sidebar p-0 collapse d-md-block">
            <?php include __DIR__ . '/template/sidebar.php'; ?>
        </div>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fa-regular fa-calendar-check"></i> Lịch Điều Phối Dịch Vụ</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                        <i class="fa-solid fa-rotate-right"></i> Làm mới
                    </button>
                </div>
            </div>

            <div id='calendar'></div>

        </main>
    </div>
</div>

<script src="/TechFixPHP/assets/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');

        var calendar = new FullCalendar.Calendar(calendarEl, {
            // Cấu hình cơ bản
            initialView: 'dayGridMonth',
            locale: 'vi',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            buttonText: {
                today: 'Hôm nay',
                month: 'Tháng',
                week: 'Tuần',
                list: 'Danh sách'
            },
            
            // Data Source
            events: '../api/get_calendar_events.php',
            
            // TÍNH NĂNG KÉO THẢ (DRAG & DROP)
            editable: true, 
            droppable: true, 

            // --- [QUAN TRỌNG] XỬ LÝ KHI KÉO THẢ ---
            // Tìm đoạn eventDrop trong admin_calendar.php và sửa thành:

eventDrop: function(info) {
    // --- [MỚI] LOGIC CHẶN TRẠNG THÁI ---
    var status = info.event.extendedProps.status;
    
    // Nếu đơn đã Hoàn thành hoặc Đã hủy -> KHÔNG CHO ĐỔI
    if (status === 'completed' || status === 'cancelled') {
        Swal.fire({
            icon: 'warning',
            title: 'Không thể thao tác!',
            text: 'Đơn hàng này đã kết thúc/hủy, không thể đổi lịch được nữa.',
            timer: 2500,
            showConfirmButton: false
        });
        info.revert(); // Quay xe!
        return;
    }
    // ------------------------------------

    // --- LOGIC CHẶN QUÁ KHỨ (CŨ) ---
    var droppedDate = info.event.start;
    var today = new Date();
    today.setHours(0,0,0,0);

    if (droppedDate < today) {
        Swal.fire({
            icon: 'error',
            title: 'Lỗi thời gian!',
            text: 'Không thể chuyển lịch về quá khứ.',
            timer: 2000,
            showConfirmButton: false
        });
        info.revert();
        return;
    }

    // --- HỎI XÁC NHẬN ---
    Swal.fire({
        title: 'Xác nhận đổi lịch',
        html: `Bạn muốn dời đơn <b>${info.event.title}</b><br>sang ngày <b>${info.event.start.toLocaleDateString()}</b>?
               <br><br><small style="color:red">⚠️ Hệ thống sẽ gửi mail báo cho Khách & Thợ.</small>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Đồng ý dời',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            updateEventDate(info.event.id, info.event.startStr);
        } else {
            info.revert();
        }
    });
},

            // Khi click vào sự kiện (Xem chi tiết)
            eventClick: function(info) {
                Swal.fire({
                    title: info.event.title,
                    html: `<b>Thời gian:</b> ${info.event.start.toLocaleString()}<br>
                           <b>Trạng thái:</b> ${info.event.extendedProps.status}<br>
                           <b>Khách hàng:</b> ${info.event.extendedProps.customer}`,
                    icon: 'info'
                });
            },

            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                meridiem: false
            }
        });

        calendar.render();
    });

    // Hàm gửi API cập nhật ngày
    function updateEventDate(id, newDate) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('new_date', newDate);

        fetch('../api/update_calendar_event.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const Toast = Swal.mixin({
                    toast: true, position: 'top-end', showConfirmButton: false, timer: 3000
                });
                Toast.fire({ icon: 'success', title: 'Cập nhật lịch thành công!' });
            } else {
                Swal.fire('Lỗi', 'Không thể cập nhật: ' + data.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Lỗi', 'Lỗi kết nối máy chủ', 'error');
        });
    }
</script>

</body>
</html>