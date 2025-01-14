<?php
session_start();
require 'db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../index.php#login-section");
    exit();
}

if (isset($_SESSION['user_id'])) {
    $ownerId = $_SESSION['user_id'];
} else {
    echo "Session error: user_id not set. Redirecting to login...";
    header("Location: ../index.php#login-section");
    exit();
}

$stmt = $pdo->prepare("
    SELECT id, name 
    FROM futsals 
    WHERE owner_id = :owner_id
");
$stmt->execute(['owner_id' => $ownerId]);
$futsals = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$futsals) {
    echo "<p>You don't own any futsals yet. Please add one first.</p>";
    exit();
}

$futsalIds = array_column($futsals, 'id');
$bookings = [];
if (!empty($futsalIds)) {
    $placeholders = implode(',', array_fill(0, count($futsalIds), '?'));
    $stmt = $pdo->prepare("
    SELECT b.id AS booking_id, f.name AS futsal_name, b.start_time, b.end_time, b.total_cost, b.status, u.name AS customer_name
    FROM bookings b
    JOIN futsals f ON b.futsal_id = f.id
    JOIN users u ON b.customer_id = u.id
    WHERE b.futsal_id IN ($placeholders)
    ORDER BY b.start_time DESC
");

    $stmt->execute($futsalIds);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Bookings | PlaySphere</title>
    <link rel="icon" type="image/png" href="../images/fav.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Roboto&family=Open+Sans&family=Lato&family=Montserrat&family=Poppins&family=Oswald&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #161313;
            color: #fff;
        }

        nav.hero-nav {
            background-color: #000;
            padding: 10px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 3rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);
        }

        nav.hero-nav a {
            color: #bbd12b;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease-in-out;
        }

        nav.hero-nav a:hover {
            color: #a3c61f;
        }

        .container {
            max-width: 1200px;
            min-height: 29rem;
            margin: 20px auto;
            padding: 20px;
            background-color: #1e1e1e;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
        }

        h1 {
            font-size: 2.5rem;
            color: #bbd12b;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .search-bar {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
        }

        .search-bar input {
            width: 100%;
            padding: 10px;
            border: 1px solid #444;
            border-radius: 5px;
            background-color: #252525;
            color: #fff;
            font-size: 1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border: 1px solid #444;
        }

        th {
            background-color: #252525;
            color: #bbd12b;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: rgba(255, 255, 255, 0.05);
        }

        tr:hover {
            background-color: rgba(187, 209, 43, 0.1);
        }

        footer.footer {
            text-align: center;
            padding: 20px;
            background-color: #000;
            color: #bbd12b;
            margin-top: 20px;
            font-size: 0.9rem;
        }

        nav.hero-nav img.logo {
            width: 50px;
            margin-right: 20px;
        }

        footer a {
            text-decoration: none;
            color: #bbd12b;
        }

        .btn-cancel {
            background-color: #bbd12b;
            color: #161313;
            font-size: 13px;
            font-weight: bold;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-transform: uppercase;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-cancel:hover {
            background-color: #a3c61f;
            transform: scale(1.05);
        }

        .btn-cancel:active {
            background-color: #8bb015;
            transform: scale(1);
        }

        .btn-cancel:focus {
            outline: 2px solid #bbd12b;
            outline-offset: 2px;
        }

        /* Floating Button */
        .floating-btn {
            position: fixed;
            bottom: 60px;
            right: 30px;
            background-color: #bbd12b;
            color: #161313;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            animation: bounce 2s infinite;
        }

        /* Bounce Animation */
        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-10px);
            }

            60% {
                transform: translateY(-5px);
            }
        }

        /* Hover Effect */
        .floating-btn:hover {
            background-color: #a3c61f;
            transform: scale(1.1);
            animation: none;
            z-index: 12;
        }

        .floating-btn:active {
            transform: scale(1);
            box-shadow: 0px 4px 10px rgba(255, 255, 255, 0.3);
        }

        /* Contact Us Overlay */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 999;
        }

        .overlay-content {
            background-color: #161313;
            color: #fff;
            padding: 2rem;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            position: relative;
        }

        .overlay-content h2 {
            font-size: 2rem;
            color: #bbd12b;
            margin-bottom: 1rem;
        }

        .overlay-content p {
            font-size: 1.2rem;
            color: #ddd;
            margin-bottom: 2rem;
        }

        .contact-form label {
            text-align: left;
            display: block;
            font-size: 1rem;
            color: #ddd;
            margin-bottom: 0.5rem;
        }

        .contact-form input,
        .contact-form textarea {
            width: 97%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #444;
            border-radius: 5px;
            background-color: #252525;
            color: #fff;
        }

        .contact-form button {
            background-color: #bbd12b;
            color: #161313;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }


        /* Close Button */
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 1.5rem;
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-btn:hover {
            color: #bbd12b;
        }

        .btn {
            background-color: #bbd12b;
            color: #161313;
            font-size: 15px;
            font-weight: bolder;
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-bottom: 10px;
        }

        .btn:hover {
            background-color: #a3c61f;
            transform: scale(1.05);
        }

        .btn:active {
            background-color: #8bb015;
            transform: scale(1);
        }

        .btn:focus {
            outline: 2px solid #bbd12b;
            outline-offset: 2px;
        }

        @media (max-width: 500px) {

            /* Navbar adjustments */
            nav.hero-nav {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
            }

            nav.hero-nav a {
                font-size: 0.9rem;
                padding: 0.5rem 0;
            }

            nav.hero-nav img.logo {
                width: 40px;
                margin-right: 10px;
            }

            /* Main container adjustments */
            .container {
                width: 90%;
                margin: 1rem auto;
                padding: 1rem;
            }

            .container h1 {
                font-size: 1.8rem;
                margin-bottom: 1rem;
            }

            .search-bar input {
                font-size: 0.9rem;
                padding: 0.7rem;
            }


            .table-wrapper {
                max-width: 100%;
                overflow-x: auto;
                margin-top: 1rem;
                border: 1px solid #444;
            }

            .table-wrapper table {
                width: 100%;
                border-collapse: collapse;
            }

            .table-wrapper th,
            .table-wrapper td {
                padding: 10px;
                text-align: left;
                font-size: 0.8rem;
                border: 1px solid #444;
            }

            .table-wrapper th {
                background-color: #252525;
                color: #bbd12b;
                font-weight: bold;
                position: sticky;
                top: 0;
                z-index: 2;
            }

            .table-wrapper tr:nth-child(even) {
                background-color: rgba(255, 255, 255, 0.05);
            }

            .table-wrapper tr:hover {
                background-color: rgba(187, 209, 43, 0.1);
            }

            .table-wrapper::-webkit-scrollbar {
                height: 8px;
            }

            .table-wrapper::-webkit-scrollbar-thumb {
                background: #bbd12b;
                border-radius: 5px;
            }

            .table-wrapper::-webkit-scrollbar-track {
                background: #252525;
            }

            footer.footer {
                font-size: 0.8rem;
                padding: 1rem;
            }

            footer a {
                font-size: 0.8rem;
            }

            .floating-btn {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }

            .overlay-content {
                padding: 1rem;
            }

            .overlay-content h2 {
                font-size: 1.5rem;
            }

            .overlay-content p {
                font-size: 0.9rem;
            }

            .contact-form label {
                font-size: 0.8rem;
            }

            .contact-form input,
            .contact-form textarea {
                font-size: 0.8rem;
                padding: 0.5rem;
            }

            .contact-form button {
                font-size: 0.9rem;
                padding: 0.5rem 1rem;
            }

            .close-btn {
                font-size: 1.2rem;
            }
        }
    </style>
    <script>
        function filterTable() {
            const searchInput = document.getElementById("searchInput").value.toLowerCase();
            const tableRows = document.querySelectorAll("#bookingsTable tbody tr");

            tableRows.forEach(row => {
                const cells = Array.from(row.children);
                const rowText = cells.map(cell => cell.textContent.toLowerCase()).join(" ");
                row.style.display = rowText.includes(searchInput) ? "" : "none";
            });
        }
    </script>
</head>

<body>
    <nav class="hero-nav">
        <img class="logo" src="../images/logoA.png" alt="Logo">
        <a href="staff.php">Home</a>
        <a href="futsal.php">Futsals</a>
        <a href="futsalBookings.php">Bookings</a>
        <a href="../logout.php">Logout</a>
    </nav>

    <div class="container">
        <h1>Your Futsal Bookings</h1>
        <a href="owner_book_futsal.php" class="btn">Book</a> <br>
        <div class="search-bar">
            <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search by Booking ID, Futsal, Customer, Start Time, or End Time">
        </div>
        <?php if (!empty($bookings)): ?>
            <div class="table-wrapper">
                <table id="bookingsTable">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Futsal</th>
                            <th>Customer</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Total Cost</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?= htmlspecialchars($booking['booking_id']) ?></td>
                                <td><?= htmlspecialchars($booking['futsal_name']) ?></td>
                                <td><?= htmlspecialchars($booking['customer_name']) ?></td>
                                <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($booking['start_time']))) ?></td>
                                <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($booking['end_time']))) ?></td>
                                <td>Rs. <?= htmlspecialchars(number_format($booking['total_cost'], 2)) ?></td>
                                <td><?= htmlspecialchars(ucfirst($booking['status'])) ?></td>
                                <td>
                                    <?php if ($booking['status'] === 'cancelled'): ?>
                                        <form action="refunded.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['booking_id']) ?>">
                                            <button type="submit" class="btn-cancel" onclick="return confirm('Are you sure you want to refund this booking?')">Refund</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($booking['status'] === 'confirmed'): ?>
                                        <img src="../images/check.png" alt="Confirmed" style="width: 42px; height: auto;">
                                    <?php endif; ?>
                                    <?php if ($booking['status'] === 'playing'): ?>
                                        <img src="../images/soccer.png" alt="playing" style="width: 42px; height: auto;">
                                    <?php endif; ?>
                                    <?php if ($booking['status'] === 'refunded'): ?>
                                        <img src="../images/letter-x.png" alt="refunded" style="width: 42px; height: auto;">
                                    <?php endif; ?>
                                    <?php if ($booking['status'] === 'completed'): ?>
                                        <img src="../images/football-award.png" alt="completed" style="width: 52px; height: auto;">
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No bookings have been made for your futsals yet.</p>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; 2025 PlaySphere | Futsal Servior Dashboard Designed by team PlaySphere</p>
    </footer>
    <!-- Floating Button -->
    <button id="contactUsBtn" class="floating-btn">
        <i class="fas fa-comments"></i>
    </button>

    <!-- Contact Us Overlay -->
    <div id="contactOverlay" class="overlay">
        <div class="overlay-content">
            <h2>Contact Us</h2>
            <p style="font-size: 16px;">We'd love to hear from you! Reach out to us for any inquiries or support. <br>
                &copy; 2025 PlaySphere | Designed by team PlaySphere</p>

            <form action="contact.php" method="post" class="contact-form">
                <label for="contact-name">Name</label>
                <input type="text" id="contact-name" name="name" placeholder="Your Name" required />

                <label for="contact-email">Email</label>
                <input type="email" id="contact-email" name="email" placeholder="Your Email" required />

                <label for="contact-message">Message</label>
                <textarea id="contact-message" name="message" placeholder="Your Message" rows="5" required></textarea>

                <button type="submit" class="btn submit">Send Message</button>

            </form>
            <button id="closeOverlay" class="close-btn">&times;</button>
        </div>
    </div>

    <script>
        const contactBtn = document.getElementById('contactUsBtn');
        const contactOverlay = document.getElementById('contactOverlay');
        const closeOverlay = document.getElementById('closeOverlay');

        contactBtn.addEventListener('click', () => {
            contactOverlay.style.display = 'flex';
        });

        closeOverlay.addEventListener('click', () => {
            contactOverlay.style.display = 'none';
        });

        contactOverlay.addEventListener('click', (e) => {
            if (e.target === contactOverlay) {
                contactOverlay.style.display = 'none';
            }
        });
        const contactForm = document.querySelector('.contact-form');

        contactForm.addEventListener('submit', (e) => {
            setTimeout(() => {
                contactForm.reset();
            }, 100); // Delay reset to allow PHP to process
        });
    </script>
</body>

</html>