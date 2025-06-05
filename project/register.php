<?php
session_start();
require_once 'config/db.php';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? '');
    
    // Validate form data
    if (empty($fullName) || empty($username) || empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        try {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                $error = "Username already exists";
            } else {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->rowCount() > 0) {
                    $error = "Email already exists";
                } else {
                    // Begin transaction
                    $pdo->beginTransaction();
                    
                    // Hash password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Face image processing
                    $faceImage = null;
                    $faceEncodingJson = null;

                    if (isset($_POST['face_data']) && !empty($_POST['face_data'])) {
                        $faceEncodingJson = $_POST['face_data'];
                        // Decode the JSON string received from the frontend
                        $faceEncodingArray = json_decode($faceEncodingJson, true);

                        // Check if JSON decoding was successful and if it's a valid encoding (an array of numbers)
                        if (json_last_error() !== JSON_ERROR_NONE || !is_array($faceEncodingArray) || empty($faceEncodingArray)) {
                            $error = "Invalid face encoding data received.";
                            $pdo->rollBack();
                            goto end_processing;
                        }
                        
                        // You might want to re-encode to a consistent format if storing as TEXT/LONGTEXT
                        // $faceEncodingJson = json_encode($faceEncodingArray); 
                        // Or store as is if your column is BLOB/LONGTEXT and can handle JSON strings directly
                    } else if ($role === 'voter') {
                        $error = "Face capture and processing failed or was not completed.";
                        goto end_processing;
                    }
                    
                    // Insert user
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password, role, face_encoding) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$fullName, $username, $email, $hashedPassword, $role, $faceEncodingJson]);
                    $userId = $pdo->lastInsertId();
                    
                    // Insert role-specific data
                    if ($role === 'voter') {
                        $weredaName = trim($_POST['wereda'] ?? '');
                        if (empty($weredaName)) {
                            $error = "Wereda is required for voters";
                            $pdo->rollBack();
                            goto end_processing;
                        }

                        // Get the wereda_id based on the wereda name
                        $stmt = $pdo->prepare("SELECT id FROM weredas WHERE name = ?");
                        $stmt->execute([$weredaName]);
                        $weredaData = $stmt->fetch(PDO::FETCH_ASSOC);

                        if (!$weredaData) {
                            $error = "Invalid Wereda selected.";
                            $pdo->rollBack();
                            goto end_processing;
                        }

                        $weredaId = $weredaData['id'];

                        // Insert into voters table using wereda_id
                        $stmt = $pdo->prepare("INSERT INTO voters (user_id, wereda_id) VALUES (?, ?)");
                        $stmt->execute([$userId, $weredaId]);
                    } else if ($role === 'candidate') {
                        $candidateType = trim($_POST['candidate_type'] ?? '');
                        if (empty($candidateType)) {
                            $error = "Candidate type is required";
                            $pdo->rollBack();
                            goto end_processing;
                        }
                        
                        $stmt = $pdo->prepare("INSERT INTO candidates (user_id, candidate_type) VALUES (?, ?)");
                        $stmt->execute([$userId, $candidateType]);
                    }
                    
                    // Commit transaction
                    $pdo->commit();
                    $success = "Registration successful! You can now log in.";
                }
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Registration failed: " . $e->getMessage();
        }
    }
    
    end_processing:
}

// Get weredas for dropdown
$weredas = [];
try {
    $stmt = $pdo->query("SELECT name FROM weredas ORDER BY name");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $weredas[] = $row['name'];
    }
} catch (PDOException $e) {
    $error = "Failed to load weredas: " . $e->getMessage();
}

// Get candidate types for dropdown
$candidateTypes = [];
try {
    $stmt = $pdo->query("SELECT name FROM candidate_types ORDER BY name");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $candidateTypes[] = $row['name'];
    }
} catch (PDOException $e) {
    $error = "Failed to load candidate types: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Election System</title>
    <link rel="stylesheet" href="css/styles.css">
    <!-- Face API -->
    <script defer src="js/face-api.min.js"></script>
    <script defer src="js/register.js"></script>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Register</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert success">
                    <?php echo $success; ?>
                    <p><a href="login.php">Go to Login</a></p>
                </div>
            <?php else: ?>
            
            <form id="registrationForm" method="POST" action="register.php">
                <div class="form-group">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label for="username">Username <span class="required">*</span></label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="role">Register as <span class="required">*</span></label>
                    <select id="role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="voter">Voter</option>
                        <option value="candidate">Candidate</option>
                    </select>
                </div>
                
                <!-- Voter-specific fields -->
                <div id="voterFields" class="role-fields" style="display: none;">
                    <div class="form-group">
                        <label for="wereda">Wereda <span class="required">*</span></label>
                        <select id="wereda" name="wereda">
                            <option value="">Select Wereda</option>
                            <?php foreach ($weredas as $wereda): ?>
                                <option value="<?php echo htmlspecialchars($wereda); ?>"><?php echo htmlspecialchars($wereda); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Face Registration <span class="required">*</span></label>
                        <div class="face-capture-container">
                            <video id="faceVideo" width="400" height="300" autoplay muted></video>
                            <canvas id="faceCanvas" width="400" height="300" style="display: none;"></canvas>
                            <div id="faceFeedback">Please allow camera access</div>
                            <button type="button" id="captureBtn" class="btn primary">Capture Face</button>
                        </div>
                        <input type="hidden" id="face_data" name="face_data">
                    </div>
                </div>
                
                <!-- Candidate-specific fields -->
                <div id="candidateFields" class="role-fields" style="display: none;">
                    <div class="form-group">
                        <label for="candidate_type">Candidate Type <span class="required">*</span></label>
                        <select id="candidate_type" name="candidate_type">
                            <option value="">Select Type</option>
                            <?php foreach ($candidateTypes as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn primary">Register</button>
                    <a href="login.php" class="btn link">Already have an account? Login</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>