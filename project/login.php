<?php
session_start();
require_once 'config/db.php';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the request is a JSON POST request
    $contentType = trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]);

    if ($contentType === 'application/json') {
        // Read the raw POST data
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);

        // Check if user_id is provided in the JSON data
        if (isset($data['user_id']) && !empty($data['user_id'])) {
            // User was identified by face recognition
            $userId = $data['user_id'];

            try {
                // Fetch user details based on the provided user ID
                $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    // Redirect to dashboard by sending a JSON response
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'success', 'redirect' => 'dashboard.php']);
                    exit;
                } else {
                    // User ID from face recognition did not match any user
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'error', 'message' => 'User not found based on recognition result.']);
                    exit;
                }
            } catch (PDOException $e) {
                // Database error during user lookup
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Database error during login: ' . $e->getMessage()]);
                exit;
            }
        } else {
             // JSON request received, but missing user_id
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid or missing user_id in JSON data.']);
            exit;
        }
    }
    // Add standard form login handling here if you also support password login on the same page
    // else if (isset($_POST['username']) && isset($_POST['password'])) {
    //     // Handle password login logic for form submission
    // }

    // If it's a POST request but not JSON, or other POST handling failed,
    // you might want to set an error and let the HTML page render.
    // For now, we'll just let it proceed to render the HTML, which will likely
    // show no specific error unless you set one here.
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face Login - Election System</title>
    <link rel="stylesheet" href="css/styles.css">
    <!-- Face API -->
    <script defer src="js/face-api.min.js"></script>
    <script defer src="js/login.js"></script>
</head>
<body>
    <div class="container">
        <div class="form-container face-login">
            <h2>Login with Face Recognition</h2>
            
            <div id="loginMessage" class="alert" style="display: none;"></div>
            
            <div class="face-capture-container">
                <video id="faceVideo" width="400" height="300" autoplay muted></video>
                <canvas id="faceCanvas" width="400" height="300"></canvas>
                <div id="faceFeedback">Please allow camera access for face recognition</div>
                
                <div class="loader" id="recognitionLoader" style="display: none;">
                    <div class="spinner"></div>
                    <p>Recognizing face...</p>
                </div>
            </div>
            
            <div class="form-actions">
                <button id="startRecognitionBtn" class="btn primary">Start Face Recognition</button>
                <a href="register.php" class="btn link">Don't have an account? Register</a>
            </div>
        </div>
    </div>
</body>
</html>