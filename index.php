<?php
require 'staff/db.php';

$futsals = [];
try {
    $stmt = $pdo->prepare("
        SELECT f.id, f.name, f.location, f.price_per_hour, f.description, f.image,
               (SELECT GROUP_CONCAT(CONCAT(b.start_time, '|', b.end_time)) 
                FROM bookings b 
                WHERE b.futsal_id = f.id 
                  AND b.start_time <= NOW() 
                  AND b.end_time > NOW() 
                  AND b.status NOT IN ('cancelled', 'refunded')) AS bookings
        FROM futsals f
        ORDER BY f.name ASC
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
    <title>PlaySphere&copy;</title>
    <link rel="icon" type="image/png" href="images/fav.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <link href="Styles/index.css" rel="stylesheet">
    <style>
    /* Search bar styling */
    .search-container {
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

    /* Horizontal scrolling for futsals */
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
    </style>
    <script>
    function scrollToSection() {
        if (!window.location.hash) {
            window.location.href = window.location.href + '#home-section';
        }
    }
</script>

</head>
<body onload="scrollToSection()">

    <a href="#home-section"><img src="images/logo.png" class="logo" alt="logo"></a>

    <nav class="hero-nav">
        <a href="#home-section">Home</a>
        <a href="#futsals-section">Futsals</a>
        <a href="#about-section">About Us</a>
        <a href="#login-section">Login</a>
    </nav>

    <section id="home-section" class="hero" style="background-color: #161313; z-index: -1000;">
    <div class="hero-content" style="z-index: -100;">
    <span style="font-size: 22px; margin-left: -1rem; color: #bbd12b; font-family: 'Lato', sans-serif; font-weight: 700;" class="the">the</span>

    <h1 style="text-shadow: 1px 1px 0 #bbd12b, 2px 2px 0 #a3c61f, 3px 3px 3px rgba(110, 144, 17, 0.8), 4px 4px 0 #6e9011; color: white;">

            PlaySphere<sup style="font-size: 35px;">&copy;</sup>
        </h1>

        <p style="text-align: justify; margin-left: 1rem; max-width: 80%;"  style="z-index: -100;">
            Discover the ultimate sports experience with top-notch  
            venues tailored for all your indoor activities. Book seamlessly, 
            compete confidently, and enjoy world-class facilities designed for champions.
        </p>
        <a href="#login-section" class="btn spotlight"  style="z-index: 1;">BOOK NOW</a>
    </div>
    <div class="image-wrapper">
        <img src="images/wrap3.png" alt="Stylized Image" class="styled-image">
    </div>

    <!-- Copyright Section -->
    <p class="copyright" style="z-index: 1;">
        &copy; 2025 PlaySphere | Designed by team
        <a href="mailto:co.dex11@hotmail.com?subject=Website%20Inquiry&body=I%20would%20like%20to%20learn%20more%20about..." target="_blank" style="z-index: -1;">CodeX11</a>
    </p>
</section>


<!-- Futsal section -->

<section id="futsals-section" class="section">
    <div class="venues-container">
     <br>
     <h1 class="futsalhead" style="font-size: 2.2rem; color: #bbd12b; margin-top: 2rem; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5); text-align: center;">
    Available Futsals</h1>

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
                                    $now = new DateTime();
                                    // Determine the display label
                                    if ($startDate->format('Y-m-d') === $now->format('Y-m-d')) {
                                        $label = "Today";
                                    } elseif ($startDate->format('Y-m-d') === $now->modify('+1 day')->format('Y-m-d')) {
                                        $label = "Tomorrow";
                                    } else {
                                        $label = $startDate->format('Y-m-d');
                                    }
                                    echo "<span>{$label}: {$startDate->format('H:i')} to {$endDate->format('H:i')}</span><br>";
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




<section id="about-section" class="section">
    <div class="about-container">
        <h1 style="margin-top: 6rem;">About Us</h1>
        <p class="about-description">
            PlaySphere is committed to providing the ultimate sports experience. From top-notch facilities to seamless booking, we strive to ensure your satisfaction and success.
        </p>
        <div class="about-features">
            <div class="feature">
                <i class="fas fa-building feature-icon"></i>
                <h3>World-Class Facilities</h3>
                <p>Our venues are equipped with cutting-edge technology and maintained to meet international standards.</p>
            </div>
            <div class="feature">
                <i class="fas fa-calendar-check feature-icon"></i>
                <h3>Seamless Booking</h3>
                <p>Our user-friendly platform makes it quick and easy to book your favorite sports venues online.</p>
            </div>
            <div class="feature">
                <i class="fas fa-headset feature-icon"></i>
                <h3>Dedicated Support</h3>
                <p>Our team is always here to assist you and ensure a smooth and enjoyable experience.</p>
            </div>
        </div>
    </div>
</section>

<section id="login-section" class="section">
    <div class="login-container">
    <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>
        <div class="form-wrapper">
            <!-- Login Form -->
            <div class="form-content login-form active">
                <h2>Login</h2>
                <form action="login.php" method="post" class="login-form">
                    <label for="login-username">Username</label>
                    <input type="text" id="usernameOrEmail" name="usernameOrEmail" 
               placeholder="Enter your username or email" 
               value="<?php echo isset($_POST['usernameOrEmail']) ? htmlspecialchars($_POST['usernameOrEmail']) : ''; ?>" required />
<br>
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" placeholder="Enter your password" required />
                    <span style="text-align: right; text-decoration: underline; color: #bbd12b; font-size: 11px;"><a href="forgot_password.php" class="btn-lg">Forgot Password?</a></span>
<br>
                    <button type="submit" value="login" class="btn submit">Login</button>
                </form>
                <p class="toggle-text">
                    Don't have an account? 
                    <span class="toggle-link" data-target="create-form">Create one</span>.
                </p>
    <img src="images/dhoni.png" alt="" class="loginimg">
            </div>
            <!-- Create Account Form -->
            <div class="form-content create-form" id="login-section-create">
    <h2>Create Account</h2>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error">
            <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>
    <form action="register.php" method="post" class="signup-form" id="register">
        <div class="form-row">
            <div class="form-field">
                <label for="signup-name">Name</label>
                <input type="text" id="signup-name" name="name" placeholder="Enter your name" required />
            </div>
            <div class="form-field">
                <label for="signup-username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required />
            </div> 
        </div>

        <div class="form-row">
            <div class="form-field">
            <label for="signup-email">Email</label>
            <input type="email" id="email" name="email" placeholder="name@example.com" required />
            </div>
            <div class="form-field">
            <label for="phone_number">Phone Number:</label>
            <input type="number" id="phone_number" name="phone_number" placeholder="07X XXX XXXX" min="0711111111" max="0799999999" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-field">
                <label for="signup-password">Password</label>
                <input type="password" id="signup-password" name="password" placeholder="Create a password" required />
            </div>
            <div class="form-field">
                <label for="signup-confirm-password">Confirm Password</label>
                <input type="password" id="signup-confirm-password" name="confirm-password" placeholder="re-enter password" required />
            </div>
        </div>

        <button type="submit" class="btn submit">Create Account</button>
    <img src="images/Cups _ Premier Trophies.png" alt="" class="loginimg1">

    </form>
    <p class="toggle-text">
        Already have an account? 
        <span class="toggle-link" data-target="login-form">Login</span>.
    </p>
</div>

        </div>
    </div>

</section>

<script>
document.querySelectorAll('.toggle-link').forEach(link => {
    link.addEventListener('click', (event) => {
        event.preventDefault();

        document.querySelectorAll('.form-content').forEach(form => {
            form.classList.remove('active');
        });

        const targetClass = event.target.getAttribute('data-target');
        document.querySelector(`.${targetClass}`).classList.add('active');
    });
});
</script>
<script>
document.getElementById('register').addEventListener('submit', function(e) {
    // Get password and confirm password values
    const password = document.getElementById('signup-password').value;
    const confirmPassword = document.getElementById('signup-confirm-password').value;

    // Check if passwords match
    if (password !== confirmPassword) {
        alert("Passwords do not match. Please ensure both passwords are the same.");
        e.preventDefault(); // Prevent form submission
        return false;
    }

    // Check password length and strength (example criteria)
    if (password.length < 8) {
        alert("Password must be at least 8 characters long.");
        e.preventDefault(); // Prevent form submission
        return false;
    }

    // Example of additional password strength validation (optional)
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/;
    if (!passwordRegex.test(password)) {
        alert("Password must contain at least one uppercase letter, one lowercase letter, and one number.");
        e.preventDefault(); // Prevent form submission
        return false;
    }

    // If everything is valid, the form will submit
    return true;
});
</script>


<!-- Floating Button -->
    <button id="contactUsBtn" class="floating-btn">
        <i class="fas fa-comments"></i>
    </button>

    <!-- Contact Us Overlay -->
    <div id="contactOverlay" class="overlay">
        <div class="overlay-content">
            <h2>Contact Us</h2>
            <p style="font-size: 16px;">We'd love to hear from you! Reach out to us for any inquiries or support. <br>
            &copy; 2025 PlaySphere | Designed by team <a href="mailto:co.dex11@hotmail.com?subject=Website%20Inquiry&body=I%20would%20like%20to%20learn%20more%20about..." target="_blank" style="z-index: -1;" style="text-decoration: underline; color: #bbd12b;" >CodeX11</a></p>
            <form action="contact.php" method="post" class="contact-form">
                <label for="contact-name">Name</label>
                <input type="text" id="contact-name" name="name" placeholder="Your Name" required />
                <label for="contact-email">Email</label>
                <input type="email" id="contact-email" name="email" placeholder="Your Email" required />
                <label for="contact-message">Message</label>
                <textarea id="contact-message" name="message" placeholder="Your Message" rows="3" required></textarea>
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
    }, 100);
});
</script>
</html>