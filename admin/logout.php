<?php
session_start();
require 'db.php'; // Ensure this path is correct for your project structure

// Check if the user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php#login-section");
    exit();
}

// Validate the $pdo connection
if (!isset($pdo)) {
    die('Database connection not established.');
}

// Fetch all users from the database
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
$query = "SELECT id, name, username, email, phone_number, role, created_at FROM users";
if (!empty($roleFilter)) {
    $query .= " WHERE role = ?";
}
$stmt = $pdo->prepare($query);
if (!empty($roleFilter)) {
    $stmt->execute([$roleFilter]);
} else {
    $stmt->execute();
}
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle creating a new staff user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_staff'])) {
    $name = htmlspecialchars($_POST['name']);
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $phone_number = htmlspecialchars($_POST['phone_number']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'staff';

    $insertQuery = "INSERT INTO users (name, username, email, phone_number, password_hash, role) 
                    VALUES (?, ?, ?, ?, ?, ?)";
    $insertStmt = $pdo->prepare($insertQuery);
    $insertStmt->execute([$name, $username, $email, $phone_number, $password, $role]);
    header("Location: admin.php");
    exit();
}

// Handle logout confirmation
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    session_unset();
    session_destroy();
    header("Location: ../index.php");
    exit();
}
?>
