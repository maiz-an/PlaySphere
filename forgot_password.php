<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "PlaySphere";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle OTP generation and sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    $usernameOrEmail = $_POST['usernameOrEmail'];
    
    // Check if the email or username exists in the database
    $sql = "SELECT email FROM users WHERE username='$usernameOrEmail' OR email='$usernameOrEmail'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        // Generate OTP
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['usernameOrEmail'] = $usernameOrEmail;
        $_SESSION['otp_time'] = time();
        
        // Fetch email from database
        $row = mysqli_fetch_assoc($result);
        $email = $row['email'];

        // Send OTP via email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'Rizkhanrk01@gmail.com';
            $mail->Password = 'kzbgnttfcpxrnvci';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('Rizkhanrk01@gmail.com', 'PlaySphere');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = "Your OTP Code";
            $mail->Body = "<p>Your OTP code for resetting your password is: <strong>{$otp}</strong></p>";

            $mail->send();
            echo "<script>alert('OTP has been sent to your email.'); window.location.href = 'forgot_password.php?step=verify';</script>";
        } catch (Exception $e) {
            echo "<script>alert('Failed to send OTP. Please try again.'); window.location.href = 'forgot_password.php';</script>";
        }
    } else {
        echo "<script>alert('No account found with that username or email.'); window.location.href = 'forgot_password.php';</script>";
    }
    exit();
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $entered_otp = $_POST['otp'];

    // Check if OTP is valid
    if (isset($_SESSION['otp']) && $_SESSION['otp'] == $entered_otp) {
        echo "<script>alert('OTP verified successfully. Please reset your password.'); window.location.href = 'forgot_password.php?step=reset';</script>";
    } else {
        echo "<script>alert('Invalid OTP. Please try again.'); window.location.href = 'forgot_password.php?step=verify';</script>";
    }
    exit();
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    if ($newPassword !== $confirmPassword) {
        echo "<script>alert('Passwords do not match.'); window.location.href = 'forgot_password.php?step=reset';</script>";
        exit();
    }

    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update the password in the database
    $usernameOrEmail = $_SESSION['usernameOrEmail'];
    $sql = "UPDATE users SET password_hash='$hashedPassword' WHERE username='$usernameOrEmail' OR email='$usernameOrEmail'";
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Password reset successful!'); window.location.href = 'index.php#login-section';</script>";
    } else {
        echo "<script>alert('Error updating password.'); window.location.href = 'forgot_password.php?step=reset';</script>";
    }
    exit();
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - PlaySphere</title>
    <link rel="icon" type="image/png" href="images/fav.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="Styles/index.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #161313;
            color: #fff;
            margin: 0;
            padding: 0;
        }

        .form-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .forgot-password-form {
            background: rgba(43, 40, 40, 0.7);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }

        .forgot-password-form h2 {
            color: #bbd12b;
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .forgot-password-form form {
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
        }

        .forgot-password-form label {
            text-align: left;
            font-size: 1rem;
            font-weight: 500;
            color: #ddd;
        }

        .forgot-password-form input {
            background: #252525;
            border: 1px solid #444;
            color: #fff;
            font-size: 1rem;
            padding: 0.75rem;
            border-radius: 5px;
            outline: none;
        }

        .forgot-password-form input:focus {
            border-color: #bbd12b;
            box-shadow: 0 0 5px rgba(187, 209, 43, 0.5);
        }

        .forgot-password-form .btn-sub {
            background-color: #bbd12b;
            color: #161313;
            font-weight: 600;
            font-size: 1rem;
            padding: 0.75rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .forgot-password-form .btn-sub:hover {
            background-color: #a3c61f;
        }

        .forgot-password-form p {
            font-size: 0.9rem;
            color: #ddd;
            margin-top: 1rem;
        }

        .forgot-password-form a {
            color: #bbd12b;
            font-weight: 500;
            text-decoration: underline;
        }

        .forgot-password-form a:hover {
            color: #a3c61f;
        }
    </style>

</head>
<body>
    <div class="form-container">
        <div class="forgot-password-form">
            <?php if (!isset($_GET['step']) || $_GET['step'] === 'request'): ?>
                <!-- Step 1: Enter Username or Email -->
                <h2>Forgot Password</h2>
                <form action="forgot_password.php" method="post">
                    <label for="usernameOrEmail">Username or Email:</label>
                    <input type="text" id="usernameOrEmail" name="usernameOrEmail" placeholder="Enter your username or email" required><br>
                    <button type="submit" name="send_otp" class="btn-sub">Send OTP</button>
                </form>

            <?php elseif ($_GET['step'] === 'verify'): ?>
                <!-- Step 2: OTP Verification -->
                <h2>Verify OTP</h2>
                <form action="forgot_password.php" method="post">
                    <label for="otp">Enter OTP:</label>
                    <input type="text" id="otp" name="otp" placeholder="Enter OTP sent to your email" required><br>
                    <button type="submit" name="verify_otp" class="btn-sub">Verify OTP</button>
                </form>

            <?php elseif ($_GET['step'] === 'reset'): ?>
                <!-- Step 3: Reset Password -->
                <h2>Reset Password</h2>
                <form action="forgot_password.php" method="post">
                    <label for="newPassword">New Password:</label>
                    <input type="password" id="newPassword" name="newPassword" placeholder="Enter your new password" required><br>
                    <label for="confirmPassword">Confirm New Password:</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your new password" required><br>
                    <button type="submit" name="reset_password" class="btn-sub">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
