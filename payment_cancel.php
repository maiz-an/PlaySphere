<?php
session_start();

if (isset($_GET['reason'])) {
    $reason = htmlspecialchars($_GET['reason']);
} else {
    $reason = "No specific reason provided.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Canceled</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #161313;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #1e1e1e;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
            text-align: center;
        }
        h1 {
            color: red;
            margin-bottom: 20px;
        }
        p {
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Payment Canceled</h1>
        <p>Your payment was canceled.</p>
        <p><strong>Reason:</strong> <?= $reason ?></p>
        <a href="booking_page.php" style="color: #bbd12b;">Go Back to Booking</a>
    </div>
</body>
</html>
