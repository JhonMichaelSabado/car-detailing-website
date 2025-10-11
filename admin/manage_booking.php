<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/database_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $carDB = new CarDetailingDB($db);
    
    $action = $_POST['action'] ?? null;
    $booking_id = $_POST['booking_id'] ?? null;
    $admin_id = $_SESSION['user_id'];
    
    if (!$action || !$booking_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }
    
    switch ($action) {
        case 'confirm':
            $result = $carDB->updateBookingStatus($booking_id, 'confirmed', 'Booking confirmed by admin', $admin_id);
            break;
            
        case 'decline':
            $reason = $_POST['reason'] ?? 'Booking declined by admin';
            $result = $carDB->updateBookingStatus($booking_id, 'declined', $reason, $admin_id);
            break;
            
        case 'start':
            $result = $carDB->updateBookingStatus($booking_id, 'in_progress', 'Service started', $admin_id);
            break;
            
        case 'complete':
            $result = $carDB->updateBookingStatus($booking_id, 'completed', 'Service completed', $admin_id);
            break;
            
        case 'cancel':
            $reason = $_POST['reason'] ?? 'Booking cancelled by admin';
            $result = $carDB->updateBookingStatus($booking_id, 'cancelled', $reason, $admin_id);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>