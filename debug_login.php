<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    $email = 'admin@cardetailing.com';
    $query = "SELECT id, username, email, password, is_active FROM users WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "User found:\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Password hash: " . $user['password'] . "\n";
        echo "Is active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n";

        // Test password
        $test_password = 'admin123';
        if (password_verify($test_password, $user['password'])) {
            echo "Password 'admin123' matches the hash.\n";
        } else {
            echo "Password 'admin123' does NOT match the hash.\n";
        }
    } else {
        echo "User with email 'admin@cardetailing.com' not found.\n";
    }
} else {
    echo "Database connection failed.\n";
}
?>
