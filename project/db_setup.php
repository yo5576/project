<?php
require_once 'config/db.php';

function createDatabase() {
    global $pdo;
    
    try {
        // Create users table with improved security
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('voter', 'candidate', 'admin') NOT NULL,
            face_image VARCHAR(255),
            is_active BOOLEAN DEFAULT TRUE,
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_email (email),
            INDEX idx_role (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Create voters table with improved structure
        $pdo->exec("CREATE TABLE IF NOT EXISTS voters (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            wereda_id INT NOT NULL,
            voter_id VARCHAR(50) UNIQUE,
            registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (wereda_id) REFERENCES weredas(id) ON DELETE RESTRICT,
            INDEX idx_voter_id (voter_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Create candidates table with improved structure
        $pdo->exec("CREATE TABLE IF NOT EXISTS candidates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            candidate_type_id INT NOT NULL,
            party VARCHAR(100),
            manifesto TEXT,
            registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (candidate_type_id) REFERENCES candidate_types(id) ON DELETE RESTRICT,
            INDEX idx_party (party)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Create weredas table with improved structure
        $pdo->exec("CREATE TABLE IF NOT EXISTS weredas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            code VARCHAR(20) UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_code (code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Create candidate types table with improved structure
        $pdo->exec("CREATE TABLE IF NOT EXISTS candidate_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Create login attempts table for security
        $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            success BOOLEAN DEFAULT FALSE,
            INDEX idx_username_ip (username, ip_address)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Populate weredas
        $weredas = [
            ['Addis Ketema', 'AK'],
            ['Akaki Kality', 'AKK'],
            ['Arada', 'AR'],
            ['Bole', 'BL'],
            ['Gullele', 'GL'],
            ['Kirkos', 'KR'],
            ['Kolfe Keranio', 'KK'],
            ['Lideta', 'LD'],
            ['Nifas Silk-Lafto', 'NSL'],
            ['Yeka', 'YK']
        ];
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO weredas (name, code) VALUES (?, ?)");
        foreach ($weredas as $wereda) {
            $stmt->execute($wereda);
        }
        
        // Populate candidate types
        $types = [
            ['Federal', 'Federal level candidates'],
            ['Regional', 'Regional level candidates'],
            ['Local', 'Local level candidates']
        ];
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO candidate_types (name, description) VALUES (?, ?)");
        foreach ($types as $type) {
            $stmt->execute($type);
        }
        
        // Create admin user if not exists
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (full_name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['System Admin', 'admin', 'admin@election.com', $adminPassword, 'admin']);
        
        return true;
    } catch (PDOException $e) {
        die("Database setup failed: " . $e->getMessage());
    }
}

// Create necessary directories
$directories = ['face_img', 'models'];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: $dir<br>";
    }
}

// Run database setup
if (createDatabase()) {
    echo "Database setup completed successfully!<br>";
    echo "Default admin credentials:<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    echo "<br>Please change the admin password after first login!";
}