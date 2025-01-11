<?php
session_start();
require '../db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../index.php#login-section");
    exit();
}

$staffName = htmlspecialchars($_SESSION['username']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Servior Dashboard | PlaySphere</title>
    <link rel="icon" type="image/png" href="../images/fav.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../Styles/staff.css" rel="stylesheet">
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

        nav.hero-nav img.logo {
            width: 50px;
            margin-right: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            height: 30rem;
            padding: 20px;
            background-color: #1e1e1e;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
        }

        .container h1 {
            text-align: center;
            color: #bbd12b;
            margin-bottom: 20px;
            font-size: 2.5rem;
        }

        .container p {
            text-align: center;
            color: #ddd;
            margin-bottom: 20px;
            font-size: 1.2rem;
        }

        .features {
            display: flex;
            justify-content: space-around;
            margin-top: 30px;
        }

        .feature-card {
            background-color: #222;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            width: 250px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
        }

        .feature-card h3 {
            color: #bbd12b;
            margin-bottom: 10px;
            font-size: 1.5rem;
        }

        .feature-card p {
            color: #ddd;
            font-size: 1rem;
            margin-bottom: 15px;
        }

        .feature-card a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #bbd12b;
            color: #161313;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: background-color 0.3s ease-in-out;
        }

        .feature-card a:hover {
            background-color: #a3c61f;
        }

        footer.footer {
            text-align: center;
            padding: 20px;
            background-color: #000;
            color: #bbd12b;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        footer a{
            text-decoration: none;
            color: #bbd12b;
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
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

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
        @media (max-width: 500px) {
            body {
                padding: 0;
                overflow-x: hidden;
            }

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

            .container {
                width: 90%;
                margin: 1rem auto;
                padding: 1rem;
                height: fit-content !important;
            }

            .container h1 {
                font-size: 1.8rem;
                margin-bottom: 1rem;
            }

            .container p {
                font-size: 1rem;
                margin-bottom: 1rem;
            }

            /* Features section */
            .features {
                flex-direction: column;
                gap: 1rem;
                justify-content: center;
            }

            .feature-card {
                width: 90%;
                padding: 1rem;
                text-align: center;
            }

            .feature-card h3 {
                font-size: 1.2rem;
                margin-bottom: 0.5rem;
            }

            .feature-card p {
                font-size: 0.9rem;
                margin-bottom: 0.5rem;
            }

            .feature-card a {
                font-size: 0.9rem;
                padding: 0.5rem 1rem;
            }

            /* Footer */
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
    <h1>Welcome, <?= $staffName ?>!</h1>
    <p>Manage your assigned futsal and view your profile information.</p>

    <div class="features">
        <!-- Feature 1 -->
        <div class="feature-card">
            <h3>Assigned futsal</h3>
            <p>View and manage your assigned futsal efficiently.</p>
            <a href="futsal.php">Go to futsal</a>
        </div>

        <!-- Feature: Futsal Bookings -->
    <div class="feature-card">
        <h3>Futsal Bookings</h3>
        <p>View and manage all bookings for your futsal venues.</p>
        <a href="futsalBookings.php">Go to Bookings</a>
    </div>


        <!-- Feature 3 -->
        <div class="feature-card">
            <h3>Logout</h3>
            <p>Log out of your account securely.</p>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
</div>

<footer class="footer">
    <p>&copy; 2025 PlaySphere | Futsal Servior Dashboard Designed by team <a href="mailto:co.dex11@hotmail.com">CodeX11</a></p>
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
            &copy; 2025 PlaySphere | Designed by team <a href="mailto:co.dex11@hotmail.com?subject=Website%20Inquiry&body=I%20would%20like%20to%20learn%20more%20about..." target="_blank" style="z-index: -1;" style="text-decoration: underline; color: #bbd12b;" >CodeX11</a></p>
            
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
        }, 100);
    });

</script>
</body>
</html>
