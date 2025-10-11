<?php
// Test form submission for booking wizard
session_start();

// Set test session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'testuser';
$_SESSION['role'] = 'user';

echo "<h1>🧪 Form Submission Test</h1>";

// Simulate a form submission
$_POST = [
    'action' => 'create_booking',
    'service_id' => '1',
    'vehicle_size' => 'medium',
    'booking_date' => date('Y-m-d', strtotime('+1 day')),
    'booking_time' => '09:00:00',
    'service_address' => 'Test Address, Manila, Philippines',
    'vehicle_year' => '2020',
    'vehicle_make' => 'Toyota',
    'vehicle_model' => 'Camry',
    'vehicle_body_type' => 'Sedan',
    'vehicle_color' => 'White',
    'license_plate' => 'ABC-1234',
    'special_instructions' => 'Test booking from wizard',
    'payment_method' => 'cash',
    'total_amount' => '500.00'
];

echo "<h2>Test Form Data:</h2>";
foreach ($_POST as $key => $value) {
    echo "<strong>$key:</strong> $value<br>";
}

echo "<h2>Processing...</h2>";

try {
    // Include the booking wizard file to test processing
    ob_start();
    include 'create_booking_wizard.php';
    $output = ob_get_clean();
    
    // Check if there were any errors
    if (isset($_SESSION['booking_success'])) {
        echo "<h2 style='color: green;'>✅ SUCCESS!</h2>";
        echo "<p>Booking was created successfully.</p>";
        print_r($_SESSION['booking_success']);
        unset($_SESSION['booking_success']); // Clean up
    } else {
        echo "<h2 style='color: orange;'>⚠️ Check Output</h2>";
        echo "<p>No success session found. Check output:</p>";
        echo "<div style='background: #f0f0f0; padding: 10px; border-radius: 5px; max-height: 400px; overflow-y: auto;'>";
        echo htmlspecialchars(substr($output, 0, 2000));
        if (strlen($output) > 2000) echo "...(truncated)";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ ERROR</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<br><a href='test_booking_wizard.php'>← Back to Functionality Test</a>";
echo " | <a href='create_booking_wizard.php'>Test Wizard Manually →</a>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h1 { color: #333; }
h2 { color: #666; }
</style>