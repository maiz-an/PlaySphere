<?php
session_start();
require 'db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php#login-section");
    exit();
}

if (!isset($pdo)) {
    die('Database connection not established.');
}

$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
$query = "SELECT id, name, username, email, phone_number, role, created_at FROM users";
if (!empty($roleFilter)) {
    $query .= " WHERE role = ?";
}
$stmt = $pdo->prepare($query);
if (!empty($roleFilter)) {
    $stmt->execute([$roleFilter]);
} else {
    $stmt->execute();
}
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle creating a new staff user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_staff'])) {
    $name = htmlspecialchars($_POST['name']);
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $phone_number = htmlspecialchars($_POST['phone_number']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'staff';

    $insertQuery = "INSERT INTO users (name, username, email, phone_number, password_hash, role) 
                    VALUES (?, ?, ?, ?, ?, ?)";
    $insertStmt = $pdo->prepare($insertQuery);
    $insertStmt->execute([$name, $username, $email, $phone_number, $password, $role]);
    header("Location: admin.php");
    exit();
}

// Handle logout confirmation
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    session_unset();
    session_destroy();
    header("Location: ../index.php");
    exit();
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $userIdToDelete = intval($_POST['delete_user_id']);
    $deleteQuery = "DELETE FROM users WHERE id = ?";
    $deleteStmt = $pdo->prepare($deleteQuery);
    $deleteStmt->execute([$userIdToDelete]);

    header("Location: admin.php");
    exit();
}

// Fetch all futsals
$futsalsQuery = "SELECT id, name, location, price_per_hour, description, created_at FROM futsals";
$futsalsStmt = $pdo->prepare($futsalsQuery);
$futsalsStmt->execute();
$futsals = $futsalsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all bookings
$bookingsQuery = "
    SELECT 
        b.id AS booking_id, 
        f.name AS futsal_name, 
        u.name AS customer_name, 
        b.start_time, 
        b.end_time, 
        b.status, 
        b.payment_status, 
        b.total_cost 
    FROM bookings b
    JOIN futsals f ON b.futsal_id = f.id
    JOIN users u ON b.customer_id = u.id
    ORDER BY b.start_time DESC";
$bookingsStmt = $pdo->prepare($bookingsQuery);
$bookingsStmt->execute();
$bookings = $bookingsStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle search for futsals
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$futsalsQuery = "
    SELECT 
        f.id, 
        f.name, 
        f.location, 
        f.price_per_hour, 
        f.description, 
        f.created_at, 
        u.name AS owner_name 
    FROM futsals f
    JOIN users u ON f.owner_id = u.id
";

if (!empty($searchTerm)) {
    $futsalsQuery .= " WHERE f.name LIKE ? OR f.location LIKE ?";
}

$futsalsStmt = $pdo->prepare($futsalsQuery);
if (!empty($searchTerm)) {
    $futsalsStmt->execute(["%$searchTerm%", "%$searchTerm%"]);
} else {
    $futsalsStmt->execute();
}
$futsals = $futsalsStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/fav.png">
    <title>Admin Dashboard | PlaySphere</title>
    <link rel="icon" type="image/png" href="../images/fav.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="Styles/index.css" rel="stylesheet">
    <link href="Styles/admin.css" rel="stylesheet">
    <style>
        .logs-table table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background-color: #1e1e1e;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
}

.logs-table th, .logs-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #444;
    font-size: 1rem;
    color: #ddd;
}

.logs-table th {
    background-color: #222;
    color: #bbd12b;
    text-transform: uppercase;
    font-weight: bold;
}

.logs-table tr:hover {
    background-color: #252525;
    transition: background-color 0.3s ease-in-out;
}

.logs-table td {
    color: #ccc;
}

.logs-table td:first-child {
    font-family: monospace;
    color: #bbb;
}

.logs-table {
    overflow-x: auto;
    border-radius: 10px;
}

/* Responsive Styling */
@media (max-width: 768px) {
    .logs-table table {
        font-size: 0.9rem;
    }

    .logs-table th, .logs-table td {
        padding: 10px;
    }

    .logs-table tr {
        display: block;
        margin-bottom: 10px;
        border-bottom: 2px solid #444;
    }

    .logs-table td {
        display: flex;
        justify-content: space-between;
    }

    .logs-table td:before {
        content: attr(data-label);
        font-weight: bold;
        text-transform: uppercase;
        color: #bbd12b;
        flex: 1;
    }
}

    </style>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const navLinks = document.querySelectorAll(".hero-nav a");
            const sections = document.querySelectorAll(".section");

            // Handle navigation click
            navLinks.forEach((link) => {
                link.addEventListener("click", (e) => {
                    e.preventDefault();
                    const targetSectionId = link.getAttribute("href").substring(1);
                    sections.forEach((section) => {
                        section.style.display = section.id === targetSectionId ? "block" : "none";
                    });
                });
            });

            // Show the first section by default
            sections.forEach((section, index) => {
                section.style.display = index === 0 ? "block" : "none";
            });

            // Confirm logout
            const logoutYes = document.getElementById("logout-yes");
            const logoutNo = document.getElementById("logout-no");

            logoutYes.addEventListener("click", () => {
                window.location.href = "admin.php?confirm=yes";
            });

            logoutNo.addEventListener("click", () => {
                sections.forEach((section) => {
                    section.style.display = section.id === "users-section" ? "block" : "none";
                });
            });
        });


    </script>
</head>
<body>
<nav class="hero-nav">
    <img class="logo" src="../images/logoA.png" alt="logo" >
    <a href="#users-section">Users</a>
    <a href="#futsals-section">Futsals</a>
    <a href="#bookings-section">Bookings</a>
    <a href="#logs-section">Logs</a>
    <a href="#logout-section">Logout</a>

</nav>


<div id="users-section" class="section">
    <h1 class="head1">Manage Users</h1>
    <!-- Filter Users by Role -->
    <form action="admin.php" method="get" class="filter-form">
        <label for="role">Filter by Role:</label>
        <select name="role" id="role">
            <option value="">All</option>
            <option value="customer" <?= $roleFilter === 'customer' ? 'selected' : '' ?>>Customer</option>
            <option value="staff" <?= $roleFilter === 'staff' ? 'selected' : '' ?>>Vender</option>
            <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
        <button type="submit" class="btn">Filter</button>
    </form>

    <div class="create-staff-toggle">
        <button id="toggle-staff-form" class="btn left-aligned">Add Futsal Vender</button>
    </div>

    <!-- Create New Staff User -->
    <div id="create-staff-form" class="create-staff-form" style="display: none;">
        <h2>Create New Futsal Vender</h2>
        <form action="admin.php" method="post">
            <div class="form-row">
                <label for="name">Name:</label>
                <input type="text" name="name" id="name" required>
            </div>
            <div class="form-row">
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="form-row">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-row">
                <label for="phone_number">Phone Number:</label>
                <input type="text" name="phone_number" id="phone_number" required>
            </div>
            <div class="form-row">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit" name="create_staff" class="btn">Create Futsal Vender</button>
        </form>
    </div>

    <div class="search-container">
    <input type="text" id="user-search" placeholder="Search users by name, username, email, or role">
</div>

<div class="user-table">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>Role</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="user-table-body">
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['id']) ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['phone_number']) ?></td>
                    <td><?= htmlspecialchars(($user['role'] === 'status' || $user['role'] === 'staff') ? 'Futsal Vender' : ucfirst($user['role'])) ?></td>
                    <td><?= htmlspecialchars($user['created_at']) ?></td>
                    <td>
                        <form action="admin.php" method="post" style="display: inline;">
                            <input type="hidden" name="delete_user_id" value="<?= htmlspecialchars($user['id']) ?>">
                            <button type="submit" class="btn delete-btn" onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('user-search');
        const tableBody = document.getElementById('user-table-body');

        if (searchInput && tableBody) {
            searchInput.addEventListener('input', () => {
                const searchQuery = searchInput.value.toLowerCase();
                const rows = tableBody.querySelectorAll('tr');

                rows.forEach(row => {
                    const name = row.cells[1].textContent.toLowerCase();
                    const username = row.cells[2].textContent.toLowerCase();
                    const email = row.cells[3].textContent.toLowerCase();
                    const role = row.cells[5].textContent.toLowerCase();

                    if (
                        name.includes(searchQuery) ||
                        username.includes(searchQuery) ||
                        email.includes(searchQuery) ||
                        role.includes(searchQuery)
                    ) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        } else {
            console.error('Search input or table body not found.');
        }
    });
</script>




                <script>
    document.addEventListener("DOMContentLoaded", () => {
        const toggleButton = document.getElementById("toggle-staff-form");
        const staffForm = document.getElementById("create-staff-form");

        toggleButton.addEventListener("click", () => {
            // Toggle visibility of the staff creation form
            if (staffForm.style.display === "none" || staffForm.style.display === "") {
                staffForm.style.display = "block";
            } else {
                staffForm.style.display = "none";
            }
        });
    });
</script>
            </div>
    </div>


    <div id="futsals-section" class="section">
    <h1 class="head1">Available Futsals</h1>
    <div class="search-container">
        <input type="text" id="futsal-search" placeholder="Search futsals by ID, Name, Owner, Location, or Price/Hour">
    </div>

    <div class="futsals-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Owner</th>
                    <th>Location</th>
                    <th>Price/Hour</th>
                    <th>Phone</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($futsals as $futsal): ?>
                    <tr>
                        <td><?= htmlspecialchars($futsal['id']) ?></td>
                        <td><?= htmlspecialchars($futsal['name']) ?></td>
                        <td><?= htmlspecialchars($futsal['owner_name']) ?></td>
                        <td><?= htmlspecialchars($futsal['location']) ?></td>
                        <td>Rs. <?= htmlspecialchars(number_format($futsal['price_per_hour'], 2)) ?></td>
                        <td><?= htmlspecialchars($futsal['description']) ?></td>
                        <td><?= htmlspecialchars($futsal['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('futsal-search');
        const futsalTableBody = document.querySelector('#futsals-section .futsals-table tbody');

        if (searchInput && futsalTableBody) {
            searchInput.addEventListener('input', () => {
                const searchQuery = searchInput.value.toLowerCase();
                const rows = futsalTableBody.querySelectorAll('tr');

                rows.forEach(row => {
                    const id = row.cells[0].textContent.toLowerCase();
                    const name = row.cells[1].textContent.toLowerCase();
                    const owner = row.cells[2].textContent.toLowerCase();
                    const location = row.cells[3].textContent.toLowerCase();
                    const price = row.cells[4].textContent.toLowerCase();

                    if (
                        id.includes(searchQuery) ||
                        name.includes(searchQuery) ||
                        owner.includes(searchQuery) ||
                        location.includes(searchQuery) ||
                        price.includes(searchQuery)
                    ) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        } else {
            console.error('Futsal search input or table body not found.');
        }
    });
</script>

</div>



<div id="bookings-section" class="section">
    <h1 class="head1">All Bookings</h1>
    <div class="search-container">
    <input type="text" id="booking-search" placeholder="Search bookings by Booking ID, Futsal, Customer, Status, or Payment Status">
</div>

    <div class="bookings-table">
        <table>
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>Futsal</th>
                    <th>Customer</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Status</th>
                    <th>Payment Status</th>
                    <th>Total Cost</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
    <td data-label="Booking ID"><?= htmlspecialchars($booking['booking_id']) ?></td>
    <td data-label="Futsal"><?= htmlspecialchars($booking['futsal_name']) ?></td>
    <td data-label="Customer"><?= htmlspecialchars($booking['customer_name']) ?></td>
    <td data-label="Start Time"><?= htmlspecialchars($booking['start_time']) ?></td>
    <td data-label="End Time"><?= htmlspecialchars($booking['end_time']) ?></td>
    <td data-label="Status" class="status-<?= htmlspecialchars(strtolower($booking['status'])) ?>">
        <?= htmlspecialchars($booking['status']) ?>
    </td>
    <td data-label="Payment Status" class="payment-<?= htmlspecialchars(strtolower($booking['payment_status'])) ?>">
        <?= htmlspecialchars($booking['payment_status']) ?>
    </td>
    <td data-label="Total Cost">$<?= htmlspecialchars(number_format($booking['total_cost'], 2)) ?></td>
</tr>

                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('booking-search');
        const bookingTableBody = document.querySelector('#bookings-section .bookings-table tbody');

        if (searchInput && bookingTableBody) {
            searchInput.addEventListener('input', () => {
                const searchQuery = searchInput.value.toLowerCase();
                const rows = bookingTableBody.querySelectorAll('tr');

                rows.forEach(row => {
                    const bookingId = row.cells[0].textContent.toLowerCase();
                    const futsal = row.cells[1].textContent.toLowerCase();
                    const customer = row.cells[2].textContent.toLowerCase();
                    const status = row.cells[5].textContent.toLowerCase();
                    const paymentStatus = row.cells[6].textContent.toLowerCase();

                    if (
                        bookingId.includes(searchQuery) ||
                        futsal.includes(searchQuery) ||
                        customer.includes(searchQuery) ||
                        status.includes(searchQuery) ||
                        paymentStatus.includes(searchQuery)
                    ) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        } else {
            console.error('Booking search input or table body not found.');
        }
    });
</script>

</div>
<script>
    document.addEventListener("DOMContentLoaded", () => {
    const navLinks = document.querySelectorAll(".hero-nav a");
    const sections = document.querySelectorAll(".section");

    navLinks.forEach((link) => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            const targetSectionId = link.getAttribute("href").substring(1);
            sections.forEach((section) => {
                section.style.display = section.id === targetSectionId ? "block" : "none";
            });
        });
    });

    sections.forEach((section, index) => {
        section.style.display = index === 0 ? "block" : "none";
    });
});

</script>


    <div id="settings-section" class="section">
        <h1 class="head1">Settings</h1>
        <p>Configuration options for the application. (Under construction)</p>
    </div>

    <div id="analytics-section" class="section">
        <h1 class="head1">Analytics</h1>
        <p>View website usage analytics. (Under construction)</p>
    </div>

    <div id="logs-section" class="section">
    <h1 class="head1">PayPal&copy; Logs</h1>

    <div class="search-container">
        <input type="text" id="log-search" placeholder="Search logs by keywords">
    </div>

    <div class="logs-table">
        <table>
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Log Message</th>
                </tr>
            </thead>
            <tbody id="logs-table-body">
                <?php
                // Path to the log file
                $logFile = '../paypal.log';
                if (file_exists($logFile)) {
                    // Read the file line by line
                    $logs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    foreach ($logs as $line) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars(substr($line, 0, 19)) . '</td>';
                        echo '<td>' . htmlspecialchars(substr($line, 20)) . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="2">Log file not found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('log-search');
        const logTableBody = document.getElementById('logs-table-body');

        if (searchInput && logTableBody) {
            searchInput.addEventListener('input', () => {
                const searchQuery = searchInput.value.toLowerCase();
                const rows = logTableBody.querySelectorAll('tr');

                rows.forEach(row => {
                    const timestamp = row.cells[0].textContent.toLowerCase();
                    const logMessage = row.cells[1].textContent.toLowerCase();

                    if (timestamp.includes(searchQuery) || logMessage.includes(searchQuery)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        } else {
            console.error('Log search input or table body not found.');
        }
    });
</script>

</div>


    <!-- Logout Section -->
    <div id="logout-section" class="section">
        <h1 class="head1">Logout</h1>
        <p>Are you sure you want to log out?</p>
        <button id="logout-yes" class="btn">Yes</button>
        <button id="logout-no" class="btn">No</button>
    </div>

    <footer class="footer">
    <p >
        &copy; 2025 PlaySphere | Designed by team
        <a href="mailto:co.dex11@hotmail.com?subject=Website%20Inquiry&body=I%20would%20like%20to%20learn%20more%20about..." target="_blank">CodeX11</a>
    </p>
    </footer>
</body>
</html>
