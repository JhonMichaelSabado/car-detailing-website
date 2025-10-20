<?php
// get_booking_details.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/database_functions.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing booking ID']);
    exit();
}
$booking_id = intval($_GET['id']);

$database = new Database();
$db = $database->getConnection();
$carDB = new CarDetailingDB($db);

$booking = $carDB->getBookingDetails($booking_id);
if (!$booking) {
    http_response_code(404);
    echo json_encode(['error' => 'Booking not found']);
    exit();
}

// Optionally fetch payment info, add-ons, etc.
$payment = $carDB->getPaymentForBooking($booking_id);
$addons = $carDB->getAddonsForBooking($booking_id);

$booking['payment'] = $payment;
$booking['addons'] = $addons;

header('Content-Type: application/json');
echo json_encode($booking);
