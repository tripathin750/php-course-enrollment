<?php
include 'db_connection.php';

// Fetch data from the registration form
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$email = $_POST['email'];
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$user_type = $_POST['user_type'];

// Check if passwords match
if ($password !== $confirm_password) {
    die("Passwords do not match");
}

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Prepare SQL statement
$stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, user_type) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $first_name, $last_name, $email, $hashed_password, $user_type);

// Execute the statement
if ($stmt->execute()) {
    // Redirect to login page upon successful registration
    header("Location: login.php");
    exit();
} else {
    echo "Error: " . $conn->error;
}

// Close statement and database connection
$stmt->close();
$conn->close();
?>

