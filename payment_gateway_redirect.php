<?php
session_start();
if (!isset($_SESSION['booking_details'])) {
    header("Location: booking_page.php");
    exit();
}

$booking_details = $_SESSION['booking_details'];

// Simulate payment gateway page
echo "<h1>Redirecting to Payment Gateway...</h1>";
echo "<p>Total Cost: Rs. " . number_format($booking_details['total_cost'], 2) . "</p>";
echo '<a href="payment_success.php?status=success">Simulate Successful Payment</a>';
