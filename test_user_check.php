<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute(['john.doe.test@example.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ User found: " . $user['first_name'] . " " . $user['last_name'] . " (" . $user['email'] . ")\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Phone: " . $user['phone'] . "\n";
        echo "Created: " . $user['created_at'] . "\n";
    } else {
        echo "❌ User not found. Let me test if registration works...\n";
        
        // Test manual registration
        $email = 'testuser@example.com';
        $password = password_hash('TestPass123!', PASSWORD_DEFAULT);
        $first_name = 'Test';
        $last_name = 'User';
        $phone = '+1234567890';
        $username = 'testuser';
        
        $query = "INSERT INTO users (username, email, password, first_name, last_name, phone, role, is_active) 
                  VALUES (?, ?, ?, ?, ?, ?, 'user', TRUE)";
        $stmt = $conn->prepare($query);
        
        if ($stmt->execute([$username, $email, $password, $first_name, $last_name, $phone])) {
            echo "✅ Registration test successful!\n";
            echo "Test user created: $first_name $last_name ($email)\n";
        } else {
            echo "❌ Registration test failed.\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>