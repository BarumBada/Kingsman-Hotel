<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];

if ($role == 'admin') {
    include 'admin/index.php';
} else {
    include 'user/index.php';
}
?>