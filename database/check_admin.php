<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->query('SELECT id, username, email, role FROM users WHERE role = "admin"');
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Admin users found:\n";
    foreach ($admins as $admin) {
        echo "ID: " . $admin['id'] . ", Username: " . $admin['username'] . ", Email: " . $admin['email'] . ", Role: " . $admin['role'] . "\n";
    }
    
    if (empty($admins)) {
        echo "No admin users found. Creating default admin...\n";
        
        $username = 'admin';
        $email = 'admin@riderevive.com';
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $role = 'admin';
        
        $stmt = $db->prepare('INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)');
        if ($stmt->execute([$username, $email, $password, $role])) {
            echo "Default admin created successfully!\n";
            echo "Username: admin\n";
            echo "Password: admin123\n";
        } else {
            echo "Failed to create admin user.\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>