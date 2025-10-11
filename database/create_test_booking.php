<?php
require_once '../config/database.php';
require_once '../includes/database_functions.php';

$database = new Database();
$db = $database->getConnection();
$carDB = new CarDetailingDB($db);

// Create a test booking with correct parameters
$user_id = 2; // Assuming user ID 2 exists
$service_id = 1; // Basic car wash
$vehicle_size = 'medium';
$booking_date = '2025-10-10';
$vehicle_details = '2020 Honda Civic';
$special_requests = 'Test booking for admin confirmation demo';

$result = $carDB->createBooking($user_id, $service_id, $vehicle_size, $booking_date, $vehicle_details, $special_requests);

if ($result['success']) {
    echo "Test booking created successfully!\n";
    echo "Booking ID: " . $result['booking_id'] . "\n";
    echo "Status: pending (ready for admin confirmation)\n";
} else {
    echo "Failed to create test booking: " . $result['message'] . "\n";
}
?>