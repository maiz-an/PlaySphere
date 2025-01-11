<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "PlaySphere";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $name = trim($_POST['name']);
    $user = trim($_POST['username']);
    $email = trim($_POST['email']);
    $pass = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $phone_number = trim($_POST['phone_number']);
    $role = "customer";

    // Optional: Remove this line if "nic" is not used in your form
    $nic = isset($_POST['nic']) ? trim($_POST['nic']) : null;

    // Check if username already exists
    $checkUsernameSql = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $checkUsernameSql->bind_param("s", $user);
    $checkUsernameSql->execute();
    $usernameResult = $checkUsernameSql->get_result();

    // If username exists
    if ($usernameResult->num_rows > 0) {
        echo "<script>
            alert('Username already exists. Please choose another.');
            history.back();
        </script>";
        exit();
    }
    

    // Check if email already exists
    $checkEmailSql = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $checkEmailSql->bind_param("s", $email);
    $checkEmailSql->execute();
    $emailResult = $checkEmailSql->get_result();

    if ($emailResult->num_rows > 0) {
        echo "<script>
            alert('Email already exists. Please use another.');
            history.back();
        </script>";
        exit();
    }

    // If username and email do not exist, insert new user into the database using a prepared statement
    $sql = $conn->prepare("INSERT INTO users (name, username, email, password_hash, nic, phone_number, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $sql->bind_param("sssssss", $name, $user, $email, $pass, $nic, $phone_number, $role);

    // If successful
    if ($sql->execute()) {
        echo "<script>alert('Registration successful! You can now log in.'); window.location.href = 'index.php#login-section';</script>";
        exit();
    } else {
        echo "<script>
            alert('Error: " . $sql->error . "');
            history.back();
        </script>";
        exit();
    }

    $checkUsernameSql->close();
    $checkEmailSql->close();
    $sql->close();
}

$conn->close();
