<?php

// $conn = new mysqli("localhost", "root", "", "hometech_db", 3306);
$conn = new mysqli("localhost", "root", "", "hometech_db", 3306);
if ($conn->connect_error)
    die("Kết nối thất bại: " . $conn->connect_error);
function asset($path)
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
        || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
    return $protocol . $host . $basePath . ltrim($path, '/');
}
?>