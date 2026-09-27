<?php
session_start();

// Agar hospital (role = Hospital) login hai
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Hospital') {
    header('Location: login.php');
    exit;
}

// Optional: Load hospital-specific data if needed
$hospital_id = $_SESSION['user_id']; // users.id = hospital's id
$hospital_name = $_SESSION['name'];
?>