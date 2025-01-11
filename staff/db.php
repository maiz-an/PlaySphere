<?php
$host = 'localhost';
$db = 'playsphere';
$username = 'root';
$password = '';

// Establish a PDO connection
try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // Display error message and terminate script
    die("Database connection failed: " . $e->getMessage());
}
