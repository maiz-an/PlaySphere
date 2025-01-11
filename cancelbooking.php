<?php
session_start();
require 'staff/db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Mpdf\Mpdf;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $bookingId = $_POST['booking_id'];
    $customerId = $_SESSION['customer_id'] ?? null;

    if ($customerId) {
        try {
            // Fetch booking details
            $stmt = $pdo->prepare("
                SELECT b.*, f.name AS futsal_name, f.owner_id, u.email AS owner_email, u.name AS owner_name, c.email AS customer_email, c.name AS customer_name
                FROM bookings b
                JOIN futsals f ON b.futsal_id = f.id
                JOIN users u ON f.owner_id = u.id
                JOIN users c ON b.customer_id = c.id
                WHERE b.id = :booking_id AND b.customer_id = :customer_id
            ");
            $stmt->execute(['booking_id' => $bookingId, 'customer_id' => $customerId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                throw new Exception("Booking not found or unauthorized.");
            }

            // Check if cancellation is allowed (30 minutes before start time)
            $currentTime = new DateTime();
            $startTime = new DateTime($booking['start_time']);
            $timeDifference = $startTime->getTimestamp() - $currentTime->getTimestamp();

            if ($currentTime >= $startTime) {
                throw new Exception("Cancellation is not allowed after the booking start time.");
            }

            if ($timeDifference < 1800) { // 1800 seconds = 30 minutes
                throw new Exception("Cancellation is only allowed at least 30 minutes before the start time.");
            }

            // Update booking status to 'cancelled'
            $updateStmt = $pdo->prepare("
                UPDATE bookings 
                SET status = 'cancelled' 
                WHERE id = :booking_id AND customer_id = :customer_id
            ");
            $updateStmt->execute([
                'booking_id' => $bookingId,
                'customer_id' => $customerId,
            ]);

            // Generate refund receipt
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
    <h1>Refund Receipt</h1>
    <p>Dear {$booking['customer_name']},</p>
    <p>Your booking has been successfully canceled. Below are your refund details:</p>
    <table class='details'>
        <tr><td>Booking ID:</td><td>{$bookingId}</td></tr>
        <tr><td>Futsal:</td><td>{$booking['futsal_name']}</td></tr>
        <tr><td>Start Time:</td><td>{$booking['start_time']}</td></tr>
        <tr><td>End Time:</td><td>{$booking['end_time']}</td></tr>
        <tr><td>Total Cost:</td><td>Rs. {$booking['total_cost']}</td></tr>
        <tr><td>Status:</td><td>Refund Requested</td></tr>
    </table>
    <p>Please note that a cancellation fee of Rs. 100/- will be deducted from your refund amount.</p>
    <p class='footer'>Need help? Contact us at <a href='mailto:mohamedmaizanmunas@gmail.com' style='color: #bbd12b;'>support@playsphere.com</a></p>
</div>";


            $mpdf = new Mpdf();
            $mpdf->WriteHTML($html);
            $pdfFilePath = "receipts/Refund_Receipt_{$bookingId}.pdf";
            $mpdf->Output($pdfFilePath, \Mpdf\Output\Destination::FILE);

            // Send email notifications
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'mohammedmaizan@gmail.com';
            $mail->Password = 'ocbocejxyxquwxic';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Email to futsal owner
            $mail->setFrom('mohammedmaizan@gmail.com', 'PlaySphere');
            $mail->addAddress($booking['owner_email']);
            $mail->isHTML(true);
            $mail->Subject = "Booking Cancellation - Refund Required";
            $mail->Body = "Your booking cancellation email content here.";
            $mail->send();

            // Email to customer
            $mail->clearAddresses();
            $mail->addAddress($booking['customer_email']);
            $mail->Subject = "Refund Receipt - PlaySphere";
            $mail->Body = "Dear {$booking['customer_name']},<br><br>Your booking has been successfully canceled. The refund receipt is attached.<br><br>Thank you for choosing PlaySphere.";
            $mail->addAttachment($pdfFilePath);
            $mail->send();

            header("Location: home.php#booking-section");
            exit();
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    } else {
        echo "You are not authorized to cancel this booking.";
    }
} else {
    header("Location: home.php");
    exit();
}
