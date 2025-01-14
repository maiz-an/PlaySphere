<?php
session_start();
require 'db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure the user is logged in as a staff member
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../index.php#login-section");
    exit();
}

$owner_id = $_SESSION['user_id'];

// Fetch futsals owned by the owner
$stmt = $pdo->prepare("SELECT id, name, price_per_hour FROM futsals WHERE owner_id = :owner_id");
$stmt->execute(['owner_id' => $owner_id]);
$futsals = $stmt->fetchAll(PDO::FETCH_ASSOC);


$error_message = '';
$success_message = '';
$unavailable_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;

    if ($action === 'book_futsal') {
        $futsal_id = $_POST['futsal_id'] ?? null;
        $customer_name = $_POST['customer_name'] ?? null;
        $customer_phone = $_POST['customer_phone'] ?? null;
        $customer_email = $_POST['customer_email'] ?? null;
        $start_time = $_POST['start_time'] ?? null;
        $end_time = $_POST['end_time'] ?? null;

        if ($futsal_id && $customer_name && $customer_phone && $start_time && $end_time) {
            try {
                // Fetch the futsal's price per hour (updated column name)
                $stmt = $pdo->prepare("SELECT price_per_hour FROM futsals WHERE id = :futsal_id");
                $stmt->execute(['futsal_id' => $futsal_id]);
                $futsal = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($futsal) {
                    $price_per_hour = $futsal['price_per_hour'];

                    // Calculate the duration of the booking in hours
                    $start_time_obj = new DateTime($start_time);
                    $end_time_obj = new DateTime($end_time);
                    $interval = $start_time_obj->diff($end_time_obj);
                    $duration_in_hours = $interval->h + ($interval->i / 60);

                    // Calculate the total cost
                    $total_cost = $price_per_hour * $duration_in_hours;
                } else {
                    $error_message = "Futsal not found!";
                    exit();
                }

                // Fetch the futsal details
                $stmt = $pdo->prepare("SELECT name FROM futsals WHERE id = :futsal_id");
                $stmt->execute(['futsal_id' => $futsal_id]);
                $futsal_details = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($futsal_details) {
                    $futsal_name = $futsal_details['name'];
                } else {
                    $error_message = "Futsal not found!";
                    exit();
                }



                // Check for overlapping bookings
                $stmt = $pdo->prepare("
                    SELECT start_time, end_time 
                    FROM bookings 
                    WHERE futsal_id = :futsal_id 
                      AND status NOT IN ('cancelled', 'refunded')
                      AND (
                          (start_time <= :start_time1 AND end_time > :start_time2) OR
                          (start_time < :end_time1 AND end_time >= :end_time2) OR
                          (start_time >= :start_time3 AND end_time <= :end_time4)
                      )
                ");
                $stmt->execute([
                    'futsal_id' => $futsal_id,
                    'start_time1' => $start_time,
                    'start_time2' => $start_time,
                    'end_time1' => $end_time,
                    'end_time2' => $end_time,
                    'start_time3' => $start_time,
                    'end_time4' => $end_time,
                ]);
                $overlapping_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($overlapping_bookings)) {
                    $unavailable_times = [];
                    foreach ($overlapping_bookings as $booking) {
                        $unavailable_times[] = "From " . date('Y-m-d H:i', strtotime($booking['start_time'])) .
                            " to " . date('Y-m-d H:i', strtotime($booking['end_time']));
                    }
                    $unavailable_message = "Selected futsal is unavailable during the following times:<br>" . implode('<br>', $unavailable_times);
                } else {
                    // Insert the booking into the database with customer_id as 0 for unregistered users
                    $stmt = $pdo->prepare("
                        INSERT INTO bookings (futsal_id, customer_id, start_time, end_time, status, payment_status, total_cost, cancel)
                        VALUES (:futsal_id, 0, :start_time, :end_time, 'confirmed', 'paid', :total_cost, 0)
                    ");
                    $stmt->execute([
                        'futsal_id' => $futsal_id,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'total_cost' => $total_cost,  // Ensure total_cost is calculated correctly
                    ]);


                    // Get the futsal owner's email
                    $ownerStmt = $pdo->prepare("SELECT email, name FROM users WHERE id = :owner_id");
                    $ownerStmt->execute(['owner_id' => $owner_id]);
                    $owner = $ownerStmt->fetch(PDO::FETCH_ASSOC);

                    // Send email to the owner
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'Rizkhanrk01@gmail.com';
                    $mail->Password = 'kzbgnttfcpxrnvci';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('Rizkhanrk01@gmail.com', 'PlaySphere');
                    $mail->addAddress($owner['email']);
                    $mail->isHTML(true);
                    $mail->Subject = "New Booking for Your Futsal";
                    $mail->Body = "
                        Dear {$owner['name']},<br><br>
                        A new booking has been made for your futsal.<br>
                        <strong>Details:</strong><br>
                        Customer Name: {$customer_name}<br>
                        Phone: {$customer_phone}<br>
                        Start Time: {$start_time}<br>
                        End Time: {$end_time}<br>
                        Total Cost: Rs. " . number_format($total_cost, 2) . "<br><br>
                        Regards,<br>
                        PlaySphere
                    ";
                    $mail->send();

                    // Send email to the customer
                    $mail->clearAddresses(); // Clear the previous address
                    $mail->addAddress($customer_email); // Add customer email
                    $mail->Subject = "Your Futsal Booking Confirmation";
                    $mail->Body = "
                        Dear {$customer_name},<br><br>
                        Your futsal booking has been confirmed.<br>
                        <strong>Booking Details:</strong><br>
                        Futsal Name: {$futsal_name}<br>
                        Start Time: {$start_time}<br>
                        End Time: {$end_time}<br>
                        Total Cost: Rs. " . number_format($total_cost, 2) . "<br><br>
                        Regards,<br>
                        PlaySphere
                    ";
                    $mail->send();

                    $success_message = "Booking successfully created for {$customer_name}!";
                }
            } catch (PDOException $e) {
                $error_message = "Booking failed: " . $e->getMessage();
            } catch (Exception $e) {
                $error_message = "An error occurred: " . $e->getMessage();
            }
        } else {
            $error_message = "All fields are required for booking.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/fav.png">
    <title>Owner Booking | PlaySphere</title>
</head>
<style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #161313;
        color: #fff;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }

    .container {
        background-color: #1e1e1e;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
        max-width: 500px;
        width: 90%;
        text-align: center;
    }

    h1 {
        color: #bbd12b;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 15px;
        text-align: left;
    }

    label {
        display: block;
        font-weight: 600;
        margin-bottom: 5px;
    }

    input,
    select,
    button {
        width: 96%;
        padding: 10px;
        margin-top: 5px;
        border-radius: 5px;
        border: 1px solid #444;
        background-color: #252525;
        color: #fff;
        font-size: 1rem;
    }

    button {
        background-color: #bbd12b;
        border: none;
        cursor: pointer;
        font-weight: 600;
        transition: background-color 0.3s;
    }

    button:hover {
        background-color: #a3c61f;
    }

    .error {
        color: red;
        margin-top: 10px;
    }

    .success {
        color: green;
        margin-top: 10px;
    }

    .total-cost {
        margin-top: 15px;
        font-size: 1.2rem;
        color: #ffcc00;
    }

    .unavailable-futsals {
        margin-top: 15px;
        font-size: 0.8rem;
        color: #ffcc00;
    }

    .futsal-list {
        margin-top: 20px;
    }

    .hw {
        position: relative;
        display: flex;
        gap: 6rem;
    }


    @media (max-width: 500px) {
        body {
            justify-content: flex-start;
            padding: 10px;
            overflow-x: hidden !important;
        }

        .container {
            padding: 15px;
            max-width: 90%;
            width: 100%;
            overflow-x: hidden !important;
            box-shadow: none;
        }

        h1 {
            margin-top: 2rem;
            font-size: 2.4rem;
            margin-bottom: 15px;
        }

        .form-group label {
            font-size: 0.9rem;
        }

        input,
        select,
        button {
            font-size: 1.05rem;
            padding: 8px;
        }

        button {
            font-size: 0.9rem;
        }

        .error,
        .success {
            font-size: 0.9rem;
        }

        .total-cost {
            font-size: 1rem;
        }

        .hw {
            gap: 1rem;
        }

        .hw a img {
            width: 1.2rem !important;
        }

        input {
            width: 120% !important;
            padding: 10px;
            font-size: 1rem !important;
            box-sizing: border-box;
        }
    }
</style>

<body>
    <div class="container">
        <div class="hw">
            <a href="futsalBookings.php"><img src="../images/arrow.png" alt="back" style="width: 2rem;"></a>
            <h1>Add Booking</h1>
        </div>
        <?php if ($error_message): ?>
            <p class="error"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <p class="success"><?= htmlspecialchars($success_message) ?></p>
        <?php endif; ?>
        <?php if ($unavailable_message): ?>
            <p class="unavailable"><?= $unavailable_message ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="book_futsal">
            <div class="form-group">
                <label for="futsal">Select Futsal:</label>
                <select name="futsal_id" required>
                    <?php foreach ($futsals as $futsal): ?>
                        <option value="<?= $futsal['id'] ?>"><?= htmlspecialchars($futsal['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="customer_name">Customer Name:</label>
                <input type="text" name="customer_name" required>
            </div>
            <div class="form-group">
                <label for="customer_phone">Customer Phone:</label>
                <input type="text" name="customer_phone" required>
            </div>
            <div class="form-group">
                <label for="customer_email">Customer Email:</label>
                <input type="email" name="customer_email" required>
            </div>
            <div class="form-group">
                <label for="start_time">Start Time:</label>
                <input type="datetime-local" name="start_time" required>
            </div>
            <div class="form-group">
                <label for="end_time">End Time:</label>
                <input type="datetime-local" name="end_time" required>
            </div>
            <div class="total-cost" id="total-cost">
                Total Cost: Rs. 0.00
            </div>

            <button type="submit">Confirm Booking</button>
        </form>

    </div>
    <script>
function calculateTotalCost() {
    const futsalSelect = document.querySelector('select[name="futsal_id"]');
    const selectedFutsalId = futsalSelect.value;
    
    // Get the price per hour for the selected futsal
    const pricePerHour = getPriceForFutsal(selectedFutsalId);

    const startTime = document.querySelector('input[name="start_time"]').value;
    const endTime = document.querySelector('input[name="end_time"]').value;

    if (startTime && endTime) {
        const start = new Date(startTime);
        const end = new Date(endTime);

        // Check if the start time is before the end time
        if (start >= end) {
            alert("End time must be later than the start time.");
            return;
        }

        // Calculate duration in hours
        const durationInHours = (end - start) / (1000 * 60 * 60); // Duration in hours

        if (durationInHours <= 0) {
            alert("Invalid time range. Please ensure the start time is before the end time.");
            return;
        }

        // Calculate the total cost
        const totalCost = pricePerHour * durationInHours;

        // Update the total cost display
        const totalCostElement = document.getElementById('total-cost');
        totalCostElement.innerHTML = `Total Cost: Rs. ${totalCost.toFixed(2)}`;
    } else {
        document.getElementById('total-cost').innerHTML = `Total Cost: Rs. 0.00`;
    }
}

// Get the price per hour for the selected futsal
function getPriceForFutsal(futsalId) {
    const futsals = <?= json_encode($futsals); ?>; // Passing PHP data to JavaScript
    const selectedFutsal = futsals.find(futsal => futsal.id == futsalId);
    return selectedFutsal ? selectedFutsal.price_per_hour : 0;
}

// Attach event listeners to recalculate the cost when start or end times change
document.querySelector('input[name="start_time"]').addEventListener('input', calculateTotalCost);
document.querySelector('input[name="end_time"]').addEventListener('input', calculateTotalCost);
document.querySelector('select[name="futsal_id"]').addEventListener('change', calculateTotalCost);

</script>
</body>


</html>