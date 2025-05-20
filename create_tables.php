<?php
$host = 'localhost';
$dbname = 'mypsydz';
$username = 'root';
$password = '';

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ✅ Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    // Shared structure for all user tables
    $commonUserFields = "
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL UNIQUE,
        email VARCHAR(100),
        password VARCHAR(255) NOT NULL,
        gender ENUM('male', 'female') NOT NULL,
        dob DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ";

    // ✅ Create role-specific user tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS patients (
        $commonUserFields
    ) ENGINE=InnoDB;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS medical_advisors (
        $commonUserFields
    ) ENGINE=InnoDB;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS educational_psychologists (
        $commonUserFields
    ) ENGINE=InnoDB;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS speech_therapists (
        $commonUserFields
    ) ENGINE=InnoDB;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS social_workers (
        $commonUserFields
    ) ENGINE=InnoDB;");

    // ✅ Messages table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            content TEXT NOT NULL,
            is_used BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
        ) ENGINE=InnoDB;
    ");

    // ✅ Assignments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS assignments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            message_id INT NOT NULL,
            assigned_by INT NOT NULL,
            assigned_to_role VARCHAR(50) NOT NULL,
            assigned_to_id INT NOT NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_by) REFERENCES medical_advisors(id) ON DELETE CASCADE
        ) ENGINE=InnoDB;
    ");

    // ✅ Appointments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            professional_id INT NOT NULL,
            professional_type VARCHAR(50) NOT NULL,
            appointment_date DATETIME NOT NULL,
            status VARCHAR(20) DEFAULT 'scheduled',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
        ) ENGINE=InnoDB;
    ");

    echo "✅ Database '$dbname' and all tables created successfully with gender, date of birth, and appointments table.";
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
