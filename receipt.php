<?php
session_start();

// Validate receipt details
if (!isset($_SESSION['receipt_details'])) {
    header("Location: booking.php");
    exit();
}

$receiptDetails = $_SESSION['receipt_details'];
unset($_SESSION['receipt_details']); // Clear session after use

$receiptData = <<<EOT
Booking Receipt
====================
Customer ID: {$receiptDetails['customer_id']}
Futsal ID: {$receiptDetails['futsal_id']}
Start Time: {$receiptDetails['start_time']}
End Time: {$receiptDetails['end_time']}
Total Cost: {$receiptDetails['total_cost']}
Payment ID: {$receiptDetails['payment_id']}
====================
Thank you for your payment!
EOT;

$fileName = "Booking_Receipt_{$receiptDetails['payment_id']}.txt";
header('Content-Type: text/plain');
header("Content-Disposition: attachment; filename=\"$fileName\"");

// Output the receipt data
echo $receiptData;
exit();
