<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'election_system');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Create necessary tables if they don't exist
function setupDatabase($pdo) {
    // Users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('voter', 'candidate') NOT NULL,
        face_image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Voters table
    $pdo->exec("CREATE TABLE IF NOT EXISTS voters (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        wereda VARCHAR(100) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Candidates table
    $pdo->exec("CREATE TABLE IF NOT EXISTS candidates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        candidate_type VARCHAR(100) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Wereda reference table
    $pdo->exec("CREATE TABLE IF NOT EXISTS weredas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE
    )");
    
    // Populate weredas if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM weredas");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $weredas = [
            'Addis Ketema', 'Akaki Kality', 'Arada', 'Bole', 'Gullele',
            'Kirkos', 'Kolfe Keranio', 'Lideta', 'Nifas Silk-Lafto', 'Yeka'
        ];
        
        $stmt = $pdo->prepare("INSERT INTO weredas (name) VALUES (?)");
        foreach ($weredas as $wereda) {
            $stmt->execute([$wereda]);
        }
    }
    
    // Candidate types reference table
    $pdo->exec("CREATE TABLE IF NOT EXISTS candidate_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE
    )");
    
    // Populate candidate types if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM candidate_types");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $types = ['Federal', 'Regional', 'Local'];
        
        $stmt = $pdo->prepare("INSERT INTO candidate_types (name) VALUES (?)");
        foreach ($types as $type) {
            $stmt->execute([$type]);
        }
    }
}

// Run database setup
// setupDatabase($pdo); // Removed this line