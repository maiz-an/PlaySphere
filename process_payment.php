<?php
require 'staff/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'];
    $payment_method = $_POST['payment_method'];

    if (!$booking_id || !$payment_method) {
        echo "Invalid payment details.";
        exit;
    }

    try {
        // Update booking payment status
        $stmt = $pdo->prepare("
            UPDATE bookings SET payment_status = 'paid', status = 'confirmed'
            WHERE id = ?
        ");
        $stmt->execute([$booking_id]);

        // Insert payment record
        $stmt = $pdo->prepare("
            INSERT INTO payments (booking_id, payment_method, payment_status, amount)
            SELECT bookings.id AS booking_id, ?, 'paid', 
                   TIMESTAMPDIFF(HOUR, bookings.start_time, bookings.end_time) * futsals.price_per_hour
            FROM bookings 
            JOIN futsals ON bookings.futsal_id = futsals.id
            WHERE bookings.id = ?
        ");
        $stmt->execute([$payment_method, $booking_id]);

        echo "Payment successful! Booking confirmed.";
    } catch (PDOException $e) {
        echo "Error processing payment: " . $e->getMessage();
    }
}
?>
