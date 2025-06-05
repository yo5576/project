<?php
// Security functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Authentication functions
function check_login_attempts($username, $ip_address) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempts 
        FROM login_attempts 
        WHERE username = ? 
        AND ip_address = ? 
        AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        AND success = 0
    ");
    $stmt->execute([$username, $ip_address]);
    $result = $stmt->fetch();
    
    return $result['attempts'] < 5; // Allow max 5 failed attempts in 15 minutes
}

function log_login_attempt($username, $ip_address, $success) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO login_attempts (username, ip_address, success) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$username, $ip_address, $success]);
}

function update_last_login($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE users 
        SET last_login = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
}

// User management functions
function get_user_by_id($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT u.*, 
               CASE 
                   WHEN u.role = 'voter' THEN v.wereda_id 
                   WHEN u.role = 'candidate' THEN c.candidate_type_id 
                   ELSE NULL 
               END as role_specific_id
        FROM users u
        LEFT JOIN voters v ON u.id = v.user_id
        LEFT JOIN candidates c ON u.id = c.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function get_user_by_username($username) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT u.*, 
               CASE 
                   WHEN u.role = 'voter' THEN v.wereda_id 
                   WHEN u.role = 'candidate' THEN c.candidate_type_id 
                   ELSE NULL 
               END as role_specific_id
        FROM users u
        LEFT JOIN voters v ON u.id = v.user_id
        LEFT JOIN candidates c ON u.id = c.user_id
        WHERE u.username = ?
    ");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

// Role-specific functions
function get_wereda_name($wereda_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT name FROM weredas WHERE id = ?");
    $stmt->execute([$wereda_id]);
    $result = $stmt->fetch();
    return $result ? $result['name'] : null;
}

function get_candidate_type_name($type_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT name FROM candidate_types WHERE id = ?");
    $stmt->execute([$type_id]);
    $result = $stmt->fetch();
    return $result ? $result['name'] : null;
}

// File handling functions
function save_face_image($base64_image) {
    $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64_image));
    $filename = 'face_img/' . uniqid() . '.png';
    
    if (file_put_contents($filename, $image_data)) {
        return $filename;
    }
    return false;
}

function delete_face_image($filename) {
    if (file_exists($filename)) {
        return unlink($filename);
    }
    return false;
}

// Session management
function start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        session_start();
    }
}

// Error handling
function handle_error($message, $redirect = null) {
    $_SESSION['error'] = $message;
    if ($redirect) {
        header("Location: $redirect");
        exit;
    }
}

function handle_success($message, $redirect = null) {
    $_SESSION['success'] = $message;
    if ($redirect) {
        header("Location: $redirect");
        exit;
    }
} 