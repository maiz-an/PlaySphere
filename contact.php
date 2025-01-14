<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);

    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'Rizkhanrk01@gmail.com';
        $mail->Password = 'kzbgnttfcpxrnvci';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('Rizkhanrk01@gmail.com', 'PlaySphere Contact Form');
        $mail->addAddress('Rizkhanrk01@gmail.com');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New Contact Form Submission';
        $mail->Body    = "
            <h3>New Contact Form Submission</h3>
            <p><strong>Name:</strong> {$name}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Message:</strong><br>{$message}</p>
        ";
        $mail->AltBody = "New Contact Form Submission\n\nName: {$name}\nEmail: {$email}\nMessage: {$message}";

        // Send the email
        $mail->send();
        echo "<script>alert('Message sent successfully!'); window.location.href = 'index.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('Message could not be sent. Mailer Error: {$mail->ErrorInfo}'); window.location.href = 'index.php';</script>";
    }
}
?>
