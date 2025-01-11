<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'];

    $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed', payment_status = 'paid' WHERE id = ?");
    $stmt->execute([$booking_id]);

    echo json_encode(['success' => true]);
}
?>
