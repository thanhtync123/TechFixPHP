<?php
// /TechFixPHP/Customer/vnpay_create_payment.php
session_start();
require_once '../config/db.php';
require_once 'config_vnpay.php';

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? null) !== 'customer') {
    die('Bạn cần đăng nhập.');
}

$booking_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($booking_id <= 0) {
    die("Không tìm thấy đơn hàng");
}

$sql = "SELECT * FROM bookings WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking || (int) $booking['customer_id'] !== (int) $_SESSION['user']['id']) {
    die("Đơn hàng không tồn tại hoặc bạn không có quyền.");
}
if ($booking['payment_status'] === 'paid') {
    die("Đơn hàng đã được thanh toán.");
}

$chargeAmount = (float) ($booking['final_price'] ?? 0);
if ($chargeAmount <= 0) {
    die("Đơn hàng chưa có giá để thanh toán.");
}

$vnp_TxnRef   = $booking['id'];
$vnp_OrderInfo = "Thanh toan don hang TechFix #" . $booking['id'];
$vnp_OrderType = "billpayment";
$vnp_Amount    = $chargeAmount * 100;
$vnp_Locale    = "vn";
$vnp_BankCode  = "NCB";
$vnp_IpAddr    = $_SERVER['REMOTE_ADDR'];

$inputData = [
    "vnp_Version"   => "2.1.0",
    "vnp_TmnCode"   => $vnp_TmnCode,
    "vnp_Amount"    => $vnp_Amount,
    "vnp_Command"   => "pay",
    "vnp_CreateDate"=> date("YmdHis"),
    "vnp_CurrCode"  => "VND",
    "vnp_IpAddr"    => $vnp_IpAddr,
    "vnp_Locale"    => $vnp_Locale,
    "vnp_OrderInfo" => $vnp_OrderInfo,
    "vnp_OrderType" => $vnp_OrderType,
    "vnp_ReturnUrl" => $vnp_Returnurl,
    "vnp_TxnRef"    => $vnp_TxnRef
];

ksort($inputData);
$query = "";
$hashdata = "";
$i = 0;
foreach ($inputData as $key => $value) {
    $hashdata .= ($i ? '&' : '') . urlencode($key) . "=" . urlencode($value);
    $query    .= urlencode($key) . "=" . urlencode($value) . '&';
    $i = 1;
}

$vnp_Url = $vnp_Url . "?" . $query;
if (!empty($vnp_HashSecret)) {
    $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
    $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
}

header('Location: ' . $vnp_Url);
exit;
?>