<?php
session_start();

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election System</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <h2>Election System</h2>
                <p>Welcome to the secure election platform</p>
            </div>
            <div class="auth-options">
                <a href="login.php" class="auth-btn primary">Login with Face</a>
                <a href="register.php" class="auth-btn secondary">Register New Account</a>
            </div>
        </div>
    </div>
</body>
</html>