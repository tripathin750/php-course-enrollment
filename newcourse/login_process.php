<?php
include 'db_connection.php';

// Fetch data from the login form
$email = $_POST['email'];
$password = $_POST['password'];
$user_type = $_POST['user_type'];

// Prepare SQL statement
$stmt = $conn->prepare("SELECT id, password, user_type FROM users WHERE email = ?");
$stmt->bind_param("s", $email);

// Execute the statement
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($id, $hashed_password, $db_user_type);
    $stmt->fetch();
    
    // Verify password
    if (password_verify($password, $hashed_password) && $db_user_type == $user_type) {
        // Password is correct, start a new session
        session_start();
        $_SESSION['loggedin'] = TRUE;
        $_SESSION['user_id'] = $id;
        $_SESSION['user_type'] = $user_type;

        // Redirect based on user type
        if ($user_type == 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: student_dashboard.php");
        }
        exit();
    } else {
        echo "Incorrect email or password";
    }
} else {
    echo "Incorrect email or password";
}

// Close statement and database connection
$stmt->close();
$conn->close();
?>
