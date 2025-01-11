<?php

session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "PlaySphere";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = $_POST['usernameOrEmail'];
    $password = $_POST['password'];

    if (filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL)) {
        $sql = "SELECT * FROM users WHERE email = ?";
    } else {
        $sql = "SELECT * FROM users WHERE username = ?";
    }

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $usernameOrEmail);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password_hash'])) {
            // Set session variables
            $_SESSION['username'] = $user['username'];
            $_SESSION['customer_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['user_id'] = $user['id'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin/admin.php");
            } elseif ($user['role'] === 'customer') {
                header("Location: home.php");
            } else {
                header("Location: staff/staff.php");
            }
            exit();
        } else {
            echo "<script>alert('Invalid password.'); window.location.href = 'index.php#login-section';</script>";
        }
    } else {
        echo "<script>alert('No user found with that username or email.'); window.location.href = 'index.php#login-section';</script>";
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
