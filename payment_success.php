<?php
session_start();
require 'vendor/autoload.php';
require 'staff/db.php';

use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize PayPal API Context
$apiContext = new ApiContext(
    new OAuthTokenCredential(
        'AWB-uJNAy7dIng1crd9LOsjNiMB3fAfZi91-O1NYy323rHGrjLFFmdByjhNdZ30ldfRLyXsRKU3dLXT4',
        'EJ8nufZKYZXbj9EUwVAcROOFTNnA3f-8YmbzcJSJElEXuk3Nop-KsEyWEwZL5-92ir4Als4y8QtzmhBU'
    )
);
$apiContext->setConfig(['mode' => 'sandbox']);
if (!isset($_GET['paymentId'], $_GET['PayerID'])) {
    header("Location: booking.php");
    exit();
}

$paymentId = $_GET['paymentId'];
$payerId = $_GET['PayerID'];

try {
    if (!isset($_SESSION['booking_details'], $_SESSION['customer_id'])) {
        throw new Exception("Session data missing. Please try again.");
    }

    $bookingDetails = $_SESSION['booking_details'];
    $customerId = $_SESSION['customer_id'];

    // Execute PayPal Payment
    $payment = Payment::get($paymentId, $apiContext);
    $execution = new PaymentExecution();
    $execution->setPayerId($payerId);

    $result = $payment->execute($execution, $apiContext);

    if ($result->getState() === 'approved') {
        // Insert booking into the database
        $stmt = $pdo->prepare(
            "INSERT INTO bookings (futsal_id, customer_id, start_time, end_time, status, payment_status, total_cost) 
             VALUES (:futsal_id, :customer_id, :start_time, :end_time, 'confirmed', 'paid', :total_cost)"
        );
        $stmt->execute([
            'futsal_id' => $bookingDetails['futsal_id'],
            'customer_id' => $customerId,
            'start_time' => $bookingDetails['start_time'],
            'end_time' => $bookingDetails['end_time'],
            'total_cost' => $bookingDetails['total_cost'],
        ]);
        $bookingId = $pdo->lastInsertId();

        // Fetch customer email
        $customerStmt = $pdo->prepare("SELECT email FROM users WHERE id = :id");
        $customerStmt->execute(['id' => $customerId]);
        $customerEmail = $customerStmt->fetchColumn();

        // Fetch futsal owner details
        $ownerStmt = $pdo->prepare(
            "SELECT u.email, f.name AS futsal_name FROM futsals f
             JOIN users u ON f.owner_id = u.id
             WHERE f.id = :futsal_id"
        );
        $ownerStmt->execute(['futsal_id' => $bookingDetails['futsal_id']]);
        $ownerDetails = $ownerStmt->fetch(PDO::FETCH_ASSOC);
        $ownerEmail = $ownerDetails['email'];
        $futsalName = $ownerDetails['futsal_name'];

        // Create `receipts` directory if it does not exist
        $receiptsDir = 'receipts';
        if (!is_dir($receiptsDir)) {
            if (!mkdir($receiptsDir, 0777, true) && !is_dir($receiptsDir)) {
                throw new Exception("Failed to create receipts directory.");
            }
        }

        $html = "
<style>
    body {
        font-family: 'Poppins', sans-serif;
        margin: 0;
        padding: 0;
        color: #fff;
        background-color: #161313;
    }
    .container {
        max-width: 600px;
        margin: 20px auto;
        padding: 20px;
        background: rgba(0, 0, 0, 0.85);
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        text-align: center;
        color: #fff;
    }
    .container h1 {
        font-size: 2.5rem;
        color: #bbd12b;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }
    .container p {
        font-size: 1rem;
        color: #ddd;
        line-height: 1.8;
        margin-bottom: 2rem;
    }
    .details {
        margin-top: 20px;
        text-align: left;
        border-collapse: collapse;
        width: 100%;
    }
    .details td {
        padding: 10px;
        border: 1px solid #444;
        font-size: 0.9rem;
        color: #ddd;
    }
    .details td:first-child {
        font-weight: bold;
        color: #bbd12b;
    }
    .footer {
        margin-top: 20px;
        font-size: 0.8rem;
        color: #888;
    }
</style>
<div class='container'>
    <h1>Booking Receipt</h1>
    <p>Thank you for booking with PlaySphere! Below are your booking details:</p>
    <table class='details'>
        <tr><td>Booking ID:</td><td>{$bookingId}</td></tr>
        <tr><td>Futsal:</td><td>{$futsalName}</td></tr>
        <tr><td>Start Time:</td><td>{$bookingDetails['start_time']}</td></tr>
        <tr><td>End Time:</td><td>{$bookingDetails['end_time']}</td></tr>
        <tr><td>Total Cost:</td><td>Rs. {$bookingDetails['total_cost']}</td></tr>
        <tr><td>Payment ID:</td><td>{$paymentId}</td></tr>
    </table>
    <p class='footer'>Need help? Contact us at <a href='mailto:Rizkhanrk01@gmail.com' style='color: #bbd12b;'>support@playsphere.com</a></p>
</div>";


        $mpdf = new \Mpdf\Mpdf(['tempDir' => 'C:/wamp64/www/PlaySphere/tmp']);
        $mpdf->WriteHTML($html);
        $pdfFilePath = "{$receiptsDir}/Booking_Receipt_{$paymentId}.pdf";
        $mpdf->Output($pdfFilePath, \Mpdf\Output\Destination::FILE);

        // Send Email Notifications
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'Rizkhanrk01@gmail.com';
            $mail->Password = 'kzbgnttfcpxrnvci';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Email to Customer
            $mail->setFrom('Rizkhanrk01@gmail.com', 'PlaySphere');
            $mail->addAddress($customerEmail);
            $mail->addAttachment($pdfFilePath);

            $mail->isHTML(true);
            $mail->Subject = 'Booking Receipt';
            $mail->Body = 'Thank you for your booking! Please find your receipt attached.';
            $mail->send();

            // Email to Futsal Owner
            $mail->clearAddresses();
            $mail->addAddress($ownerEmail);
            $mail->Subject = 'New Booking Notification';
            $mail->Body = "A new booking has been made for your futsal: {$futsalName}. Please find the receipt attached.";
            $mail->send();
        } catch (Exception $e) {
            error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        }

        // Clear session and redirect
        unset($_SESSION['booking_details']);
        header("Location: home.php#booking-section");

        exit();
    }
} catch (Exception $e) {
    error_log("Error in PayPal Callback: " . $e->getMessage());
    echo "An error occurred: " . htmlspecialchars($e->getMessage());
}
