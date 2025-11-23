<?php
session_start();
require_once '../config/db.php';
require_once '../libs/fpdf/fpdf.php'; 

// 1. Kiểm tra quyền
if (!isset($_SESSION['user'])) die("Vui lòng đăng nhập");

$booking_id = $_GET['id'] ?? 0;

// 2. Lấy thông tin đơn hàng
$sql = "SELECT b.*, s.name as service_name, u.name as full_name, u.phone as phone_number, u.address 
        FROM bookings b 
        JOIN services s ON b.service_id = s.id 
        JOIN users u ON b.customer_id = u.id 
        WHERE b.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) die("Không tìm thấy đơn hàng");

// Hàm convert Tiếng Việt (Giữ nguyên của bạn)
function convertToUnsigned($str) {
    if (!$str) return "";
    $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", "a", $str);
    $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", "e", $str);
    $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", "i", $str);
    $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", "o", $str);
    $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", "u", $str);
    $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", "y", $str);
    $str = preg_replace("/(đ)/", "d", $str);
    $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", "A", $str);
    $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", "E", $str);
    $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", "I", $str);
    $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", "O", $str);
    $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", "U", $str);
    $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", "Y", $str);
    $str = preg_replace("/(Đ)/", "D", $str);
    return $str;
}

class PDF extends FPDF {
    function Header() {
        // Logo (Thay đường dẫn ảnh của bạn vào đây)
        // $this->Image('../assets/image/vlute1.png',10,6,30);
        
        // Tên công ty
        $this->SetFont('Arial','B',24);
        $this->SetTextColor(13, 110, 253); // Màu xanh Bootstrap
        $this->Cell(0, 15, 'TECHFIX', 0, 1, 'C');
        
        $this->SetFont('Arial','',10);
        $this->SetTextColor(100);
        $this->Cell(0, 5, '73 Nguyen Hue, Vinh Long - Hotline: 1900 1234', 0, 1, 'C');
        
        // Đường kẻ ngang
        $this->SetDrawColor(200, 200, 200);
        $this->Line(10, 35, 200, 35);
        $this->Ln(15);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(150);
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// --- TIÊU ĐỀ HÓA ĐƠN ---
$pdf->SetFont('Arial','B',18);
$pdf->SetTextColor(50);
$pdf->Cell(0, 10, 'HOA DON THANH TOAN', 0, 1, 'C');
$pdf->Ln(5);

// --- BOX THÔNG TIN ĐƠN HÀNG (Khung xám nhạt) ---
$pdf->SetFillColor(245, 245, 245);
$pdf->Rect(10, 60, 190, 35, 'F'); // Vẽ hình chữ nhật nền

$pdf->SetY(65);
$pdf->SetX(15);

$pdf->SetFont('Arial','B',11);
$pdf->Cell(40, 8, 'Ma Don Hang:', 0, 0);
$pdf->SetFont('Arial','',11);
$pdf->Cell(60, 8, '#' . $booking['id'], 0, 0);

$pdf->SetFont('Arial','B',11);
$pdf->Cell(40, 8, 'Ngay Lap:', 0, 0);
$pdf->SetFont('Arial','',11);
$pdf->Cell(50, 8, date('d/m/Y H:i'), 0, 1);

$pdf->SetX(15);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(40, 8, 'Khach Hang:', 0, 0);
$pdf->SetFont('Arial','',11);
$pdf->Cell(60, 8, convertToUnsigned($booking['full_name']), 0, 0);

$pdf->SetFont('Arial','B',11);
$pdf->Cell(40, 8, 'Trang Thai:', 0, 0);

// Logic thanh toán đã sửa
$is_paid = ($booking['payment_status'] == 'paid') || ($booking['status'] == 'completed');
$payment_text = $is_paid ? 'DA THANH TOAN' : 'CHUA THANH TOAN';
$color = $is_paid ? [40, 167, 69] : [220, 53, 69]; // Xanh hoặc Đỏ

$pdf->SetTextColor($color[0], $color[1], $color[2]);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(50, 8, $payment_text, 0, 1);

$pdf->SetTextColor(0); // Reset màu đen
$pdf->Ln(15);

// --- BẢNG CHI TIẾT ---
// Header bảng
$pdf->SetFont('Arial','B',11);
$pdf->SetFillColor(13, 110, 253); // Xanh đậm
$pdf->SetTextColor(255); // Chữ trắng
$pdf->Cell(10, 10, 'STT', 1, 0, 'C', true);
$pdf->Cell(90, 10, 'Mo Ta Dich Vu', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'So Luong', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Don Gia', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Thanh Tien', 1, 1, 'C', true);

// Dữ liệu bảng
$pdf->SetFont('Arial','',11);
$pdf->SetTextColor(0);
$pdf->SetFillColor(255, 255, 255); // Nền trắng

// Dòng 1: Dịch vụ chính
$pdf->Cell(10, 10, '1', 1, 0, 'C');
$pdf->Cell(90, 10, convertToUnsigned($booking['service_name']), 1, 0, 'L');
$pdf->Cell(30, 10, '1', 1, 0, 'C');
$pdf->Cell(30, 10, number_format($booking['final_price']), 1, 0, 'R');
$pdf->Cell(30, 10, number_format($booking['final_price']), 1, 1, 'R');

// (Nếu có vật tư thì loop ở đây...)

// --- TỔNG CỘNG ---
$pdf->Ln(2);
$pdf->SetFont('Arial','B',13);
$pdf->Cell(130, 10, '', 0, 0);
$pdf->Cell(30, 10, 'TONG:', 0, 0, 'R');
$pdf->SetTextColor(220, 53, 69); // Màu đỏ
$pdf->Cell(30, 10, number_format($booking['final_price']) . ' d', 0, 1, 'R');

// --- FOOTER LỜI CẢM ƠN ---
$pdf->Ln(20);
$pdf->SetTextColor(100);
$pdf->SetFont('Arial','I',10);
$pdf->Cell(0, 6, 'Cam on quy khach da su dung dich vu cua TECHFIX!', 0, 1, 'C');
$pdf->Cell(0, 6, 'Moi thac mac vui long lien he: contact@techfix.vn', 0, 1, 'C');

$pdf->Output('I', 'Hoa_don_' . $booking['id'] . '.pdf');
?>