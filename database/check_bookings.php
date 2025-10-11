<?php
require_once '../config/database.php';
require_once '../includes/database_functions.php';

$database = new Database();
$db = $database->getConnection();
$carDB = new CarDetailingDB($db);

$pending = $carDB->getAllBookings('pending', 10);
echo "Pending bookings: " . count($pending) . "\n";

if (!empty($pending)) {
    foreach ($pending as $booking) {
        echo "ID: " . $booking['id'] . ", Customer: " . $booking['customer_name'] . ", Service: " . $booking['service_name'] . ", Status: " . $booking['status'] . "\n";
    }
} else {
    echo "No pending bookings found.\n";
}
?>