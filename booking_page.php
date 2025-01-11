<?php
session_start();

require 'staff/db.php';

$available_futsals = [];
$error_message = "";

// Validate session
if (!isset($_SESSION['username']) || !isset($_SESSION['customer_id'])) {
    header("Location: index.php#login-section");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$username = htmlspecialchars($_SESSION['username']);
$name = htmlspecialchars($_SESSION['name']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;

    if ($action === 'check_availability') {
        $start_time = $_POST['start_time'] ?? null;
        $end_time = $_POST['end_time'] ?? null;
        $start = new DateTime($start_time);
        $end = new DateTime($end_time);
        $duration = $start->diff($end);
        if ($start_time && $end_time) {
            try {
                // Fetch available futsals
                $stmt = $pdo->prepare("
                    SELECT * FROM futsals f
                    WHERE NOT EXISTS (
                        SELECT 1 FROM bookings b 
                        WHERE b.futsal_id = f.id 
                        AND b.status NOT IN ('cancelled', 'refunded')
                        AND (
                            (b.start_time <= :start_time1 AND b.end_time > :start_time2) OR
                            (b.start_time < :end_time1 AND b.end_time >= :end_time2)
                        )
                    )
                ");

                $stmt->execute([
                    'start_time1' => $start_time,
                    'start_time2' => $start_time,
                    'end_time1'   => $end_time,
                    'end_time2'   => $end_time,
                ]);
                $available_futsals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $error_message = "Database Error: " . $e->getMessage();
            }
        } else {
            $error_message = "Both start and end times are required.";
        }
    } elseif ($duration->h < 1 && $duration->i < 60) {
        $error_message = "The booking duration must be at least one hour.";
    } elseif ($action === 'book_futsal') {
        $futsal_id = $_POST['futsal_id'] ?? null;
        $start_time = $_POST['start_time'] ?? null;
        $end_time = $_POST['end_time'] ?? null;

        if ($futsal_id && $start_time && $end_time) {
            try {
                $stmt = $pdo->prepare("SELECT price_per_hour FROM futsals WHERE id = :futsal_id");
                $stmt->execute(['futsal_id' => $futsal_id]);
                $futsal = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($futsal) {
                    $price_per_hour = $futsal['price_per_hour'];
                    $start = new DateTime($start_time);
                    $end = new DateTime($end_time);
                    $duration = $start->diff($end)->h + ($start->diff($end)->i / 60);
                    $total_cost = $duration * $price_per_hour;

                    // Pass data to payment.php using POST
                    echo "
                        <form id='payment-form' action='payment.php' method='POST'>
                            <input type='hidden' name='futsal_id' value='{$futsal_id}'>
                            <input type='hidden' name='start_time' value='{$start_time}'>
                            <input type='hidden' name='end_time' value='{$end_time}'>
                            <input type='hidden' name='total_cost' value='{$total_cost}'>
                        </form>
                        <script>document.getElementById('payment-form').submit();</script>
                    ";
                    exit();
                } else {
                    $error_message = "Futsal not found.";
                }
            } catch (PDOException $e) {
                $error_message = "Booking failed: " . $e->getMessage();
            }
        } else {
            $error_message = "Please select a futsal and provide valid times.";
        }
    }
}
?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/fav.png">
    <title>Book a Futsal | PlaySphere</title>
    <link rel="stylesheet" href="styles/booking.css">
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
</head>

<body>
    <div class="container">
        <div class="hw">
            <a href="home.php"><img src="images/arrow.png" alt="back" style="width: 2rem;"></a>
            <h1>Book a Futsal</h1>
        </div>
        <?php if (!empty($error_message)): ?>
            <p class="error"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <p class="success"><?= htmlspecialchars($success_message) ?></p>
        <?php endif; ?>

        <!-- Availability Form -->
        <form method="POST">
            <input type="hidden" name="action" value="check_availability">
            <div class="form-group">
                <label for="start_time">Start Time:</label>
                <input type="datetime-local" id="start_time" name="start_time"
                    value="<?= htmlspecialchars($_POST['start_time'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="end_time">End Time:</label>
                <input type="datetime-local" id="end_time" name="end_time"
                    value="<?= htmlspecialchars($_POST['end_time'] ?? '') ?>" required>
            </div>
            <button type="submit">Search Available Futsals</button>
        </form>


        <!-- Unavailable Futsals -->
        <?php if (!empty($unavailable_futsals)): ?>
            <div class="unavailable-futsals">
                <h2>Unavailable Futsals</h2>
                <ul>
                    <?php foreach ($unavailable_futsals as $futsal): ?>
                        <li>
                            <?= htmlspecialchars($futsal['name']) ?>
                            (<?= htmlspecialchars($futsal['location']) ?>):
                            <?= htmlspecialchars($futsal['start_time']) ?> to <?= htmlspecialchars($futsal['end_time']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Futsal Selection Form -->
        <!-- Futsal Selection Form -->
        <?php if (!empty($available_futsals)): ?>
            <form action="payment.php" method="POST">
                <input type="hidden" name="action" value="book_futsal">
                <input type="hidden" name="start_time" value="<?= htmlspecialchars($_POST['start_time'] ?? '') ?>">
                <input type="hidden" name="end_time" value="<?= htmlspecialchars($_POST['end_time'] ?? '') ?>">

                <div class="form-group">
                    <br><label for="futsal">Select Futsal:</label>
                    <select id="futsal" name="futsal_id" required>
                        <?php foreach ($available_futsals as $futsal): ?>
                            <option value="<?= $futsal['id'] ?>" data-price="<?= $futsal['price_per_hour'] ?>">
                                <?= htmlspecialchars($futsal['name']) ?>
                                (<?= htmlspecialchars($futsal['location']) ?>) - Rs. <?= number_format($futsal['price_per_hour'], 2) ?>/hr
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="total-cost">
                    Total Cost: Rs. <span id="total-cost">0.00</span>
                </div>
                <!-- Hidden input for total cost -->
                <input type="hidden" id="total-cost-input" name="total_cost" value="0.00">
                <button type="submit">Book Now</button>
            </form>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'check_availability'): ?>
            <p class="error">No futsal found for the selected time.</p>
        <?php endif; ?>


    </div>


    <script>
        const futsalSelect = document.getElementById('futsal');
        const totalCostElement = document.getElementById('total-cost');
        const totalCostInput = document.getElementById('total-cost-input');
        const startTime = new Date("<?= $_POST['start_time'] ?? '' ?>");
        const endTime = new Date("<?= $_POST['end_time'] ?? '' ?>");
        const durationInHours = (endTime - startTime) / (1000 * 60 * 60);

        function updateTotalCost() {
            if (!futsalSelect || durationInHours <= 0) return;
            const selectedOption = futsalSelect.options[futsalSelect.selectedIndex];
            const pricePerHour = parseFloat(selectedOption.dataset.price || 0);
            const totalCost = (durationInHours * pricePerHour).toFixed(2);
            totalCostElement.textContent = totalCost;
            totalCostInput.value = totalCost;
        }

        if (futsalSelect) {
            futsalSelect.addEventListener('change', updateTotalCost);
            updateTotalCost();
        }
        document.querySelector('form').addEventListener('submit', function(e) {
            const startTime = new Date(document.getElementById('start_time').value);
            const endTime = new Date(document.getElementById('end_time').value);
            const durationInHours = (endTime - startTime) / (1000 * 60 * 60);

            if (durationInHours < 1) {
                e.preventDefault();
                alert('The booking duration must be at least one hour.');
            }
        });
    </script>

</body>

</html>