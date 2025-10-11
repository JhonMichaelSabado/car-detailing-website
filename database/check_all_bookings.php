<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check the bookings table directly
$stmt = $db->prepare("SELECT * FROM bookings ORDER BY id DESC LIMIT 5");
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "All bookings in database:\n";
foreach ($bookings as $booking) {
    echo "ID: " . $booking['id'] . ", User: " . $booking['user_id'] . ", Status: " . $booking['status'] . ", Date: " . $booking['created_at'] . "\n";
}
?>