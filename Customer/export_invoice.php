<?php
// File: /TechFixPHP/Customer/export_invoice.php
session_start();
require_once '../config/db.php';

// Định nghĩa đường dẫn font
define('FPDF_FONTPATH', __DIR__ . '/../libs/fpdf/font/');
require_once '../libs/fpdf/fpdf.php'; 

// 1. Kiểm tra quyền
if (!isset($_SESSION['user'])) {
    die("Vui lòng đăng nhập");
}

$booking_id = $_GET['id'] ?? 0;

// 2. Lấy thông tin đơn hàng (CÂU SQL ĐÃ CHỈNH CHUẨN THEO ẢNH CSDL)
// - u.name AS full_name: Lấy cột 'name' nhưng đặt tên giả là 'full_name'
// - u.phone AS phone_number: Lấy cột 'phone' nhưng đặt tên giả là 'phone_number'
$sql = "SELECT b.*, s.name as service_name, u.name as full_name, u.phone as phone_number, u.address 
        FROM bookings b 
        JOIN services s ON b.service_id = s.id 
        JOIN users u ON b.customer_id = u.id 
        WHERE b.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) die("Không tìm thấy đơn hàng hoặc lỗi truy vấn");

// Hàm chuyển đổi Tiếng Việt có dấu -> Không dấu
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

// 3. Tạo PDF
class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',20);
        $this->SetTextColor(0, 123, 255); 
        $this->Cell(0,10,'TECHFIX - HOA DON DIEN TU',0,1,'C');
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(128);
        $this->Cell(0,10,'Trang '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// --- THÔNG TIN ĐƠN HÀNG ---
$pdf->SetFont('Arial','',12);
$pdf->SetTextColor(0);

$pdf->Cell(0, 10, 'Ma Don Hang: #' . $booking['id'], 0, 1);
$pdf->Cell(0, 10, 'Ngay Tao: ' . date('d/m/Y H:i', strtotime($booking['created_at'])), 0, 1);
$pdf->Cell(0, 10, 'Trang Thai Thanh Toan: ' . ($booking['payment_status'] == 'paid' ? 'DA THANH TOAN' : 'CHUA THANH TOAN'), 0, 1);
$pdf->Ln(5);

// --- THÔNG TIN KHÁCH HÀNG ---
$pdf->SetFont('Arial','B',14);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(0, 10, ' THONG TIN KHACH HANG', 0, 1, 'L', true);
$pdf->SetFont('Arial','',12);
$pdf->Ln(2);

// Sử dụng key 'full_name' (đã được alias từ 'name' trong SQL)
$pdf->Cell(40, 10, 'Ho Ten:', 0, 0);
$pdf->Cell(0, 10, convertToUnsigned($booking['full_name']), 0, 1);

// Sử dụng key 'phone_number' (đã được alias từ 'phone' trong SQL)
$pdf->Cell(40, 10, 'So Dien Thoai:', 0, 0);
$pdf->Cell(0, 10, $booking['phone_number'], 0, 1);

$pdf->Cell(40, 10, 'Dia Chi:', 0, 0);
$pdf->MultiCell(0, 10, convertToUnsigned($booking['address'])); 
$pdf->Ln(5);

// --- CHI TIẾT DỊCH VỤ ---
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(0, 123, 255);
$pdf->SetTextColor(255); 

$pdf->Cell(100, 10, 'Dich Vu', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Ngay Hen', 1, 0, 'C', true);
$pdf->Cell(50, 10, 'Thanh Tien (VND)', 1, 1, 'C', true);

$pdf->SetFont('Arial','',12);
$pdf->SetTextColor(0); 

$pdf->Cell(100, 10, convertToUnsigned($booking['service_name']), 1, 0);
$pdf->Cell(40, 10, date('d/m/Y', strtotime($booking['appointment_time'])), 1, 0, 'C');
$pdf->Cell(50, 10, number_format($booking['final_price']), 1, 1, 'R');

// --- TỔNG TIỀN ---
$pdf->Ln(5);
$pdf->SetFont('Arial','B',14);
$pdf->Cell(140, 10, 'TONG CONG:', 0, 0, 'R');
$pdf->SetTextColor(220, 53, 69); 
$pdf->Cell(50, 10, number_format($booking['final_price']) . ' VND', 0, 1, 'R');

// --- LỜI CẢM ƠN ---
$pdf->Ln(20);
$pdf->SetFont('Arial','I',11);
$pdf->SetTextColor(100);
$pdf->MultiCell(0, 8, "Cam on quy khach da su dung dich vu cua TECHFIX.\nDay la hoa don dien tu, co gia tri luu hanh noi bo.\nHotline ho tro: 1900 1234", 0, 'C');

$pdf->Output('I', 'Hoa_don_TECHFIX_' . $booking['id'] . '.pdf');
?>