<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/database_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
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
    
    $user_id = $_SESSION['user_id'];
    $booking_id = $_POST['booking_id'] ?? null;
    $payment_method = $_POST['payment_method'] ?? null;
    $payment_amount = $_POST['payment_amount'] ?? 0;
    $payment_type = $_POST['payment_type'] ?? 'partial';
    $payment_reference = $_POST['payment_reference'] ?? null;
    
    // Validate inputs
    if (!$booking_id || !$payment_method || !$payment_amount) {
        echo json_encode(['success' => false, 'message' => 'Missing required payment information']);
        exit();
    }
    
    // Handle file upload for payment proof
    $upload_path = null;
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/payment_proofs/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
        $file_name = 'payment_' . $booking_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $file_name;
        
        // Validate file
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array(strtolower($file_extension), $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload an image.']);
            exit();
        }
        
        if ($_FILES['payment_proof']['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 5MB allowed.']);
            exit();
        }
        
        if (!move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_path)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload payment proof']);
            exit();
        }
        
        // Store relative path
        $upload_path = 'uploads/payment_proofs/' . $file_name;
    }
    
    // Determine payment status based on method
    $payment_status = 'pending';
    $booking_status = 'pending_verification';
    
    if ($payment_method === 'cash') {
        $payment_status = 'confirmed';
        $booking_status = 'confirmed';
    } else {
        $payment_status = 'pending_verification';
        $booking_status = 'pending_verification';
    }
    
    // Create enhanced payment record
    $payment_result = createEnhancedPayment($db, [
        'booking_id' => $booking_id,
        'user_id' => $user_id,
        'amount' => $payment_amount,
        'payment_method' => $payment_method,
        'payment_status' => $payment_status,
        'transaction_reference' => $payment_reference,
        'payment_proof_path' => $upload_path,
        'payment_type' => $payment_type
    ]);
    
    if ($payment_result['success']) {
        // Update booking status
        updateBookingStatus($db, $booking_id, $booking_status, $payment_status);
        
        // Log activity
        $carDB->logActivity($user_id, null, 'payment_submitted', 
            "Payment submitted for booking #{$booking_id}, Method: {$payment_method}, Amount: ₱{$payment_amount}", 
            'payments', $payment_result['payment_id']);
        
        // Create admin notification
        $notification_message = ($payment_method === 'cash') 
            ? "Cash payment confirmed for booking #{$booking_id} - Amount: ₱{$payment_amount}"
            : "Payment proof uploaded for booking #{$booking_id} - requires verification. Amount: ₱{$payment_amount}";
            
        $carDB->createNotification(null, 'payment', 'Payment Received', $notification_message, $booking_id);
        
        // Send confirmation email
        sendPaymentConfirmationEmail($user_id, $booking_id, [
            'method' => $payment_method,
            'amount' => $payment_amount,
            'reference' => $payment_reference,
            'status' => $payment_status
        ]);
        
        // Clear session payment data
        unset($_SESSION['pending_payment']);
        
        echo json_encode([
            'success' => true,
            'payment_id' => $payment_result['payment_id'],
            'booking_id' => $booking_id,
            'payment_reference' => $payment_reference,
            'status' => $booking_status,
            'message' => 'Payment processed successfully'
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => $payment_result['message']]);
    }
    
} catch (Exception $e) {
    error_log("Payment processing error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred. Please try again.']);
}

/**
 * Create enhanced payment record with file upload support
 */
function createEnhancedPayment($db, $payment_data) {
    try {
        // Check if payments table has the required columns
        $stmt = $db->prepare("SHOW COLUMNS FROM payments LIKE 'payment_proof_path'");
        $stmt->execute();
        $has_proof_column = $stmt->rowCount() > 0;
        
        if ($has_proof_column) {
            // Use enhanced table structure
            $stmt = $db->prepare("
                INSERT INTO payments (
                    booking_id, user_id, amount, payment_method, payment_status, 
                    transaction_id, payment_proof_path, payment_type, payment_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $payment_data['booking_id'],
                $payment_data['user_id'],
                $payment_data['amount'],
                $payment_data['payment_method'],
                $payment_data['payment_status'],
                $payment_data['transaction_reference'],
                $payment_data['payment_proof_path'],
                $payment_data['payment_type']
            ]);
        } else {
            // Use basic table structure
            $stmt = $db->prepare("
                INSERT INTO payments (
                    booking_id, user_id, amount, payment_method, payment_status, 
                    transaction_id, payment_date
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $payment_data['booking_id'],
                $payment_data['user_id'],
                $payment_data['amount'],
                $payment_data['payment_method'],
                $payment_data['payment_status'],
                $payment_data['transaction_reference']
            ]);
        }
        
        $payment_id = $db->lastInsertId();
        
        return ['success' => true, 'payment_id' => $payment_id];
        
    } catch (Exception $e) {
        error_log("Database error in createEnhancedPayment: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

/**
 * Update booking status with payment information
 */
function updateBookingStatus($db, $booking_id, $booking_status, $payment_status) {
    try {
        $stmt = $db->prepare("
            UPDATE bookings 
            SET status = ?, payment_status = ?, updated_at = NOW() 
            WHERE booking_id = ?
        ");
        
        $stmt->execute([$booking_status, $payment_status, $booking_id]);
        
    } catch (Exception $e) {
        error_log("Error updating booking status: " . $e->getMessage());
    }
}

/**
 * Send payment confirmation email
 */
function sendPaymentConfirmationEmail($user_id, $booking_id, $payment_data) {
    try {
        // In a real application, integrate with email service
        // For now, we'll simulate sending email
        
        $email_data = [
            'user_id' => $user_id,
            'booking_id' => $booking_id,
            'subject' => 'Payment Confirmation - Booking #' . $booking_id,
            'method' => $payment_data['method'],
            'amount' => $payment_data['amount'],
            'reference' => $payment_data['reference'],
            'status' => $payment_data['status'],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Log email for debugging
        error_log("Payment confirmation email: " . json_encode($email_data));
        
        // Here you would integrate with:
        // - PHPMailer for SMTP
        // - SendGrid API
        // - AWS SES
        // - Mailgun
        // etc.
        
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return false;
    }
}
?>