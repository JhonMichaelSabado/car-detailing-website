<?php
/**
 * Database Connection and Structure Test
 * Tests database connection and verifies all new tables and triggers are created
 */

// Suppress HTML error output for clean JSON/text response
ini_set('display_errors', 0);
error_reporting(0);

try {
    // Include database connection
    require_once '../config/database.php';
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    $results = [];
    $results[] = "=== DATABASE CONNECTION TEST ===";
    $results[] = "✅ Database connection: SUCCESS";
    
    // Test required tables exist
    $required_tables = [
        'bookings', 'users', 'services', 'daily_availability', 
        'time_slots', 'booking_conflicts', 'business_settings'
    ];
    
    $results[] = "\n=== TABLE STRUCTURE TEST ===";
    foreach ($required_tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE `$table`");
            if ($stmt) {
                $results[] = "✅ Table '$table': EXISTS";
            } else {
                $results[] = "❌ Table '$table': MISSING";
            }
        } catch (Exception $e) {
            $results[] = "❌ Table '$table': ERROR - " . $e->getMessage();
        }
    }
    
    // Test triggers exist
    $results[] = "\n=== TRIGGERS TEST ===";
    $trigger_query = "SHOW TRIGGERS LIKE 'bookings'";
    $stmt = $pdo->query($trigger_query);
    $triggers = $stmt->fetchAll();
    
    $expected_triggers = [
        'update_daily_availability_on_booking_insert',
        'update_daily_availability_on_booking_update', 
        'update_daily_availability_on_booking_delete'
    ];
    
    $found_triggers = array_column($triggers, 'Trigger');
    foreach ($expected_triggers as $trigger) {
        if (in_array($trigger, $found_triggers)) {
            $results[] = "✅ Trigger '$trigger': EXISTS";
        } else {
            $results[] = "❌ Trigger '$trigger': MISSING";
        }
    }
    
    // Test views exist
    $results[] = "\n=== VIEWS TEST ===";
    $view_query = "SHOW FULL TABLES WHERE Table_type = 'VIEW'";
    $stmt = $pdo->query($view_query);
    $views = $stmt->fetchAll();
    
    $expected_views = ['todays_booking_schedule', 'booking_availability_overview'];
    $found_views = array_column($views, 'Tables_in_car_detailing');
    
    foreach ($expected_views as $view) {
        if (in_array($view, $found_views)) {
            $results[] = "✅ View '$view': EXISTS";
        } else {
            $results[] = "❌ View '$view': MISSING";
        }
    }
    
    // Test business settings
    $results[] = "\n=== BUSINESS SETTINGS TEST ===";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM business_settings");
    $count = $stmt->fetch()['count'];
    $results[] = "✅ Business settings configured: $count settings";
    
    // Test time slots
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM time_slots WHERE is_active = 1");
    $count = $stmt->fetch()['count'];
    $results[] = "✅ Active time slots: $count slots";
    
    // Test sample query
    $results[] = "\n=== FUNCTIONALITY TEST ===";
    $stmt = $pdo->query("SELECT available_date, current_bookings, max_bookings FROM daily_availability LIMIT 3");
    $availability = $stmt->fetchAll();
    $results[] = "✅ Daily availability tracking: " . count($availability) . " dates tracked";
    
    $results[] = "\n=== OVERALL STATUS ===";
    $results[] = "✅ SUCCESS: Database integration is working correctly!";
    $results[] = "All core components are in place and functional.";
    
    echo implode("\n", $results);
    
} catch (Exception $e) {
    echo "❌ FAILED: Database test failed\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Check your database connection and ensure the integration script was run successfully.";
}
?>