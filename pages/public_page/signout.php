<?php
session_start();
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_unset();
    session_destroy();
    header('Location: /login.php');
    exit;
}
?>
