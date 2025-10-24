<?php
// book.php

// ==========================
// 1️⃣ XỬ LÝ GỬI FORM (PHP)
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $idCustomer = $_POST['IdCustomer'] ?? '';
    $customerName = $_POST['CustomerName'] ?? '';
    $phone = $_POST['Phone'] ?? '';
    $address = $_POST['Address'] ?? '';
    $serviceId = $_POST['ServiceId'] ?? '';
    $appointmentTime = $_POST['AppointmentTime'] ?? '';
    $status = 'pending';

    // ✅ Kết nối CSDL (chỉnh lại thông tin phù hợp)
    $conn = new mysqli("localhost", "root", "", "hometech_db");
    if ($conn->connect_error) {
        die("Kết nối thất bại: " . $conn->connect_error);
    }

    // ✅ Thêm dữ liệu vào bảng bookings
    $stmt = $conn->prepare("INSERT INTO bookings (customer_id, customer_name, phone, address, service_id, appointment_time, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssiss", $idCustomer, $customerName, $phone, $address, $serviceId, $appointmentTime, $status);

    if ($stmt->execute()) {
        echo "<script>
                alert('Đặt lịch thành công! Kỹ thuật viên sẽ liên hệ với bạn sớm.');
                window.location.href = '../index.html';
              </script>";
    } else {
        echo "<script>
                alert('Lỗi khi lưu đơn đặt lịch!');
              </script>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Lịch - TECHFIX</title>
    <link href="/TechFixPHP/assets/css/book.css" rel="stylesheet" />

    <style>
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #333;
            color: #fff;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            opacity: 0;
            transition: opacity 0.4s;
            z-index: 9999;
        }
        .toast.show { opacity: 1; }
        .validation-message {
            color: #d9534f;
            font-size: 0.9rem;
            margin-top: 4px;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="container flex justify-between items-center">
            <div class="flex items-center">
                <img src="../assets/image/hometech.jpg" alt="Logo"
                     style="width:40px; height:60px; object-fit:contain; margin-right:8px;" />
                <h1 class="logo" style="margin:8px; display:inline-block; line-height:60px;">TECHFIX</h1>
            </div>
            <div class="nav-links flex items-center space-x-4">
                <a href="../index.html">Trang Chủ |</a>
                <a href="Service.php">Dịch Vụ |</a>
                <a href="about.html">Về Chúng Tôi |</a>
                <a href="contact.html">Liên Hệ |</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="card">
            <div class="card-header">
                <h4>Đặt lịch dịch vụ</h4>
                <small>Trải nghiệm dịch vụ cao cấp ngay hôm nay</small>
            </div>
            <div class="card-body">
                <form id="bookingForm" method="POST">
                    <div class="mb-2">
                        <label>Mã khách hàng</label>
                        <input type="number" id="idCustomer" name="IdCustomer" class="form-control" value="123" readonly />
                    </div>
                    <div class="mb-2">
                        <label>Tên khách hàng</label>
                        <input type="text" id="customerName" name="CustomerName" class="form-control" value="Tên Khách Hàng Mẫu" readonly />
                    </div>
                    <div class="mb-2">
                        <label>Số điện thoại</label>
                        <input type="text" id="phone" name="Phone" class="form-control" value="0901234567" readonly />
                    </div>
                    <div class="mb-2">
                        <label>Địa chỉ</label>
                        <input type="text" id="address" name="Address" class="form-control" value="123 Đường ABC, Phường XYZ, Quận 1" readonly />
                    </div>
                    <div class="mb-2">
                        <label>Tên dịch vụ</label>
                        <select id="serviceId" name="ServiceId" class="form-control" required>
                            <option value="">-- Chọn dịch vụ --</option>
                            <option value="1">Sửa chữa & bảo trì hệ thống điện: 150,000</option>
                            <option value="2">Lắp đặt & bảo trì điều hòa: 300,000</option>
                            <option value="3">Sửa chữa tủ lạnh: 250,000</option>
                            <option value="4">Hỗ trợ kỹ thuật IT: 200,000</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Thời gian hẹn</label>
                        <input type="date" id="appointmentTime" name="AppointmentTime" class="form-control" required />
                    </div>
                    <button type="submit" class="btn btn-success">Đặt lịch ngay</button>
                </form>
            </div>
        </div>

        <!-- ==============================
             CÁC DỊCH VỤ + SLIDER + MAP
        =============================== -->
        <div class="services-column">
            <section id="services" class="section">
                <h2 class="section-title">Các Dịch Vụ TECHFIX</h2>
                <div class="slider-container">
                    <div class="slider">
                        <div class="slide-track" id="slideTrack">
                            <div class="slide">
                                <img src="../assets/image/car.jpg" alt="car">
                                <h3>Sửa Chữa & Bảo Trì Xe</h3>
                                <p>Dịch vụ sửa chữa và bảo trì xe chuyên nghiệp, giúp phương tiện của bạn vận hành êm ái, an toàn và tiết kiệm chi phí.</p>
                            </div>
                            <div class="slide">
                                <img src="../assets/image/pcc.jpg" alt="Sửa Chữa Máy Tính">
                                <h3>Sửa Chữa Máy Tính</h3>
                                <p>Sửa chữa máy tính từ phần cứng đến phần mềm, xử lý sự cố chậm, treo máy, hỏng hóc linh kiện.</p>
                            </div>
                            <div class="slide">
                                <img src="../assets/image/elec.jpg" alt="Electrical">
                                <h3>Sửa chữa & bảo trì hệ thống điện</h3>
                                <p>Dịch vụ điện dân dụng và công nghiệp toàn diện: sửa chữa, lắp đặt, bảo trì hệ thống điện.</p>
                            </div>
                            <div class="slide">
                                <img src="../assets/image/air.jpg" alt="air-conditioned">
                                <h3>Sửa Chữa & Vệ Sinh Điện Lạnh</h3>
                                <p>Vệ sinh, sửa chữa và bảo trì hệ thống điện lạnh định kỳ, giúp máy lạnh hoạt động hiệu quả hơn.</p>
                            </div>
                        </div>
                    </div>
                    <button class="control-btn" id="prevBtn">❮</button>
                    <button class="control-btn" id="nextBtn">❯</button>
                    <div class="pagination" id="pagination"></div>
                </div>

                <div class="more-btn-container" style="text-align: center; margin-top: 20px;">
                    <a href="services.php" class="more-btn">Tìm Hiểu Thêm</a>
                </div>

                <div class="company-info">
                    <img src="../assets/image/hometech.jpg" alt="Techfix Logo" class="company-logo" />
                    <div class="company-description">
                        <h3>Về TECHFIX</h3>
                        <p>TECHFIX cung cấp các giải pháp sáng tạo và dịch vụ sửa chữa chất lượng cao trong lĩnh vực kỹ thuật & công nghệ.</p>
                    </div>
                    <div class="map-container">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d15705.130930698686!2d105.98417167672112!3d10.238765824299422!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2s!4v1759656345208!5m2!1sen!2s"
                            width="600" height="450" style="border:0;" allowfullscreen loading="lazy"></iframe>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="footer">
        <div>
            <h3>TECHFIX</h3>
            <p>ĐẶT NIỀM TIN - TRAO CHỮ TÍN</p>
        </div>
        <div>
            <h3>Liên Kết Nhanh</h3>
            <a href="#services">Dịch Vụ</a> |
            <a href="#about">Về Chúng Tôi</a> |
            <a href="#contact">Liên Hệ</a>
        </div>
        <div>
            <h3>Thông Tin Liên Hệ</h3>
            <p>Email: support@techfix.com</p>
            <p>Điện thoại: +84 123 456 789</p>
            <p>Địa chỉ: P4 Phạm Thái Bường</p>
        </div>
        <p class="copy">© 2025 TECHFIX. All rights reserved.</p>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        // ================= SLIDER =================
        document.addEventListener("DOMContentLoaded", function () {
            const track = document.getElementById("slideTrack");
            const slides = track ? track.children : [];
            const prevBtn = document.getElementById("prevBtn");
            const nextBtn = document.getElementById("nextBtn");
            const pagination = document.getElementById("pagination");
            let index = 0;

            if (pagination && slides.length > 0) {
                pagination.innerHTML = "";
                for (let i = 0; i < slides.length; i++) {
                    const dot = document.createElement("span");
                    dot.addEventListener("click", () => showSlide(i));
                    pagination.appendChild(dot);
                }
            }

            function updatePagination() {
                [...pagination.children].forEach((dot, i) => {
                    dot.classList.toggle("active", i === index);
                });
            }

            function showSlide(i) {
                if (i < 0) index = slides.length - 1;
                else if (i >= slides.length) index = 0;
                else index = i;
                track.style.transform = `translateX(-${index * 100}%)`;
                updatePagination();
            }

            prevBtn.addEventListener("click", () => showSlide(index - 1));
            nextBtn.addEventListener("click", () => showSlide(index + 1));

            setInterval(() => showSlide(index + 1), 5000);
            showSlide(0);
        });

        // ============== AUTO DATE + TOAST ============
        const today = new Date();
        document.getElementById('appointmentTime').value =
            today.toISOString().split('T')[0];
    </script>
</body>
</html>
