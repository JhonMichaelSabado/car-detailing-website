<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/database_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $carDB = new CarDetailingDB($db);
    
    $booking_id = $_POST['booking_id'] ?? null;
    $payment_method = $_POST['payment_method'] ?? null;
    $payment_amount = $_POST['payment_amount'] ?? 0;
    $payment_type = $_POST['payment_type'] ?? 'partial';
    $user_id = $_SESSION['user_id'];
    
    if (!$booking_id || !$payment_method || !$payment_amount) {
        echo json_encode(['success' => false, 'message' => 'Missing required payment information']);
        exit();
    }
    
    // Process payment based on method
    $payment_status = 'pending';
    $payment_reference = 'PAY-' . $booking_id . '-' . time();
    
    // Simulate different payment methods
    switch ($payment_method) {
        case 'gcash':
            $payment_status = 'completed'; // For demo, auto-complete GCash
            break;
        case 'bank':
            $payment_status = 'pending'; // Bank transfers need verification
            break;
        case 'cash':
            $payment_status = 'pending'; // Cash on arrival
            break;
    }
    
    // Create payment record
    $payment_result = $carDB->createPayment($booking_id, $user_id, $payment_amount, $payment_method, $payment_reference);
    
    if ($payment_result['success']) {
        // Update booking status based on payment type
        $new_booking_status = 'pending'; // Default to pending admin approval
        
        if ($payment_type === 'full' && $payment_status === 'completed') {
            $new_booking_status = 'confirmed'; // Auto-confirm full paid bookings
        }
        
        $carDB->updateBookingStatus($booking_id, $new_booking_status, 'Payment received: ₱' . number_format($payment_amount, 2) . ' via ' . strtoupper($payment_method));
        
        // Log activity
        $carDB->logActivity($user_id, null, 'payment_made', "Payment of ₱{$payment_amount} made for booking #{$booking_id}", 'payments', $payment_result['payment_id']);
        
        // Create notification for admin
        $carDB->createNotification(
            null, // admin notification
            'New payment received',
            "Payment of ₱{$payment_amount} received for booking #{$booking_id} via {$payment_method}",
            'payment'
        );
        
        // Clear payment session data
        unset($_SESSION['pending_payment']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully',
            'payment_id' => $payment_result['payment_id'],
            'payment_status' => $payment_status,
            'booking_status' => $new_booking_status
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to process payment']);
    }
    
} catch (Exception $e) {
    error_log("Payment processing error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment processing failed']);
}
?>