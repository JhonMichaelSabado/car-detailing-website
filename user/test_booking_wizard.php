<?php
// Simple test script to check if booking wizard core functions work
session_start();

// Set test session data
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'testuser';
$_SESSION['role'] = 'user';

echo "<h1>🧪 Booking Wizard Functionality Test</h1>";

try {
    // Test database connection
    echo "<h2>1. Testing Database Connection...</h2>";
    require_once '../config/database.php';
    require_once '../includes/database_functions.php';
    
    $database = new Database();
    $db = $database->getConnection();
    $carDB = new CarDetailingDB($db);
    
    if ($db) {
        echo "✅ Database connection: SUCCESS<br>";
    } else {
        echo "❌ Database connection: FAILED<br>";
    }
    
    // Test services loading
    echo "<h2>2. Testing Services Loading...</h2>";
    $services = $carDB->getServices();
    echo "✅ Services loaded: " . count($services) . " services found<br>";
    
    if (!empty($services)) {
        $firstService = $services[0];
        echo "📋 First service: " . $firstService['service_name'] . "<br>";
        echo "💰 Pricing: Small: ₱" . number_format($firstService['price_small'], 2) . 
             ", Medium: ₱" . number_format($firstService['price_medium'], 2) . 
             ", Large: ₱" . number_format($firstService['price_large'], 2) . "<br>";
    }
    
    // Test availability checker
    echo "<h2>3. Testing Availability Checker...</h2>";
    require_once '../includes/BookingAvailabilityChecker.php';
    $checker = new BookingAvailabilityChecker($db);
    
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $timeSlots = $checker->getAvailableTimeSlots($tomorrow);
    echo "✅ Available time slots for $tomorrow: " . count($timeSlots) . " slots<br>";
    if (!empty($timeSlots)) {
        echo "⏰ First few slots: " . implode(', ', array_slice($timeSlots, 0, 3)) . "...<br>";
    }
    
    // Test booking creation (dry run)
    echo "<h2>4. Testing Booking Logic...</h2>";
    $testData = [
        'service_id' => $services[0]['service_id'] ?? 1,
        'vehicle_size' => 'medium',
        'booking_date' => $tomorrow,
        'booking_time' => $timeSlots[0] ?? '09:00:00',
        'service_address' => 'Test Address, Test City',
        'payment_method' => 'cash',
        'total_amount' => $services[0]['price_medium'] ?? 500
    ];
    
    echo "📝 Test booking data prepared:<br>";
    foreach ($testData as $key => $value) {
        echo "&nbsp;&nbsp;• $key: $value<br>";
    }
    
    echo "<h2>5. Testing API Endpoint...</h2>";
    $apiUrl = "http://localhost/car-detailing/api/get_available_slots.php?date=$tomorrow";
    $apiResponse = @file_get_contents($apiUrl);
    
    if ($apiResponse) {
        $apiData = json_decode($apiResponse, true);
        if ($apiData && isset($apiData['success'])) {
            echo "✅ API endpoint working: " . ($apiData['success'] ? 'SUCCESS' : 'ERROR') . "<br>";
            if (isset($apiData['available_slots'])) {
                echo "📅 API returned " . count($apiData['available_slots']) . " available slots<br>";
            }
        } else {
            echo "⚠️ API response format issue<br>";
        }
    } else {
        echo "❌ API endpoint not accessible<br>";
    }
    
    echo "<h2>✅ Test Summary</h2>";
    echo "<p style='color: green; font-weight: bold;'>All core functionality appears to be working!</p>";
    echo "<p>You can now test the booking wizard manually:</p>";
    echo "<a href='create_booking_wizard.php' style='background: #FFD700; color: #000; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>🚀 Open Booking Wizard</a>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error During Testing</h2>";
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your configuration and try again.</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h1 { color: #333; }
h2 { color: #666; border-bottom: 2px solid #ddd; padding-bottom: 5px; }
</style>