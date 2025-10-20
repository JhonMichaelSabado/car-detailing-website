<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or missing booking ID']);
    exit();
}

$booking_id = (int)$_POST['booking_id'];
$user_id = $_SESSION['user_id'];

try {
    // Check if booking exists and belongs to user
    $stmt = $pdo->prepare("
        SELECT status, booking_reference 
        FROM bookings 
        WHERE booking_id = ? AND user_id = ?
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit();
    }
    
    // Check if booking can be cancelled
    if ($booking['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Only pending bookings can be cancelled']);
        exit();
    }
    
    // Cancel the booking
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET status = 'cancelled', 
            admin_action_date = NOW(),
            admin_notes = 'Cancelled by customer'
        WHERE booking_id = ? AND user_id = ?
    ");
    $result = $stmt->execute([$booking_id, $user_id]);
    
    if ($result) {
        // Create notification for admin
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, related_booking_id)
            VALUES (1, 'booking_cancelled', 'Booking Cancelled', ?, ?)
        ");
        $stmt->execute([
            "Customer cancelled booking #{$booking['booking_reference']}",
            $booking_id
        ]);
        
        // Log the activity
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (booking_id, action_type, action_description, user_id)
            VALUES (?, 'cancelled', 'Booking cancelled by customer', ?)
        ");
        $stmt->execute([$booking_id, $user_id]);
        
        echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to cancel booking']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>