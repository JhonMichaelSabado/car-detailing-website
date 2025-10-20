<?php
session_start();
require_once '../../includes/config.php';

// Set test user if not logged in (for development)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Test user
}

// Check if we have booking data and this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['booking_flow'])) {
    header("Location: step5_review.php");
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Generate unique booking reference
    $booking_reference = 'CD' . date('Ymd') . strtoupper(substr(uniqid(), -6));
    
    // Get booking data from session
    $booking_data = $_SESSION['booking_flow'];
    $user_id = $_SESSION['user_id'];
    
    // Get service details for pricing
    $service_stmt = $pdo->prepare("SELECT * FROM services WHERE service_id = ?");
    $service_stmt->execute([$booking_data['service_id']]);
    $service = $service_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service) {
        throw new Exception("Service not found");
    }
    
    // Calculate pricing
    $vehicle_size = $booking_data['vehicle_size'];
    $base_price = $service["price_$vehicle_size"];
    $travel_fee = floatval($booking_data['travel_fee']);
    
    // Handle add-ons
    $addons = json_decode($booking_data['addon_services'] ?? '[]', true) ?: [];
    $addons_total = 0;
    
    if (!empty($addons)) {
        $addon_ids = implode(',', array_map('intval', $addons));
        $addon_stmt = $pdo->query("SELECT * FROM addon_services WHERE addon_id IN ($addon_ids)");
        $addon_services_data = $addon_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($addon_services_data as $addon) {
            $addons_total += $addon["price_$vehicle_size"];
        }
    }
    
    // Calculate totals
    $subtotal = $base_price + $travel_fee + $addons_total;
    $vat_amount = $subtotal * 0.12;
    $total_amount = $subtotal + $vat_amount;
    
    // Calculate payment amounts based on mode
    $payment_mode = $booking_data['payment_mode'];
    
    // Convert payment mode values to match database enum
    if ($payment_mode === '50_percent') {
        $payment_mode = 'deposit_50';
    } elseif ($payment_mode === 'full_payment') {
        $payment_mode = 'full_payment';
    }
    
    $deposit_amount = $payment_mode === 'deposit_50' ? $total_amount * 0.5 : $total_amount;
    $remaining_amount = $payment_mode === 'deposit_50' ? $total_amount * 0.5 : 0;
    
    // Insert booking record with correct column names
    $booking_sql = "INSERT INTO bookings (
        booking_reference, user_id, service_id, vehicle_size, add_on_services,
        booking_date, booking_time, estimated_duration,
        service_address, service_lat, service_lng, travel_fee, landmark_instructions,
        base_service_price, add_ons_total, subtotal, vat_amount, total_amount,
        payment_mode, deposit_amount, remaining_amount, payment_method,
        status, payment_status, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    
    $booking_stmt = $pdo->prepare($booking_sql);
    $booking_stmt->execute([
        $booking_reference,
        $user_id,
        $booking_data['service_id'],
        $vehicle_size,
        json_encode($addons),
        $booking_data['booking_date'],
        $booking_data['booking_time'],
        120, // estimated_duration in minutes
        $booking_data['service_address'],
        $booking_data['service_lat'] ?? null,
        $booking_data['service_lng'] ?? null,
        $travel_fee,
        $booking_data['landmark_instructions'] ?? '',
        $base_price,
        $addons_total,
        $subtotal,
        $vat_amount,
        $total_amount,
        $payment_mode,
        $deposit_amount,
        $remaining_amount,
        $booking_data['payment_method'],
        'pending'
    ]);
    
    $booking_id = $pdo->lastInsertId();
    
    // Create payment record if payments table exists
    try {
        $payment_sql = "INSERT INTO payments (
            booking_id, user_id, payment_type, amount, payment_method, payment_status, created_at
        ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
        
        $payment_type = $payment_mode === 'deposit_50' ? 'deposit' : 'full';
        $payment_stmt = $pdo->prepare($payment_sql);
        $payment_stmt->execute([
            $booking_id,
            $user_id,
            $payment_type,
            $deposit_amount,
            $booking_data['payment_method']
        ]);
        
        $payment_id = $pdo->lastInsertId();
    } catch (Exception $e) {
        // Payments table might not exist, continue without it
        $payment_id = null;
        error_log("Payment record creation failed: " . $e->getMessage());
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Store booking info in session
    $_SESSION['booking_id'] = $booking_id;
    $_SESSION['booking_reference'] = $booking_reference;
    $_SESSION['booking_created'] = true;
    
    // Log successful booking creation
    error_log("Booking created successfully: ID $booking_id, Reference: $booking_reference");
    
    // Redirect to mock payment gateway
    header("Location: payment_gateway.php?booking_id=$booking_id");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollback();
    
    error_log("Booking processing error: " . $e->getMessage());
    
    // Redirect back to review with error
    $_SESSION['booking_error'] = "There was an error processing your booking. Please try again. Error: " . $e->getMessage();
    header("Location: step5_review.php");
    exit();
}
?>