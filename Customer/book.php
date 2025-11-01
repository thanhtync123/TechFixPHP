<?php
// book.php
session_start();

// Lấy mảng 'user' từ session (giống hệt my_booking.php)
$user_session = $_SESSION['user'] ?? null; 

// Lấy thông tin từ mảng 'user'
// (Hãy đảm bảo các key 'id', 'name', 'phone', 'address' là đúng)
$customer_id = $user_session['id'] ?? ''; 
$customer_name = $user_session['name'] ?? ''; 
$customer_phone = $user_session['phone'] ?? ''; 
$customer_address = $user_session['address'] ?? ''; 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Lịch Thông Minh - TECHFIX</title>
    <link href="/TechFixPHP/assets/css/book.css" rel="stylesheet" />

    <style>
        /* CSS cho toast và form báo giá (Giữ nguyên) */
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
        #smart-results {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        #price-notes {
            background: #fdfae5; 
            padding: 15px; 
            border-radius: 5px; 
            border: 1px solid #e7d8a2; 
            list-style-position: inside;
        }
        .slot {
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            padding: 15px; 
            border: 1px solid #ddd; 
            margin: 10px 0; 
            border-radius: 5px;
        }
        .slot.disabled { 
            background: #f1f1f1; 
            text-decoration: line-through; 
            color: #999; 
        }
        .slot-info strong { 
            font-size: 1.1em; 
            color: #007bff; 
        }
        .slot-info span { 
            display: block; 
            font-size: 0.9em; 
            color: #e67e22; 
        }
        .slot button {
            background: #28a745; 
            color: white; 
            border: none; 
            padding: 10px 15px;
            border-radius: 5px; 
            cursor: pointer; 
            font-weight: bold;
        }
        .slot.disabled button { 
            background: #ccc; 
            cursor: not-allowed; 
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="container flex justify-between items-center">
            <div class="flex items-center">
                <img src="../assets/image/hometech.jpg" alt="Logo"
                     style="width:40px; height:60px; object-fit:contain; margin-right:8px;" />
                <h1 class="logo" style="margin:8px; line-height:60px;">TECHFIX</h1>
            </div>
            <div class="nav-links flex items-center space-x-4">
                <a href="../index.php">Trang Chủ |</a>
                <a href="Service.php">Dịch Vụ |</a>
                <a href="about.html">Về Chúng Tôi |</a>
                <a href="contact.html">Liên Hệ |</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        
        <div class="card">
            <div class="card-header">
                <h4>Đặt lịch dịch vụ thông minh</h4>
                <small>Giá và lịch trống sẽ cập nhật theo lựa chọn của bạn</small>
            </div>
            <div class="card-body">
                <form id="bookingForm" onsubmit="return false;">
                    <h5>Bước 1: Thông tin của bạn</h5>
                    <div class="mb-2">
                        <label>Mã khách hàng</label>
                        <input type="text" id="idCustomer" value="<?php echo htmlspecialchars($customer_id); ?>" class="form-control"  required />
                    </div>
                    <div class="mb-2">
                        <label>Tên khách hàng</label>
                        <input type="text" id="customerName" value="<?php echo htmlspecialchars($customer_name); ?>" class="form-control" required />
                    </div>
                    <div class="mb-2">
                        <label>Số điện thoại</label>
                        <input type="text" id="phone" value="<?php echo htmlspecialchars($customer_phone); ?>" class="form-control" required />
                    </div>
                    <div class="mb-2">
                        <label>Địa chỉ</label>
                        <input type="text" id="address" value="<?php echo htmlspecialchars($customer_address); ?>" class="form-control" required />
                    </div>

                    <h5 style="margin-top: 20px;">Bước 2: Lấy báo giá và lịch trống</h5>
                    <div class="mb-2">
                        <label>Tên dịch vụ</label>
                        <select id="serviceId" class="form-control" onchange="getSmartPrice()" required>
                            <option value="">-- Chọn dịch vụ --</option>
                            <option value="1">Sửa chữa & bảo trì hệ thống điện</option>
                            <option value="2">Lắp đặt & bảo trì điều hòa</option>
                            <option value="3">Sửa chữa tủ lạnh</option>
                            <option value="4">Hỗ trợ kỹ thuật IT</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label>Khu vực của bạn</label>
                        <select id="district" class="form-control" onchange="getSmartPrice()" required>
                            <option value="">-- Chọn khu vực --</option>
                            <option value="Quận 1">Quận 1</option>
                            <option value="Quận 7">Quận 7</option>
                            <option value="Hóc Môn">Hóc Môn (Ngoại thành)</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label>Thời gian hẹn</label>
                        <input type="date" id="appointmentDate" class="form-control" onchange="getSmartPrice()" required />
                    </div>
                </form>

                <div id="smart-results">
                    <p style="text-align:center; color:#888;">Vui lòng chọn 3 mục trên để xem báo giá và lịch trống...</p>
                </div>
            </div>
        </div>

        <div class="services-column">
            <section id="services" class="section">
                <h2 class="section-title">Các Dịch Vụ TECHFIX</h2>
                <div class="slider-container">
                    <div class="slider">
                        <div class="slide-track" id="slideTrack">
                            <div class="slide">
                                <img src="../assets/image/car.jpg" alt="car">
                                <h3>Sửa Chữa & Bảo Trì Xe</h3>
                                <p>Dịch vụ sửa chữa và bảo trì xe chuyên nghiệp...</p>
                            </div>
                            <div class="slide">
                                <img src="../assets/image/pcc.jpg" alt="Sửa Chữa Máy Tính">
                                <h3>Sửa Chữa Máy Tính</h3>
                                <p>Sửa chữa máy tính từ phần cứng đến phần mềm...</p>
                            </div>
                            <div class="slide">
                                <img src="../assets/image/elec.jpg" alt="Electrical">
                                <h3>Sửa chữa & bảo trì hệ thống điện</h3>
                                <p>Dịch vụ điện dân dụng và công nghiệp toàn diện...</p>
                            </div>
                            <div class="slide">
                                <img src="../assets/image/air.jpg" alt="air-conditioned">
                                <h3>Sửa Chữa & Vệ Sinh Điện Lạnh</h3>
                                <p>Vệ sinh, sửa chữa và bảo trì hệ thống điện lạnh...</p>
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
                        <p>TECHFIX cung cấp các giải pháp sáng tạo...</p>
                    </div>
                    <div class="map-container">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d15705.130930698686!2d105.98417167672112!3d10.238765824299422!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2s!4v1759656345208!5m2!1sen!2s"
                            width="600" height="450" style="border:0;" allowfullscreen loading="lazy"></iframe>
                    </div>
                </div>
            </section>
        </div>

    </div> <div class="footer">
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
        // ============== HIỂN THỊ TOAST (THÔNG BÁO) ==============
        function showToast(message, isError = false) {
            const toast = document.getElementById("toast");
            toast.textContent = message;
            toast.style.background = isError ? "#d9534f" : "#28a745";
            toast.classList.add("show");
            setTimeout(() => toast.classList.remove("show"), 3000);
        }

        // ============== LOGIC BÁO GIÁ THÔNG MINH ==============
        function getSmartPrice() {
            const serviceId = document.getElementById('serviceId').value;
            const district = document.getElementById('district').value;
            const date = document.getElementById('appointmentDate').value;
            const resultsDiv = document.getElementById('smart-results');

            if (!serviceId || !district || !date) {
                resultsDiv.innerHTML = '<p style="text-align:center; color:#888;">Vui lòng chọn 3 mục trên...</p>';
                return;
            }

            resultsDiv.innerHTML = '<p style="text-align:center; color:#007bff;">Đang kiểm tra giá và lịch trống...</p>';

            // Gọi file api_price.php
            fetch(`api_price.php?service_id=${serviceId}&district=${district}&date=${date}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        resultsDiv.innerHTML = `<p style="color: red; text-align:center;">${data.error}</p>`;
                        return;
                    }

                    let html = `<h5>Bước 3: Chọn khung giờ</h5>`;
                    
                    if (data.price_notes.length > 0) {
                        html += `<ul id="price-notes"><strong>Ghi chú điều chỉnh giá:</strong>`;
                        data.price_notes.forEach(note => html += `<li>${note}</li>`);
                        html += `</ul>`;
                    }

                    html += `<h5 style="margin-top:20px;">Các khung giờ khả dụng:</h5>`;
                    
                    let hasSlot = false;
                    for (const time in data.available_slots) {
                        const slot = data.available_slots[time];
                        const price = slot.price.toLocaleString('vi-VN');
                        
                        if (slot.available) {
                            hasSlot = true;
                            html += `
                                <div class="slot">
                                    <div class="slot-info">
                                        <strong>${time}</strong>
                                        <span style="color:green; font-weight:bold;">Giá: ${price}đ</span>
                                        ${slot.note ? `<span>(${slot.note})</span>` : ''}
                                    </div>
                                    <button onclick="bookSlot('${time}', ${slot.price})">Chọn và Đặt lịch</button>
                                </div>`;
                        } else {
                            html += `
                                <div class="slot disabled">
                                    <div class="slot-info">
                                        <strong>${time}</strong>
                                        <span>${slot.note}</span>
                                    </div>
                                    <button disabled>Đã hết</button>
                                </div>`;
                        }
                    }

                    if (!hasSlot) {
                        html += '<p style="text-align:center; color:red;">Rất tiếc, đã hết lịch cho ngày này. Vui lòng chọn ngày khác.</p>';
                    }
                    
                    resultsDiv.innerHTML = html;
                })
                .catch(error => {
                    console.error('Lỗi:', error);
                    resultsDiv.innerHTML = "<p style='color: red; text-align:center;'>Không thể kết nối đến máy chủ API.</p>";
                });
        }

        // ============== HÀM GỬI ĐƠN ĐẶT LỊCH ==============
        function bookSlot(time, price) {
            // Lấy thông tin khách hàng từ Bước 1
            const idCustomer = document.getElementById('idCustomer').value;
            const customerName = document.getElementById('customerName').value;
            const phone = document.getElementById('phone').value;
            const address = document.getElementById('address').value;

            // Lấy thông tin dịch vụ từ Bước 2
            const serviceId = document.getElementById('serviceId').value;
            const district = document.getElementById('district').value;
            const date = document.getElementById('appointmentDate').value;

            // Kiểm tra thông tin khách hàng
            if (!idCustomer || !customerName || !phone || !address) {
                showToast("Vui lòng điền đầy đủ thông tin ở Bước 1.", true);
                document.getElementById('customerName').focus();
                return;
            }

            if (!confirm(`Xác nhận đặt lịch lúc ${time} với giá ${price.toLocaleString('vi-VN')}đ?`)) return;

            const bookingData = {
                IdCustomer: idCustomer,
                CustomerName: customerName,
                Phone: phone,
                Address: address,
                District: district,
                ServiceId: serviceId,
                AppointmentDate: date,
                AppointmentTime: time,
                FinalPrice: price
            };

            // Gửi dữ liệu lên submit_booking.php
            fetch('submit_booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(bookingData)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showToast(result.message);
                    getSmartPrice(); // Làm mới lại các slot
                } else {
                    showToast(result.message, true);
                }
            })
            .catch(error => {
                console.error('Lỗi khi gửi:', error);
                showToast("Không thể gửi đơn đặt lịch.", true);
            });
        }

        // ============== HÀM KHỞI TẠO KHI TRANG TẢI (ĐÃ GỘP) ==============
        document.addEventListener("DOMContentLoaded", () => {
            
            // --- 1. LOGIC CỦA SLIDER (TỪ CODE CŨ CỦA BẠN) ---
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
                if (!pagination) return; // Thêm kiểm tra
                [...pagination.children].forEach((dot, i) => {
                    dot.classList.toggle("active", i === index);
                });
            }

            function showSlide(i) {
                if (!track) return; // Thêm kiểm tra
                if (slides.length === 0) return; // Thêm kiểm tra
                
                if (i < 0) index = slides.length - 1;
                else if (i >= slides.length) index = 0;
                else index = i;
                
                track.style.transform = `translateX(-${index * 100}%)`;
                updatePagination();
            }

            if (prevBtn && nextBtn) { // Thêm kiểm tra
                prevBtn.addEventListener("click", () => showSlide(index - 1));
                nextBtn.addEventListener("click", () => showSlide(index + 1));
            }

            if (slides.length > 0) { // Thêm kiểm tra
                setInterval(() => showSlide(index + 1), 5000);
                showSlide(0); // Hiển thị slide đầu tiên
            }

            // --- 2. LOGIC CỦA FORM ĐẶT LỊCH (MỚI) ---
            const today = new Date().toISOString().split('T')[0];
            const dateInput = document.getElementById('appointmentDate');
            if (dateInput) { // Thêm kiểm tra
                dateInput.value = today;
                dateInput.min = today; // Không cho chọn ngày quá khứ
            }
            
            // Tự động gọi nếu người dùng đã đăng nhập và điền sẵn thông tin
            if (document.getElementById('idCustomer') && document.getElementById('idCustomer').value) {
                // (Chỉ gọi khi các trường khác cũng đã có giá trị)
                // getSmartPrice(); 
            }
        });
    </script>
</body>
</html>