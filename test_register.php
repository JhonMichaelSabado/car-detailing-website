<?php
// Test registration functionality
echo "Testing Registration...\n";

// Simulate POST data
$_POST = [
    'action' => 'register',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john.doe.test@example.com',
    'phone' => '+1234567890',
    'password' => 'TestPass123!',
    'confirm_password' => 'TestPass123!',
    'terms' => 'on'
];

// Start output buffering to capture redirects
ob_start();

// Include the authentication logic
require_once 'auth/authenticate.php';

// Get any output
$output = ob_get_clean();

echo "Registration test completed.\n";
echo "Output: " . $output . "\n";

// Check if user was created
try {
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['john.doe.test@example.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ SUCCESS: User created successfully!\n";
        echo "User ID: " . $user['id'] . "\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "First Name: " . $user['first_name'] . "\n";
        echo "Last Name: " . $user['last_name'] . "\n";
        echo "Phone: " . $user['phone'] . "\n";
        echo "Role: " . $user['role'] . "\n";
        echo "Created: " . $user['created_at'] . "\n";
    } else {
        echo "❌ FAILED: User not found in database\n";
    }
} catch (Exception $e) {
    echo "Error checking database: " . $e->getMessage() . "\n";
}
?>