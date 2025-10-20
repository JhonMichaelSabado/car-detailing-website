<?php
header('Content-Type: application/json');
session_start();
require_once '../../includes/config.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$promoCode = strtoupper(trim($input['code'] ?? ''));
$totalAmount = floatval($input['total'] ?? 0);

$response = ['success' => false, 'message' => '', 'discount' => 0, 'newTotal' => $totalAmount];

try {
    if (empty($promoCode)) {
        $response['message'] = 'Please enter a promo code';
        echo json_encode($response);
        exit;
    }
    
    if ($totalAmount <= 0) {
        $response['message'] = 'Invalid total amount';
        echo json_encode($response);
        exit;
    }
    
    // Check if promo code exists and is valid
    $stmt = $pdo->prepare("
        SELECT * FROM promo_codes 
        WHERE code = ? 
        AND is_active = 1 
        AND valid_until > NOW()
        AND (usage_limit IS NULL OR used_count < usage_limit)
    ");
    $stmt->execute([$promoCode]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$promo) {
        $response['message'] = 'Invalid or expired promo code';
        echo json_encode($response);
        exit;
    }
    
    // Check minimum amount requirement
    if ($totalAmount < $promo['min_amount']) {
        $response['message'] = "Minimum spend of ₱" . number_format($promo['min_amount'], 2) . " required for this promo code";
        echo json_encode($response);
        exit;
    }
    
    // Calculate discount
    $discount = 0;
    if ($promo['discount_type'] === 'percentage') {
        $discount = ($totalAmount * $promo['discount_value']) / 100;
        // Apply max discount if set
        if ($promo['max_discount'] > 0 && $discount > $promo['max_discount']) {
            $discount = $promo['max_discount'];
        }
    } else {
        // Fixed discount
        $discount = $promo['discount_value'];
    }
    
    // Ensure discount doesn't exceed total
    if ($discount > $totalAmount) {
        $discount = $totalAmount;
    }
    
    $newTotal = $totalAmount - $discount;
    
    // Success response
    $response = [
        'success' => true,
        'message' => 'Promo code applied successfully!',
        'code' => $promo['code'],
        'description' => $promo['description'],
        'discount' => $discount,
        'newTotal' => $newTotal,
        'discountType' => $promo['discount_type'],
        'discountValue' => $promo['discount_value']
    ];
    
} catch (Exception $e) {
    $response['message'] = 'Error validating promo code. Please try again.';
    error_log("Promo validation error: " . $e->getMessage());
}

echo json_encode($response);
?>