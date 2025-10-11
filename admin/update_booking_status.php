<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['booking_id']) || !isset($input['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing booking ID or status']);
    exit();
}

$booking_id = $input['booking_id'];
$status = $input['status'];

// Validate status
$valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Update booking status
    $sql = "UPDATE bookings SET status = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(1, $status);
    $stmt->bindParam(2, $booking_id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => "Booking status updated to $status successfully!",
            'booking_id' => $booking_id,
            'status' => $status
        ]);
    } else {
        throw new Exception("Failed to update booking status");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>