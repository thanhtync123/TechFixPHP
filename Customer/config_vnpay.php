<?php
// /TechFixPHP/Customer/config_vnpay.php

date_default_timezone_set('Asia/Ho_Chi_Minh');

/*
 * Cấu hình VNPay Sandbox
 */
$vnp_TmnCode = "6EDNS6NT"; // Website ID (Mã demo chung)
$vnp_HashSecret ="J1EZA9K11FM2EVTFK0VB1N0A4EX3W8X9"; // Chuỗi bí mật (Mã demo chung)
$vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
// QUAN TRỌNG: Sửa đường dẫn này đúng với máy của bạn (localhost:8080 hay localhost?)
$vnp_Returnurl = "http://localhost:8080/TechFixPHP/Customer/vnpay_return.php"; 
$vnp_apiUrl = "https://sandbox.vnpayment.vn/merchantv2/";

// Config input format
//Expire
$startTime = date("YmdHis");
$expire = date('YmdHis',strtotime('+15 minutes',strtotime($startTime)));
?>