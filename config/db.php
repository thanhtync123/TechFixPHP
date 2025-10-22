<?php
//$conn = new mysqli("localhost", "root", "", "TechFix", 3307);
$conn = new mysqli("localhost", "root", "123456", "hometech_db", 3307);
if ($conn->connect_error) 
    die("Kết nối thất bại: " . $conn->connect_error);
?>
