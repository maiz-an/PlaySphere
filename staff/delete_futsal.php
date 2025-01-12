<?php
session_start();
require 'db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../index.php#login-section");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['futsal_id'])) {
    $futsalId = (int)$_POST['futsal_id'];
    $staffId = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT * FROM futsals WHERE id = ? AND owner_id = ?");
    $stmt->execute([$futsalId, $staffId]);
    $futsal = $stmt->fetch();

    if ($futsal) {
        $stmt = $pdo->prepare("DELETE FROM futsals WHERE id = ?");
        $stmt->execute([$futsalId]);

        if ($futsal['image'] && file_exists('../' . $futsal['image'])) {
            unlink('../' . $futsal['image']);
        }

        header("Location: futsal.php?success=Futsal deleted successfully");
        exit();
    } else {
        header("Location: futsal.php?error=Futsal not found or unauthorized action");
        exit();
    }
} else {
    header("Location: futsal.php?error=Invalid request");
    exit();
}
