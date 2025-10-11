<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "Bookings table structure:\n";
$stmt = $db->prepare('DESCRIBE bookings');
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $column) {
    echo $column['Field'] . ' - ' . $column['Type'] . "\n";
}

echo "\nBookings table content:\n";
$stmt = $db->prepare('SELECT * FROM bookings LIMIT 5');
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($bookings as $booking) {
    echo "Booking: ";
    print_r($booking);
}
?>