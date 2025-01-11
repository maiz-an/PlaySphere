<?php
session_start();
require 'db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../index.php#login-section");
    exit();
}

if (isset($_SESSION['user_id'])) {
    $staffId = $_SESSION['user_id'];
} else {
    echo "Session error: user_id not set. Redirecting to login...";
    header("Location: ../index.php#login-section");
    exit();
}

$staffName = htmlspecialchars($_SESSION['username']);


// Fetch staff details
$staffId = $_SESSION['user_id'];

// Define the districts and cities in PHP
$districtsAndCities = [
    "Ampara" => ["Ampara", "Kalmunai", "Sainthamaruthu"],
    "Anuradhapura" => ["Anuradhapura", "Kekirawa", "Mihintale"],
    "Badulla" => ["Badulla", "Bandarawela", "Haputale"],
    "Batticaloa" => ["Batticaloa", "Kattankudy", "Eravur"],
    "Colombo" => ["Colombo 1", "Colombo 2", "Colombo 3", "Nugegoda", "Dehiwala", "Mount Lavinia", "Moratuwa"],
    "Galle" => ["Galle", "Hikkaduwa", "Unawatuna", "Ambalangoda"],
    "Gampaha" => ["Negombo", "Ja-Ela", "Gampaha", "Ragama", "Wattala", "Kandana"],
    "Hambantota" => ["Hambantota", "Tangalle", "Tissamaharama"],
    "Jaffna" => ["Jaffna", "Chavakachcheri", "Point Pedro", "Nallur"],
    "Kalutara" => ["Kalutara", "Panadura", "Beruwala", "Aluthgama"],
    "Kandy" => ["Kandy", "Peradeniya", "Katugastota", "Pilimathalawa", "Digana"],
    "Kegalle" => ["Kegalle", "Mawanella", "Ruwanwella"],
    "Kilinochchi" => ["Kilinochchi", "Paranthan"],
    "Kurunegala" => ["Kurunegala", "Puttalam", "Kuliyapitiya", "Maho"],
    "Mannar" => ["Mannar", "Pesalai", "Adampan"],
    "Matale" => ["Matale", "Dambulla", "Sigiriya"],
    "Matara" => ["Matara", "Weligama", "Deniyaya", "Kamburugamuwa"],
    "Moneragala" => ["Moneragala", "Wellawaya", "Bibile"],
    "Mullaitivu" => ["Mullaitivu", "Puthukudiyiruppu", "Oddusuddan"],
    "Nuwara Eliya" => ["Nuwara Eliya", "Hatton", "Talawakele", "Bandarawela"],
    "Polonnaruwa" => ["Polonnaruwa", "Medirigiriya", "Hingurakgoda"],
    "Puttalam" => ["Puttalam", "Chilaw", "Wennappuwa"],
    "Ratnapura" => ["Ratnapura", "Balangoda", "Pelmadulla"],
    "Trincomalee" => ["Trincomalee", "Kinniya", "Muttur"],
    "Vavuniya" => ["Vavuniya", "Omanthai", "Cheddikulam"]
];


// Handle futsal creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_futsal'])) {
    $name = htmlspecialchars($_POST['name']);
    $district = htmlspecialchars($_POST['district']);
    $city = htmlspecialchars($_POST['city']);
    $price_per_hour = (float)$_POST['price_per_hour'];
    $description = htmlspecialchars($_POST['description']);
    $imagePath = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/futsals/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $imageName = time() . "_" . basename($_FILES['image']['name']);
        $imagePath = $uploadDir . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            $imagePath = 'uploads/futsals/' . $imageName;
        } else {
            $imagePath = null;
        }
    }

    // Insert futsal into the database
    $stmt = $pdo->prepare("INSERT INTO futsals (owner_id, name, location, price_per_hour, description, image) VALUES (?, ?, ?, ?, ?, ?)");
    $location = $city . ', ' . $district;
    $stmt->execute([$staffId, $name, $location, $price_per_hour, $description, $imagePath]);

    if ($futsal) {
        // Delete the futsal image if it exists
        if ($futsal['image'] && file_exists("../" . $futsal['image'])) {
            unlink("../" . $futsal['image']);
        }

        // Delete the futsal record
        $stmt = $pdo->prepare("DELETE FROM futsals WHERE id = ?");
        $stmt->execute([$futsalId]);
    }

    header("Location: futsal.php");
    exit();
}

// Fetch futsals owned by the staff member
$stmt = $pdo->prepare("SELECT * FROM futsals WHERE owner_id = ?");
$stmt->execute([$staffId]);
$futsals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Manage Futsals | PlaySphere</title>
    <link rel="icon" type="image/png" href="../images/fav.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="Styles/futsal.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <script>
        const districtsAndCities = <?= json_encode($districtsAndCities) ?>;

        function updateCities() {
            const districtSelect = document.getElementById('district');
            const citySelect = document.getElementById('city');
            const selectedDistrict = districtSelect.value;

            citySelect.innerHTML = ''; // Clear previous options
            if (selectedDistrict && districtsAndCities[selectedDistrict]) {
                districtsAndCities[selectedDistrict].forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                });
            }
        }
    </script>
    <style>
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
            padding: 20px;
            background-color: #1e1e1e;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
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
        margin: 0;
        background-color: #161313;
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

    /* Container */
    .container {
        padding: 1rem;
        margin: 0.5rem;
    }

    .container h1 {
        font-size: 1.5rem;
        text-align: center;
    }

    /* Add Futsal Form */
    #add-futsal-form {
        padding: 1rem;
        background-color: #252525;
        border-radius: 10px;
        margin-top: 1rem;
    }

    #add-futsal-form .form-row {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    #add-futsal-form label {
        font-size: 0.9rem;
        color: #ddd;
    }

    #add-futsal-form input,
    #add-futsal-form select,
    #add-futsal-form textarea {
        width: 100%;
        padding: 0.7rem;
        font-size: 0.9rem;
        border: 1px solid #444;
        border-radius: 5px;
        background-color: #252525;
        color: #fff;
    }

    .submit-btn {
        width: 100%;
        padding: 0.8rem;
        background-color: #bbd12b;
        color: #161313;
        border: none;
        border-radius: 5px;
        font-size: 1rem;
        font-weight: bold;
        cursor: pointer;
    }

    /* Futsal List */
    .futsal-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-top: 1rem;
    }

    .futsal-card {
        background-color: #252525;
        border-radius: 10px;
        padding: 1rem;
        text-align: center;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    }

    .futsal-card img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 5px;
        margin-bottom: 1rem;
    }

    .futsal-card h3 {
        font-size: 1.2rem;
        color: #bbd12b;
    }

    .futsal-card p {
        font-size: 0.9rem;
        color: #ddd;
        margin: 0.5rem 0;
    }

    .card-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .card-actions a,
    .card-actions button {
        font-size: 0.9rem;
        padding: 0.7rem;
        border: none;
        border-radius: 5px;
        text-align: center;
        background-color: #bbd12b;
        color: #161313;
        font-weight: bold;
        text-decoration: none;
    }

    .card-actions a:hover,
    .card-actions button:hover {
        background-color: #a3c61f;
    }

    .delete-btn {
        background-color: #bb2020;
    }

    .delete-btn:hover {
        background-color: #a31a1a;
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
        font-size: 1rem;
    }

    .contact-form input,
    .contact-form textarea {
        font-size: 0.9rem;
    }

    .contact-form button {
        padding: 0.6rem 1rem;
        font-size: 1rem;
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
    <h1>Manage Your Futsals</h1>

    <!-- Button to Toggle Add Futsal Form -->
    <button id="add-futsal-btn" onclick="toggleForm()">Add Futsal</button>
<!-- Search Bar -->
<div class="search-container">
        <input
            type="text"
            id="search-bar"
            placeholder="Search futsals by name, city, or district..."
            oninput="filterFutsals()"
        />
    </div>
    <!-- Form to Add Futsal -->
        <div id="add-futsal-form" style="display:none;">
            <form action="futsal.php" method="post" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Futsal Name:</label>
                        <input type="text" id="name" name="name" placeholder="Enter futsal name" required>
                    </div>
                    <div class="form-group">
                        <label for="district">District:</label>
                        <select id="district" name="district" onchange="updateCities()" required>
                            <option value="">Select District</option>
                            <?php foreach (array_keys($districtsAndCities) as $district): ?>
                                <option value="<?= $district ?>"><?= $district ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City:</label>
                        <select id="city" name="city" required>
                            <option value="">Select City</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="price_per_hour">Price (Rs/hr:)</label>
                        <input type="number" id="price_per_hour" name="price_per_hour" step="0.01" placeholder="e.g. 1000" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="description">Phone:</label>
                        <textarea id="description" name="description" rows="1" placeholder="Enter your fulsal contact number" required></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="image">Upload Image:</label>
                        <input type="file" id="image" name="image" accept="image/*">
                    </div>
                </div>

                <div class="form-row">
                    <button type="submit" name="add_futsal" class="submit-btn">Add Futsal</button>
                </div>
            </form>
        </div>


     <!-- List of Futsals -->
     <div id="futsal-list" class="futsal-list">
        <?php if ($futsals): ?>
            <?php foreach ($futsals as $futsal): ?>
                <div class="futsal-card" data-search="<?= strtolower(htmlspecialchars($futsal['name'])) . ' ' . strtolower(htmlspecialchars($futsal['location'])) ?>">
                    <img src="../<?= htmlspecialchars($futsal['image']) ?>" alt="Futsal Image">
                    <h3><?= htmlspecialchars($futsal['name']) ?></h3>
                    <p><strong>Location:</strong> <?= htmlspecialchars($futsal['location']) ?></p>
                    <p><strong>Price/Hour:</strong> Rs.<?= htmlspecialchars($futsal['price_per_hour']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($futsal['description']) ?></p>
                    <div class="card-actions">
                        <a href="edit_futsal.php?id=<?= $futsal['id'] ?>" class="edit-btn">Edit Details</a>
                        <form action="delete_futsal.php" method="post" onsubmit="return confirm('Are you sure you want to delete this futsal?');" class="delete-form">
                            <input type="hidden" name="futsal_id" value="<?= $futsal['id'] ?>">
                            <button type="submit" class="delete-btn">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No futsals added yet.</p>
        <?php endif; ?>
    </div>
</div>
    <script>
    function toggleForm() {
        const form = document.getElementById('add-futsal-form');
        form.style.display = form.style.display === 'block' ? 'none' : 'block';
    }

    function filterFutsals() {
        const searchInput = document.getElementById('search-bar').value.toLowerCase();
        const futsalCards = document.querySelectorAll('.futsal-card');

        futsalCards.forEach(card => {
            const searchText = card.getAttribute('data-search');
            if (searchText.includes(searchInput)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
</script>
<style>
    .search-container {
        margin-bottom: 20px;
        text-align: center;
    }

    #search-bar {
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

    #search-bar::placeholder {
        color: #aaa;
    }
</style>
</div>
<script>
    function toggleForm() {
        const form = document.getElementById('add-futsal-form');
        form.style.display = form.style.display === 'block' ? 'none' : 'block';
    }
</script>

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
