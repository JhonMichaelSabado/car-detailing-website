<?php
// payment_confirm.php - validate OTP and record mock payment
session_start();
require_once '../../includes/config.php';
/** @var PDO $pdo */

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: payment_gateway.php');
    exit();
}

$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$method = isset($_POST['method']) ? preg_replace('/[^a-z_]/', '', $_POST['method']) : '';
$otp_input = isset($_POST['otp_input']) ? trim($_POST['otp_input']) : '';
$bank_reference = isset($_POST['bank_reference']) ? trim($_POST['bank_reference']) : null;

try {
    if (!$booking_id || !$method || !$otp_input) throw new Exception('Missing required data');

    // Fetch booking
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$booking) throw new Exception('Booking not found');
    if ($booking['user_id'] != $_SESSION['user_id']) throw new Exception('Unauthorized access to booking');

    // Check OTP in session
    if (!isset($_SESSION['booking_otps'][$booking_id])) throw new Exception('OTP not generated for this booking');
    $otp_record = $_SESSION['booking_otps'][$booking_id];

    if ($otp_record['method'] !== $method) throw new Exception('OTP method mismatch');
    if (time() > ($otp_record['expires_at'] ?? 0)) {
        unset($_SESSION['booking_otps'][$booking_id]);
        throw new Exception('OTP expired');
    }

    if (!password_verify($otp_input, $otp_record['hash'])) {
        throw new Exception('Invalid OTP');
    }

    // OTP is valid - simulate processing
    // Determine amount to record
    $payment_amount = 0.00;
    if ($booking['payment_mode'] === 'deposit_50') {
        $payment_amount = floatval($booking['deposit_amount']);
        $payment_type = 'deposit';
    } else {
        $payment_amount = floatval($booking['total_amount']);
        $payment_type = 'full';
    }

    // For bank transfer, simulate a 2-3 second delay
    if ($method === 'bank') {
        sleep(2); // realistic delay
    }

    // Generate reference
    $reference_no = strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));

    // Use transaction for insert+update
    $pdo->beginTransaction();

    // Insert payment record using prepared statement
    $insertSql = "INSERT INTO payments (booking_id, user_id, payment_type, amount, payment_method, payment_status, gateway_reference, gateway_response, payment_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $insertStmt = $pdo->prepare($insertSql);
    $gateway_response = json_encode(['method' => $method, 'note' => 'Mock successful transaction', 'bank_reference' => $bank_reference]);
    $insertStmt->execute([
        $booking_id,
        $_SESSION['user_id'],
        $payment_type,
        $payment_amount,
        $method,
        'completed',
        $reference_no,
        $gateway_response
    ]);

    $payment_id = $pdo->lastInsertId();

    // Update booking: set payment_status='paid' and status to pending admin approval
    // Note: the bookings.status enum may not contain 'pending_approval', so map to 'pending' (assumption)
    $mapped_status = 'pending'; // mapped from requested 'pending_approval'

    $updateSql = "UPDATE bookings SET payment_status = 'paid', status = ?, updated_at = NOW() WHERE booking_id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([$mapped_status, $booking_id]);

    $pdo->commit();

    // Clear OTP so it cannot be reused
    unset($_SESSION['booking_otps'][$booking_id]);

    // Redirect to confirmation page
    header("Location: booking_confirmation.php?booking_id={$booking_id}&paid=1&method=" . urlencode($method));
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // On error, show message and allow user to retry
    $msg = $e->getMessage();
    $_SESSION['payment_error'] = $msg;
    header("Location: payment_{$method}.php?booking_id={$booking_id}&error=1");
    exit();
}

?>
