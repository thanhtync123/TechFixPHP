<?php
// config/log_helper.php

function writeLog($conn, $action, $target_id = 0) {
    if (isset($_SESSION['user'])) {
        $user_id = $_SESSION['user']['id'];
        $user_name = $_SESSION['user']['name'];
        $role = $_SESSION['role'];
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $stmt = $conn->prepare("INSERT INTO system_logs (user_id, user_name, role, action, target_id, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssis", $user_id, $user_name, $role, $action, $target_id, $ip);
        $stmt->execute();
    }
}
?>