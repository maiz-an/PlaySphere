<?php
session_start();
require '../vendor/autoload.php';
require 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Mpdf\Mpdf;

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../index.php#login-section");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $bookingId = $_POST['booking_id'];

    try {
        $ownerId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("
            SELECT b.*, u.email AS customer_email, u.name AS customer_name, f.name AS futsal_name
            FROM bookings b
            JOIN futsals f ON b.futsal_id = f.id
            JOIN users u ON b.customer_id = u.id
            WHERE b.id = :booking_id AND f.owner_id = :owner_id
        ");
        $stmt->execute(['booking_id' => $bookingId, 'owner_id' => $ownerId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            throw new Exception("Booking not found or you are not authorized to process this refund.");
        }

        if ($booking['status'] === 'refunded') {
            throw new Exception("This booking has already been refunded.");
        }

        if ($booking['status'] !== 'cancelled') {
            throw new Exception("Only cancelled bookings can be refunded.");
        }

        // Update the booking status to 'refunded'
        $updateStmt = $pdo->prepare("
            UPDATE bookings 
            SET status = 'refunded' 
            WHERE id = :booking_id
        ");
        $updateStmt->execute(['booking_id' => $bookingId]);

        // Ensure the `receipts` directory exists
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
    <h1>Refund Receipt</h1>
    <p>Dear {$booking['customer_name']},</p>
    <p>Your booking has been successfully refunded. Below are the details:</p>
    <table class='details'>
        <tr><td>Booking ID:</td><td>{$booking['id']}</td></tr>
        <tr><td>Futsal:</td><td>{$booking['futsal_name']}</td></tr>
        <tr><td>Start Time:</td><td>{$booking['start_time']}</td></tr>
        <tr><td>End Time:</td><td>{$booking['end_time']}</td></tr>
        <tr><td>Total Cost:</td><td>Rs. {$booking['total_cost']}</td></tr>
        <tr><td>Status:</td><td>Refunded</td></tr>
    </table>
    <p class='footer'>Need help? Contact us at <a href='mailto:support@playsphere.com' style='color: #bbd12b;'>support@playsphere.com</a></p>
</div>";

$mpdf = new Mpdf(['tempDir' => 'C:/wamp64/www/PlaySphere/tmp']);

        $mpdf->WriteHTML($html);
        $pdfFilePath = "{$receiptsDir}/Refund_Receipt_{$bookingId}.pdf";
        $mpdf->Output($pdfFilePath, \Mpdf\Output\Destination::FILE);

        // Send Email Notification to Customer
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
            $mail->addAddress($booking['customer_email']);
            $mail->addAttachment($pdfFilePath);

            $mail->isHTML(true);
            $mail->Subject = 'Refund Processed';
            $mail->Body = 'Your refund has been processed successfully. Please find the refund receipt attached.';
            $mail->send();

        } catch (Exception $e) {
            error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        }

        echo "<script>alert('Refund processed successfully.');</script>";
        echo "<script>window.location.href = 'futsalBookings.php';</script>";
        exit();
    } catch (Exception $e) {
        echo "<script>alert('Error: " . htmlspecialchars($e->getMessage()) . "');</script>";
        echo "<script>window.location.href = 'futsalBookings.php';</script>";
        exit();
    }
} else {
    header("Location: futsalBookings.php");
    exit();
}
