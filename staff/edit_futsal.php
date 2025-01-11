<?php
session_start();
require 'db.php';

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../index.php#login-section");
    exit();
}

// Ensure user_id is set
if (isset($_SESSION['user_id'])) {
    $staffId = $_SESSION['user_id'];
} else {
    echo "Session error: user_id not set. Redirecting to login...";
    header("Location: ../index.php#login-section");
    exit();
}

// Fetch futsal details
if (!isset($_GET['id'])) {
    echo "Futsal ID is missing. Redirecting...";
    header("Location: futsal.php");
    exit();
}

$futsalId = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM futsals WHERE id = :id AND owner_id = :owner_id");
$stmt->execute(['id' => $futsalId, 'owner_id' => $staffId]);
$futsal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$futsal) {
    echo "Futsal not found or you don't have permission to edit it.";
    exit();
}

// Handle futsal update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $district = htmlspecialchars($_POST['district']);
    $city = htmlspecialchars($_POST['city']);
    $price_per_hour = (float)$_POST['price_per_hour'];
    $description = htmlspecialchars($_POST['description']);
    $imagePath = $futsal['image'];

    // Handle new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/futsals/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $imageName = time() . "_" . basename($_FILES['image']['name']);
        $newImagePath = $uploadDir . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $newImagePath)) {
            // Delete the old image if it exists
            if ($futsal['image'] && file_exists("../" . $futsal['image'])) {
                unlink("../" . $futsal['image']);
            }
            $imagePath = 'uploads/futsals/' . $imageName;
        }
    }

    // Update futsal in the database
    $stmt = $pdo->prepare("
        UPDATE futsals 
        SET name = ?, location = ?, price_per_hour = ?, description = ?, image = ?
        WHERE id = ? AND owner_id = ?
    ");
    $location = $city . ', ' . $district;
    $stmt->execute([$name, $location, $price_per_hour, $description, $imagePath, $futsalId, $staffId]);

    header("Location: futsal.php");
    exit();
}

// Extract current location
list($city, $district) = explode(', ', $futsal['location']);

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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <link rel="icon" type="image/png" href="../images/fav.png">
    <title>Edit Futsal | PlaySphere</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #161313;
            color: #fff;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #1e1e1e;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
        }

        h1 {
            font-size: 2rem;
            color: #bbd12b;
            margin-bottom: 20px;
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            font-size: 1rem;
            margin-bottom: 5px;
            color: #bbd12b;
        }

        select, input, textarea {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #444;
            border-radius: 5px;
            background-color: #252525;
            color: #ddd;
            font-size: 1rem;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            background-color: #bbd12b;
            color: #161313;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #a3c61f;
        }

        .current-image img {
            max-width: 40%;
            border-radius: 5px;
        }
        @media (max-width: 768px) {
    .container {
        width: 80%;
        margin: 20px auto;
    }

    .current-image img {
        max-width: 60%;
    }

    form label, form input, form select, form textarea, form button {
        width: 90%;
    }
}

    </style>
<script>
    // Districts and Cities data passed from PHP
    const districtsAndCities = <?= json_encode($districtsAndCities) ?>;
    // Function to update the city dropdown based on the selected district
    function updateCities(selectedCity = "") {
        const districtSelect = document.getElementById('district');
        const citySelect = document.getElementById('city');
        const selectedDistrict = districtSelect.value;

        citySelect.innerHTML = '<option value="">Select City</option>';
        if (selectedDistrict && districtsAndCities[selectedDistrict]) {
            districtsAndCities[selectedDistrict].forEach(city => {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                if (city === selectedCity) {
                    option.selected = true;
                }
                citySelect.appendChild(option);
            });
        }
    }

    // Preload the current district and city on page load
    window.onload = () => {
        const districtElement = document.getElementById('district');
        const currentDistrict = "<?= $district ?>";
        const currentCity = "<?= $city ?>";

        districtElement.value = currentDistrict;
        updateCities(currentCity);
    };

    // Function to show feedback messages
    function showFeedback(message, isSuccess = true) {
        const feedbackDiv = document.createElement('div');
        feedbackDiv.textContent = message;
        feedbackDiv.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 10px 20px;
            color: #fff;
            background-color: ${isSuccess ? '#4CAF50' : '#F44336'};
            border-radius: 5px;
            z-index: 1000;
            text-align: center;
        `;
        document.body.appendChild(feedbackDiv);

        setTimeout(() => feedbackDiv.remove(), 3000);
    }
</script>


</head>
<body>
<div class="container">
    <h1>Edit Futsal</h1>
    <form action="" method="POST" enctype="multipart/form-data">
        <label for="name">Futsal Name:</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($futsal['name']) ?>" required>

        <label for="district">District:</label>
        <select id="district" name="district" onchange="updateCities()" required>
            <option value="">Select District</option>
            <?php foreach (array_keys($districtsAndCities) as $districtOption): ?>
                <option value="<?= $districtOption ?>"><?= $districtOption ?></option>
            <?php endforeach; ?>
        </select>

        <label for="city">City:</label>
        <select id="city" name="city" required>
            <option value="">Select City</option>
        </select>

        <label for="price_per_hour">Price (Rs/hr):</label>
        <input type="number" id="price_per_hour" name="price_per_hour" step="0.01" value="<?= htmlspecialchars($futsal['price_per_hour']) ?>" required>

        <label for="description">Phone:</label>
        <textarea id="description" name="description" rows="1" required><?= htmlspecialchars($futsal['description']) ?></textarea>

        <label for="image">Upload New Image (optional):</label>
        <input type="file" id="image" name="image" accept="image/*">

        <div class="current-image">
            <p>Current Image:</p>
            <img src="../<?= htmlspecialchars($futsal['image']) ?>" alt="Current Futsal Image">
        </div>

        <button type="submit">Update Futsal</button>
    </form>
</div>
</body>
</html>
