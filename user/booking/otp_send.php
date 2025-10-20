<?php
// otp_send.php - generate a mock OTP and store a hashed version in session
session_start();
require_once '../../includes/config.php';
/** @var PDO $pdo */
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$booking_id = isset($data['booking_id']) ? intval($data['booking_id']) : 0;
$method = isset($data['method']) ? preg_replace('/[^a-z_]/', '', $data['method']) : '';

if (!$booking_id || !$method) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT booking_id, user_id, total_amount FROM bookings WHERE booking_id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$booking) throw new Exception('Booking not found');
    if ($booking['user_id'] != $_SESSION['user_id']) throw new Exception('Unauthorized');

    // Initialize session container
    if (!isset($_SESSION['booking_otps'])) $_SESSION['booking_otps'] = [];

    // Prevent spamming: allow new OTP if none exists or last generated > 15s ago
    $existing = $_SESSION['booking_otps'][$booking_id] ?? null;
    if ($existing && isset($existing['generated_at']) && (time() - $existing['generated_at'] < 15)) {
        echo json_encode(['success' => false, 'message' => 'Please wait before requesting a new OTP']);
        exit();
    }

    // Generate secure 6-digit OTP
    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $hash = password_hash($otp, PASSWORD_DEFAULT);

    // Store only hash and metadata server-side. Mark shown=true so we only return it once per generation.
    $_SESSION['booking_otps'][$booking_id] = [
        'hash' => $hash,
        'method' => $method,
        'expires_at' => time() + 300, // 5 minutes
        'generated_at' => time(),
        'shown' => true
    ];

    // Return OTP plaintext once (sandbox)
    echo json_encode(['success' => true, 'otp' => $otp]);
    exit();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}

?>
