<?php
session_start();
require_once '../../includes/config.php';
/** @var PDO $pdo */

// Require logged-in user
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not authenticated
    header('Location: ../../auth/login.php');
    exit();
}

if (!isset($_GET['booking_id'])) {
    die('Booking ID required.');
}
$booking_id = intval($_GET['booking_id']);

// Fetch booking details and ensure this belongs to the logged-in user
try {
    $stmt = $pdo->prepare("SELECT b.booking_id, b.booking_reference, b.total_amount, b.payment_status, b.user_id, b.payment_method FROM bookings b WHERE b.booking_id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception('Booking not found');
    }

    if ($booking['user_id'] != $_SESSION['user_id']) {
        throw new Exception('Unauthorized access to booking');
    }

    // If the user already selected a payment method in the booking flow (step 4),
    // skip this selection page and redirect straight to the chosen payment page.
    // Passing ?change=1 forces the selection UI.
    if (!isset($_GET['change']) || intval($_GET['change']) !== 1) {
        // Prefer session booking flow selection (most recent), fall back to DB field
        $selectedMethod = $_SESSION['booking_flow']['payment_method'] ?? $booking['payment_method'] ?? null;
        if ($selectedMethod) {
            // Normalize bank method
            if ($selectedMethod === 'bank_transfer') $selectedMethod = 'bank';
            // Map only to supported pages
            $allowed = ['gcash' => 'payment_gcash.php', 'maya' => 'payment_maya.php', 'bank' => 'payment_bank.php'];
            if (isset($allowed[$selectedMethod])) {
                header('Location: ' . $allowed[$selectedMethod] . '?booking_id=' . $booking_id);
                exit();
            }
        }
    }

} catch (Exception $e) {
    die('Error: ' . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Gateway - RideReviveDetailing</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .pay-method-btn { margin: 1rem 0; width: 100%; font-size: 1.1rem; border-radius: 12px; }
        .demo-banner { background: linear-gradient(135deg,#ffd700,#ffa500); color: #2b2b2b; padding: 0.6rem; text-align: center; font-weight: 700; border-radius: 6px; }
        body { background: #0f0f10; color: #f8f7f3; }
        .card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); }
        .muted { color: rgba(255,255,255,0.6); }
    </style>
</head>
<body class="py-5">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="demo-banner mb-3">⚠️ DEMO MODE - No real money will be charged</div>
            <div class="card p-4">
                <h4 class="mb-2">RideReviveDetailing — Payment</h4>
                <p class="muted mb-1">Booking Reference: <strong>#<?= htmlspecialchars($booking['booking_reference']) ?></strong></p>
                <p class="muted">Amount to Pay: <strong>₱<?= number_format($booking['total_amount'], 2) ?></strong></p>
                <hr style="border-color: rgba(255,255,255,0.06);">

                <div class="d-grid gap-2">
                    <a href="payment_gcash.php?booking_id=<?= $booking_id ?>" class="btn btn-primary pay-method-btn">💙 Pay with GCash</a>
                    <a href="payment_maya.php?booking_id=<?= $booking_id ?>" class="btn btn-success pay-method-btn">🟢 Pay with Maya</a>
                    <a href="payment_bank.php?booking_id=<?= $booking_id ?>" class="btn btn-secondary pay-method-btn">🏦 Bank Transfer</a>
                </div>

                <div class="mt-3 muted small">Payment records are saved locally for demo and verification. After successful payment you'll be redirected to a secure confirmation page.</div>

                <div class="mt-3 text-end">
                    <a href="?booking_id=<?= $booking_id ?>&change=1" class="text-decoration-none small">Force change payment method</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
