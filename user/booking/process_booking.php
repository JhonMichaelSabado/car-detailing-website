<?php
session_start();
require_once '../../includes/config.php';

// Set test user if not logged in (for development)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Test user
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: step1_service_selection.php");
    exit();
}

// Validate booking flow data
if (!isset($_SESSION['booking_flow']) || $_SESSION['booking_step'] !== 5) {
    header("Location: step1_service_selection.php");
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Get service details for pricing
    $service_stmt = $pdo->prepare("SELECT * FROM services WHERE service_id = ?");
    $service_stmt->execute([$_SESSION['booking_flow']['service_id']]);
    $service = $service_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service) {
        throw new Exception("Service not found");
    }
    
    // Calculate pricing
    $vehicle_size = $_SESSION['booking_flow']['vehicle_size'];
    $base_price = $service["price_$vehicle_size"];
    $travel_fee = floatval($_SESSION['booking_flow']['travel_fee']);
    
    // Handle add-ons
    $addons = json_decode($_SESSION['booking_flow']['addon_services'] ?? '[]', true) ?: [];
    $addons_total = 0;
    $addon_services_data = [];
    
    if (!empty($addons)) {
        $addon_ids = implode(',', array_map('intval', $addons));
        $addon_stmt = $pdo->query("SELECT * FROM addon_services WHERE addon_id IN ($addon_ids)");
        $addon_services_data = $addon_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($addon_services_data as $addon) {
            $addons_total += $addon["price_$vehicle_size"];
        }
    }
    
    $subtotal = $base_price + $travel_fee + $addons_total;
    $vat_amount = $subtotal * 0.12;
    $total_amount = $subtotal + $vat_amount;
    
    // Generate booking reference
    $booking_reference = generateBookingReference($pdo);
    
    // Calculate payment amounts based on mode
    $payment_mode = $_SESSION['booking_flow']['payment_mode'];
    $deposit_amount = $payment_mode === 'deposit_50' ? $total_amount * 0.5 : $total_amount;
    $remaining_amount = $payment_mode === 'deposit_50' ? $total_amount * 0.5 : 0;
    
    // Insert booking record
    $booking_stmt = $pdo->prepare("
        INSERT INTO bookings (
            booking_reference, user_id, service_id, vehicle_size, add_on_services,
            booking_date, booking_time, estimated_duration,
            service_address, service_lat, service_lng, travel_fee, landmark_instructions,
            base_service_price, add_ons_total, subtotal, vat_amount, total_amount,
            payment_mode, deposit_amount, remaining_amount, payment_method,
            status, payment_status, slot_locked_until, auto_cancel_after
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            'pending', 'pending', DATE_ADD(NOW(), INTERVAL 10 MINUTE), DATE_ADD(NOW(), INTERVAL 48 HOUR)
        )
    ");
    
    $booking_stmt->execute([
        $booking_reference,
        $_SESSION['user_id'],
        $_SESSION['booking_flow']['service_id'],
        $vehicle_size,
        json_encode($addons),
        $_SESSION['booking_flow']['booking_date'],
        $_SESSION['booking_flow']['booking_time'],
        $_SESSION['booking_flow']['estimated_duration'] ?? 120,
        $_SESSION['booking_flow']['service_address'],
        $_SESSION['booking_flow']['service_lat'] ?? null,
        $_SESSION['booking_flow']['service_lng'] ?? null,
        $travel_fee,
        $_SESSION['booking_flow']['landmark_instructions'] ?? '',
        $base_price,
        $addons_total,
        $subtotal,
        $vat_amount,
        $total_amount,
        $payment_mode,
        $deposit_amount,
        $remaining_amount,
        $_SESSION['booking_flow']['payment_method']
    ]);
    
    $booking_id = $pdo->lastInsertId();
    
    // Create initial payment record
    $payment_stmt = $pdo->prepare("
        INSERT INTO payments (
            booking_id, user_id, payment_type, amount, payment_method, payment_status
        ) VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    
    $payment_type = $payment_mode === 'deposit_50' ? 'deposit' : 'full';
    $payment_stmt->execute([
        $booking_id,
        $_SESSION['user_id'],
        $payment_type,
        $deposit_amount,
        $_SESSION['booking_flow']['payment_method']
    ]);
    
    $payment_id = $pdo->lastInsertId();
    
    // Create notification for admin
    $notification_stmt = $pdo->prepare("
        INSERT INTO notifications (
            user_id, type, title, message, related_booking_id
        ) VALUES (
            NULL, 'booking', 'New Booking Received', 
            'New booking #? requires admin approval', ?
        )
    ");
    $notification_stmt->execute([$booking_reference, $booking_id]);
    
    // Log activity
    $activity_stmt = $pdo->prepare("
        INSERT INTO activity_logs (
            user_id, action, description, related_table, related_id, ip_address
        ) VALUES (?, 'booking_created', 'New booking created', 'bookings', ?, ?)
    ");
    $activity_stmt->execute([
        $_SESSION['user_id'],
        $booking_id,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    $pdo->commit();
    
    // Clear booking flow session
    unset($_SESSION['booking_flow']);
    unset($_SESSION['booking_step']);
    
    // Redirect to pending status page
    header("Location: step6_pending.php?booking_id=$booking_id&payment_id=$payment_id");
    exit();
    
} catch (Exception $e) {
    $pdo->rollback();
    error_log("Booking processing error: " . $e->getMessage());
    
    $_SESSION['error'] = "There was an error processing your booking. Please try again.";
    header("Location: step5_review.php");
    exit();
}

function generateBookingReference($pdo) {
    $today = date('Ymd');
    
    // Get the next sequence number for today
    $stmt = $pdo->prepare("
        SELECT COUNT(*) + 1 as next_num 
        FROM bookings 
        WHERE booking_reference LIKE ?
    ");
    $stmt->execute([$today . '%']);
    $result = $stmt->fetch();
    $sequence = str_pad($result['next_num'], 4, '0', STR_PAD_LEFT);
    
    return $today . $sequence;
}
?>