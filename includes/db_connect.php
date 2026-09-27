<?php

$host = getenv('MYSQLHOST') ?: 'localhost';
$port = getenv('MYSQLPORT') ?: '3306';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$db   = getenv('MYSQLDATABASE') ?: 'vaxify';

$conn = new mysqli(
    $host,
    $user,
    $pass,
    $db,
    (int) $port
);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Unable to connect to the database. Please try again later.");
}

$conn->set_charset("utf8mb4");
