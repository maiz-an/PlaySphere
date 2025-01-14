<?php
session_start();
require 'staff/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT id, name, username, password_hash FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Set session variables
            $_SESSION['username'] = $user['username'];
            $_SESSION['customer_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            header("Location: index.php#login-section");
            exit();
        } else {
            echo "Invalid username or password.";
        }
    } catch (PDOException $e) {
        echo "Database Error: " . $e->getMessage();
    }
}

$userName = htmlspecialchars($_SESSION['username']);
$Name = htmlspecialchars($_SESSION['name']);

$customer_id = $_SESSION['customer_id'] ?? null;

if (!$customer_id) {
    header("Location: index.php#login-section");
    exit();
}

// Fetch the user's name from the session
$userName = htmlspecialchars($_SESSION['username']);
$Name = htmlspecialchars($_SESSION['name']);

// Fetch customer bookings
$bookings = [];
try {
    $stmt = $pdo->prepare("
    SELECT b.id, f.name AS futsal_name, b.start_time, b.end_time, b.total_cost, b.status, b.cancel 
    FROM bookings b
    JOIN futsals f ON b.futsal_id = f.id
    WHERE b.customer_id = :customer_id
    ORDER BY b.start_time DESC
");

    $stmt->execute(['customer_id' => $customer_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching bookings: " . $e->getMessage());
}

// Fetch all futsals and their bookings
$futsals = [];
try {
    $stmt = $pdo->prepare("
SELECT f.id, f.name, f.location, f.price_per_hour, f.description, f.image,
       (SELECT GROUP_CONCAT(CONCAT(b.start_time, '|', b.end_time)) 
        FROM bookings b 
        WHERE b.futsal_id = f.id 
          AND b.start_time >= NOW() 
          AND b.start_time <= DATE_ADD(NOW(), INTERVAL 7 DAY)
          AND b.status NOT IN ('cancelled', 'refunded')) AS bookings
FROM futsals f
ORDER BY f.name ASC;
    ");
    $stmt->execute();
    $futsals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching futsals: " . $e->getMessage());
}




?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Welcome | PlaySphere&copy;</title>
    <link rel="icon" type="image/png" href="images/fav.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="Styles/index.css" rel="stylesheet">
</head>
<style>
    .table-wrapper {
        max-height: 350px;
        overflow-y: auto;
        margin-top: 20px;
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
        border: 1px solid #444;
        color: #ddd;
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
        width: 10px;
    }

    .table-wrapper::-webkit-scrollbar-thumb {
        background: #bbd12b;
        border-radius: 5px;
    }

    .table-wrapper::-webkit-scrollbar-track {
        background: #252525;
    }

    .search-container {
        margin-top: 1rem !important;
        margin-bottom: 20px;
        text-align: center;
    }

    .search-container input {
        text-align: center;
        width: 80%;
        padding: 10px;
        font-size: 1rem;
        border: 1px solid #444;
        border-radius: 9px;
        background-color: #252525;
        color: #ddd;
    }

    .futsals-grid {
        display: flex;
        gap: 20px;
        overflow-x: auto;
        padding-bottom: 10px;
        scrollbar-width: thin;
        scrollbar-color: #bbd12b #252525;
    }

    .futsals-grid::-webkit-scrollbar {
        height: 10px;
    }

    .futsals-grid::-webkit-scrollbar-thumb {
        background: #bbd12b;
        border-radius: 5px;
    }

    .futsals-grid::-webkit-scrollbar-track {
        background: #252525;
    }

    .futsal-card {
        background-color: #252525;
        border: 1px solid #444;
        border-radius: 10px;
        min-width: 310px;
        height: auto;
        overflow: hidden;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .futsal-card img {
        width: 100%;
        height: 150px;
        object-fit: contain;
    }

    .futsal-details {
        padding: 15px;
        color: #ddd;
        text-align: left;
    }


    .futsal-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
    }



    .futsal-details h2 {
        font-size: 1.2rem;
        color: #bbd12b;
        margin-bottom: 10px;
    }

    .fuhead h1 {
        font-size: 2.5rem;
        color: #bbd12b;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }

    .futsal-details p {
        margin: 5px 0;
        font-size: 0.8rem;
    }

    @media (max-width: 500px) {
        .hero-nav {
            margin-left: -1.7rem !important;
            gap: 5rem !important;
            margin-top: 3rem;
            margin-bottom: 2rem !important;
            padding: 0.5rem;
        }

        .hero-nav a {
            font-size: 0.9rem;
            text-decoration: none;
            color: #fff;
            transition: color 0.3s ease;
            width: 0rem !important;
            border: 1px solid transparent;
        }


        #home-section {
            padding: 1rem;
            text-align: center;
        }

        .hero-content {
            margin-top: 10rem !important;
            max-width: 100%;
            margin: 0 auto;
        }

        .hero-content span {
            font-size: 18px;
            margin-left: 0.2rem !important;
            display: block;
            text-align: left;
        }

        .hero-content h1 {
            font-size: 2rem;
            line-height: 1.2;
            margin: 1rem 0;
            text-shadow: 1px 1px 0 #bbd12b, 2px 2px 0 #a3c61f, 4px 4px 0 #6e9011;
        }

        .hero-content p {
            font-size: 0.75rem;
            margin: 1rem 0;
            max-width: 90%;
            text-align: center;
        }

        .btn {
            font-size: 1rem;
            padding: 0.6rem 1rem;
            margin-top: 1rem;
            display: inline-block;
        }

        .image-wrapper {
            margin-top: 1rem;
            margin-left: 14rem;
            width: 100%;
            height: auto;
            display: flex;
            justify-content: center;
        }

        .styled-image {
            width: 80%;
            height: auto;
        }

        .footer {
            margin-left: -7.5rem !important;
            margin-top: 4rem !important;
            width: 90%;
            text-align: left;
            padding: 1rem;
            background-color: #161313;
            color: #fff;
        }

        #booking-section {
            padding: 1rem;
        }

        .venues-container {
            text-align: center;
            margin-top: 1rem;
        }

        .venues-container h1 {
            font-size: 1.6rem !important;
            text-align: left !important;
            margin-top: 3rem !important;
            margin-bottom: 0 !important;
            margin-bottom: 1rem;
            color: #bbd12b;
        }

        .venues-container .btn {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            margin-bottom: 1rem;
        }

        .table-wrapper {
            margin-left: -0.8rem !important;
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

        .btn {

            margin-left: -15rem !important;

        }

        .btn:hover {
            opacity: 0.8;
        }

        #futsals-section {
            padding: 1rem;
        }

        .venues-container {
            text-align: center;
            margin-top: 1rem;
        }

        .venues-container h1 {
            font-size: 1.6rem;
            text-align: left;
            color: #bbd12b;
            margin-bottom: 1rem;
        }

        .search-container {
            margin-bottom: 1rem;
            text-align: center;
        }

        .search-container input {
            width: 90%;
            padding: 0.5rem;
            font-size: 0.9rem;
            border: 1px solid #444;
            border-radius: 5px;
            background-color: #252525;
            color: #ddd;
        }

        .futsals-grid {
            display: flex;
            flex-wrap: nowrap;
            gap: 1rem;
            overflow-x: auto;
            padding-bottom: 1rem;
            scrollbar-width: thin;
            scrollbar-color: #bbd12b #252525;
        }

        .futsals-grid::-webkit-scrollbar {
            height: 8px;
        }

        .futsals-grid::-webkit-scrollbar-thumb {
            background: #bbd12b;
            border-radius: 5px;
        }

        .futsals-grid::-webkit-scrollbar-track {
            background: #252525;
        }

        .futsal-card {
            flex: 0 0 80%;
            background-color: #252525;
            border: 1px solid #444;
            border-radius: 10px;
            height: auto;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            scroll-snap-align: start;
        }

        .futsal-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .futsal-details {
            padding: 10px;
            color: #ddd;
            text-align: left;
        }

        .futsal-details h2 {
            font-size: 1rem;
            color: #bbd12b;
            margin-bottom: 0.5rem;
        }

        .futsal-details p {
            font-size: 0.8rem;
            margin: 0.3rem 0;
        }

        .futsals-grid {
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
        }

        .futsals-grid>.futsal-card:first-child {
            margin-left: 1rem;
        }

        .futsals-grid>.futsal-card:last-child {
            margin-right: 1rem;
        }
    }
</style>

<body>
    <a href="#home-section"><img src="images/logo.png" class="logo" alt="logo"></a>

    <!-- Navbar -->
    <nav class="hero-nav">
        <a href="#home-section">Home</a>
        <a href="#booking-section">Bookings</a>
        <a href="#futsals-section">Futsals</a>
        <a href="logout.php">Logout</a>
    </nav>

    <!-- Hero Section -->
    <section id="home-section" class="hero" style="background-color: #161313;">
        <div class="hero-content">
            <span style="font-size: 22px; margin-left: -1rem; color: #bbd12b; font-family: 'Lato', sans-serif; font-weight: 700;">Welcome</span>
            <h1 style="text-shadow: 1px 1px 0 #bbd12b, 2px 2px 0 #a3c61f, 4px 4px 0 #6e9011;">
                <?php echo $Name; ?>
            </h1>
            <p style="text-align: justify; margin-left: 1rem; max-width: 80%;">
                Thank you for joining PlaySphere. Explore top-notch sports venues, book seamlessly, and elevate your game with facilities tailored for champions.
            </p>
            <a href="booking_page.php" class="btn spotlight">Book Now</a>
        </div>
        <div class="image-wrapper">
            <img src="images/wrap3.png" alt="Stylized Image" class="styled-image">
        </div>
    </section>


    <!-- booking sectoin -->

    <section id="booking-section" class="section">
        <div class="venues-container">
            <br>
            <h1>My Bookings</h1>
            <a href="booking_page.php" class="btn">Add Booking</a>

            <!-- Search Bar -->
            <div class="search-container">
                <input
                    type="text"
                    id="booking-search-bar"
                    placeholder="Search by Booking ID or Futsal"
                    oninput="filterBookings()" />
            </div>

            <?php if (!empty($bookings)): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Futsal</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Total Cost</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="booking-table-body">
                            <?php foreach ($bookings as $booking): ?>
                                <?php
                                // Parse booking start and end times
                                $startDate = new DateTime($booking['start_time']);
                                $now = new DateTime();
                                $minutesUntilStart = ($startDate->getTimestamp() - $now->getTimestamp()) / 60;

                                // Label for the booking date
                                if ($startDate->format('Y-m-d') === $now->format('Y-m-d')) {
                                    $label = "Today";
                                } elseif ($startDate->format('Y-m-d') === $now->modify('+1 day')->format('Y-m-d')) {
                                    $label = "Tomorrow";
                                } else {
                                    $label = $startDate->format('Y-m-d');
                                }
                                ?>
                                <tr data-search="<?= htmlspecialchars($booking['id']) . ' ' . strtolower(htmlspecialchars($booking['futsal_name'])) ?>">
                                    <td><?= htmlspecialchars($booking['id']) ?></td>
                                    <td><?= htmlspecialchars($booking['futsal_name']) ?></td>
                                    <td><?= htmlspecialchars($label) ?> <?= $startDate->format('H:i') ?></td>
                                    <td><?= htmlspecialchars((new DateTime($booking['end_time']))->format('H:i')) ?></td>
                                    <td>Rs. <?= htmlspecialchars(number_format($booking['total_cost'], 2)) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($booking['status'])) ?></td>
                                    <td>
                                        <?php if ($booking['cancel'] != 1 && $booking['status'] !== 'cancelled' && $booking['status'] !== 'refunded' && $minutesUntilStart > 30): ?>
                                            <!-- Show Cancel Button -->
                                            <form action="cancelbooking.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                                <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['id']) ?>">
                                                <button type="submit" class="btn1" style="background-color: #bb2020; color: white; padding: 5px 10px; border-radius: 5px; cursor: pointer;">Cancel</button>
                                            </form>
                                        <?php elseif ($booking['status'] === 'confirmed'): ?>
                                            <span style="color: #ffcc00;"><img src="images/formation.png" alt="cancel time out" style="width: 56px; height: auto;"></span>
                                        <?php elseif ($booking['status'] === 'completed'): ?>
                                            <span style="color: #00cc00;"><img src="images/football-award.png" alt="completed" style="width: 56px; height: auto;"></span>
                                        <?php elseif ($booking['status'] === 'playing'): ?>
                                            <span style="color: #00cc00;"><img src="images/soccer.png" alt="playing" style="width: 56px; height: auto;"></span>
                                        <?php else: ?>
                                            <!-- Show "Cannot Cancel" Label -->
                                            <span style="color: #aaa;"></span>
                                        <?php endif; ?>
                                        <?php if ($booking['status'] === 'cancelled'): ?>
                                            Refunding...
                                        <?php endif; ?>
                                        <?php if ($booking['status'] === 'refunded'): ?>
                                            Refunded.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No bookings found. <a href="booking_page.php">Make a booking now</a>.</p>
            <?php endif; ?>
        </div>
    </section>

    <script>
        function filterBookings() {
            const searchInput = document.getElementById('booking-search-bar').value.toLowerCase();
            const bookingRows = document.querySelectorAll('#booking-table-body tr');

            bookingRows.forEach(row => {
                const searchText = row.getAttribute('data-search');
                if (searchText.includes(searchInput)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>

    <style>
        /* Styles for the search bar */
        .search-container {
            margin-bottom: 20px;
            text-align: center;
        }

        #booking-search-bar {
            width: 100%;
            max-width: 500px;
            padding: 10px;
            font-size: 1rem;
            border: 1px solid #444;
            border-radius: 5px;
            background-color: #252525;
            color: #fff;
            outline: none;
        }

        #booking-search-bar::placeholder {
            color: #aaa;
        }
    </style>



    <!-- Futsal section -->

    <section id="futsals-section" class="section">
        <div class="venues-container">
            <br>
            <h1 style="font-size: 2.5rem; color: #bbd12b; margin-bottom: 1rem; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">
                Available Futsals
            </h1>

            <div class="search-container">
                <input type="text" id="futsal-search" placeholder="Search by name, location, or price">
            </div> <br>
            <div class="futsals-grid" id="futsals-grid">
                <?php if (!empty($futsals)): ?>
                    <?php foreach ($futsals as $futsal): ?>
                        <div class="futsal-card">
                            <img src="<?= htmlspecialchars($futsal['image']) ?>" alt="<?= htmlspecialchars($futsal['name']) ?>">
                            <div class="futsal-details">
                                <h2><?= htmlspecialchars($futsal['name']) ?></h2>
                                <p>Location: <?= htmlspecialchars($futsal['location']) ?></p>
                                <p>Price: Rs. <?= number_format($futsal['price_per_hour'], 2) ?>/hour</p>
                                <p>Phone: <?= htmlspecialchars($futsal['description']) ?></p>
                                <p>
                                <strong>Bookings:</strong><br>
                                    <?php
                                    if ($futsal['bookings']) {
                                        $bookings = explode(',', $futsal['bookings']);
                                        foreach ($bookings as $booking) {
                                            list($start, $end) = explode('|', $booking);
                                            $startDate = new DateTime($start);
                                            $endDate = new DateTime($end);
                                            // Determine the display label
                                            if ($startDate->format('Y-m-d') === (new DateTime())->format('Y-m-d')) {
                                                $label = "Today";
                                            } elseif ($startDate->format('Y-m-d') === (new DateTime())->modify('+1 day')->format('Y-m-d')) {
                                                $label = "Tomorrow";
                                            } else {
                                                $label = $startDate->format('d F Y');
                                            }
                                            // Change time format to 12-hour format with AM/PM
                                            echo "<span>{$label} : {$startDate->format('g:i A')} to {$endDate->format('g:i A')}</span><br>";
                                        }
                                    } else {
                                        echo 'No bookings yet';
                                    }
                                    ?>
                                </p>

                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No futsals available at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <script>
        document.getElementById('futsal-search').addEventListener('input', function() {
            const searchQuery = this.value.toLowerCase();
            const futsalCards = document.querySelectorAll('.futsal-card');

            futsalCards.forEach(card => {
                const name = card.querySelector('h2').textContent.toLowerCase();
                const location = card.querySelector('p:nth-of-type(1)').textContent.toLowerCase();
                const price = card.querySelector('p:nth-of-type(2)').textContent.toLowerCase();

                if (name.includes(searchQuery) || location.includes(searchQuery) || price.includes(searchQuery)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>



    <!-- About Section -->
    <section id="about-section" class="section">
        <div class="about-container">
            <h1 style="margin-top: 5rem;">About Us</h1>
            <p class="about-description">
                PlaySphere is dedicated to providing world-class sports experiences. From seamless booking to cutting-edge facilities, we ensure excellence for all athletes.
            </p>
            <div class="about-features">
                <div class="feature">
                    <i class="fas fa-building feature-icon"></i>
                    <h3>World-Class Facilities</h3>
                    <p>Top-notch venues maintained to international standards.</p>
                </div>
                <div class="feature">
                    <i class="fas fa-calendar-check feature-icon"></i>
                    <h3>Seamless Booking</h3>
                    <p>Effortlessly book your favorite sports venues online.</p>
                </div>
                <div class="feature">
                    <i class="fas fa-headset feature-icon"></i>
                    <h3>Dedicated Support</h3>
                    <p>24/7 support to make your experience enjoyable and smooth.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p class="copyright">
            &copy; 2025 PlaySphere | Designed by team PlaySphere
        </p>
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

        // Open overlay
        contactBtn.addEventListener('click', () => {
            contactOverlay.style.display = 'flex';
        });

        // Close overlay
        closeOverlay.addEventListener('click', () => {
            contactOverlay.style.display = 'none';
        });

        // Close overlay when clicking outside the content
        contactOverlay.addEventListener('click', (e) => {
            if (e.target === contactOverlay) {
                contactOverlay.style.display = 'none';
            }
        });
        const contactForm = document.querySelector('.contact-form');

        // Reset form after successful submission
        contactForm.addEventListener('submit', (e) => {
            setTimeout(() => {
                contactForm.reset();
            }, 100);
        });
    </script>
</body>

</html>