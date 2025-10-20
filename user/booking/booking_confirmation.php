<?php
session_start();
require_once '../../includes/config.php';
/** @var PDO $pdo */

// Ensure user logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : ($_SESSION['booking_id'] ?? null);
$paid_flag = isset($_GET['paid']) ? intval($_GET['paid']) : 0;
$method = isset($_GET['method']) ? preg_replace('/[^a-z_]/', '', $_GET['method']) : null;

$payment_verified = false;
$payment_info = null;

try {
    if (!$booking_id) {
        header('Location: step1_service_selection.php');
        exit();
    }

    // Fetch booking to ensure it belongs to user
    $stmt = $pdo->prepare("SELECT booking_reference, user_id, payment_method FROM bookings WHERE booking_id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$booking) throw new Exception('Booking not found');
    if ($booking['user_id'] != $_SESSION['user_id']) throw new Exception('Unauthorized access to booking');

    if ($paid_flag === 1) {
        // Verify a successful payment exists for this booking and user
        $allowed_status = ['completed','processing','confirmed','successful'];
        // Build placeholders
        $placeholders = implode(',', array_fill(0, count($allowed_status), '?'));
        $params = [$booking_id, $_SESSION['user_id']];
        $sql = "SELECT * FROM payments WHERE booking_id = ? AND user_id = ? AND payment_method = ? AND payment_status IN ($placeholders) ORDER BY payment_id DESC LIMIT 1";
        $params_with_method = array_merge($params, [$method], $allowed_status);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params_with_method);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($payment) {
            $payment_verified = true;
            $payment_info = $payment;
        } else {
            // No valid payment found - redirect back to payment gateway to prevent URL forging
            header('Location: payment_gateway.php?booking_id=' . $booking_id);
            exit();
        }
    }

} catch (Exception $e) {
    // On any error, redirect to dashboard
    header('Location: ../../user/dashboard_CLEAN.php');
    exit();
}

// Clear the booking_created flag if present (we'll still allow viewing of confirmed booking)
if (isset($_SESSION['booking_created'])) unset($_SESSION['booking_created']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - RideReviveDetailing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            min-height: 100vh;
            color: #222;
            font-family: -apple-system, BlinkMacSystemFont, 'San Francisco', 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }
        .confirmation-card {
            background: #fff;
            border-radius: 28px;
            box-shadow: 0 12px 48px rgba(63,103,229,0.10);
            overflow: hidden;
            border: 1.5px solid #e3eafd;
            padding: 0 0 32px 0;
        }
        .success-header {
            background: #3f67e5;
            color: #fff;
            padding: 56px 32px 36px 32px;
            text-align: center;
            border-top-left-radius: 28px;
            border-top-right-radius: 28px;
        }
        .success-icon {
            width: 110px;
            height: 110px;
            background: #f5f8ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 4px 24px 0 rgba(63,103,229,0.10);
            border: 2.5px solid #e3eafd;
        }
        .success-icon i {
            color: #3f67e5;
            font-size: 3.5rem;
        }
        .booking-ref {
            background: #f5f8ff;
            padding: 22px 18px 18px 18px;
            border-radius: 16px;
            margin: 36px 0 0 0;
            border: 1px solid #e3eafd;
        }
        .booking-ref h2 {
            font-size: 2.3rem;
            color: #111;
            font-family: 'SF Mono', 'Menlo', 'Consolas', monospace;
            letter-spacing: 0.04em;
        }
        .next-steps {
            background: #f8f9fa;
            border-radius: 18px;
            padding: 38px 32px 32px 32px;
            margin: 38px 0 0 0;
            border: 1px solid #e3eafd;
        }
        .step-item {
            display: flex;
            align-items: center;
            margin: 22px 0;
            padding: 22px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 8px 0 rgba(63,103,229,0.04);
            border: 1px solid #e3eafd;
        }
        .step-number {
            width: 48px;
            height: 48px;
            background: #3f67e5;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 22px;
            font-weight: 700;
            font-size: 1.3rem;
            box-shadow: 0 2px 8px 0 rgba(63,103,229,0.10);
        }
        .alert-success {
            background: #eaf6ee;
            color: #28a745;
            border: 1px solid #b7e4c7;
        }
        .alert-info {
            background: #f5f8ff;
            color: #3f67e5;
            border: 1px solid #e3eafd;
        }
        .text-primary {
            color: #3f67e5 !important;
        }
        .btn-primary, .btn-primary:active, .btn-primary:focus, .btn-primary:hover {
            background: #3f67e5 !important;
            border-color: #3f67e5 !important;
            color: #fff !important;
            box-shadow: 0 4px 16px 0 rgba(63,103,229,0.10);
            font-size: 1.15rem;
            font-weight: 600;
            border-radius: 12px;
            padding: 14px 38px;
        }
        .btn-outline-secondary, .btn-outline-secondary:active, .btn-outline-secondary:focus, .btn-outline-secondary:hover {
            color: #3f67e5 !important;
            border-color: #3f67e5 !important;
            background: #f5f8ff !important;
            font-size: 1.15rem;
            font-weight: 600;
            border-radius: 12px;
            padding: 14px 38px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="confirmation-card">
                    <!-- Success Header -->
                    <div class="success-header">
                        <div class="success-icon">
                            <i class="fas fa-check fa-3x"></i>
                        </div>
                        <h1 class="mb-3">🎉 Booking Confirmed!</h1>
                        <p class="lead mb-0">Your car detailing service has been successfully booked</p>

                        <div class="booking-ref">
                            <h4 class="mb-2" style="color:#111;">Booking Reference</h4>
                            <h2 class="mb-0 font-monospace"><?= htmlspecialchars($booking['booking_reference']) ?></h2>
                        </div>
                    </div>

                    <!-- Confirmation Details -->
                    <div class="p-4">
                        <?php if ($payment_verified): ?>
                        <div class="alert alert-success border-0" role="alert">
                            <h5 class="alert-heading"><i class="fas fa-check-circle me-2"></i>Payment Successful</h5>
                            <p class="mb-0">Payment successful! Your booking is now awaiting admin approval.</p>
                            <hr>
                            <p class="small mb-0">Payment Reference: <strong><?= htmlspecialchars($payment_info['gateway_reference'] ?? $payment_info['transaction_id'] ?? '') ?></strong></p>
                            <p class="small mb-0">Amount: <strong>₱<?= number_format($payment_info['amount'], 2) ?></strong></p>
                            <p class="small mb-0">Method: <strong><?= htmlspecialchars(ucfirst($payment_info['payment_method'])) ?></strong></p>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info border-0" role="alert">
                            <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Booking Created</h5>
                            <p class="mb-0">Your booking is currently <strong>pending admin approval</strong>. You will receive a confirmation email and SMS once approved (within 24 hours).</p>
                        </div>
                        <?php endif; ?>

                        <!-- Next Steps -->
                        <div class="next-steps">
                            <h5 class="mb-4"><i class="fas fa-list-check me-2 text-primary"></i>Next Steps</h5>

                            <div class="step-item">
                                <div class="step-number">1</div>
                                <div>
                                    <h6 class="mb-1">Booking Received</h6>
                                    <small class="text-muted">We’ve successfully received your booking and payment details.</small>
                                </div>
                            </div>

                            <div class="step-item">
                                <div class="step-number">2</div>
                                <div>
                                    <h6 class="mb-1">Schedule Confirmation</h6>
                                    <small class="text-muted">Our team will verify your selected date and service availability within 24 hours.</small>
                                </div>
                            </div>

                            <div class="step-item">
                                <div class="step-number">3</div>
                                <div>
                                    <h6 class="mb-1">Preparation</h6>
                                    <small class="text-muted">Once confirmed, you’ll receive an email and SMS with your official service schedule and technician details.</small>
                                </div>
                            </div>

                            <div class="step-item">
                                <div class="step-number">4</div>
                                <div>
                                    <h6 class="mb-1">Service Day</h6>
                                    <small class="text-muted">Our team will arrive at your location on the scheduled date and time — no further action needed from your side.</small>
                                </div>
                            </div>
                        </div>

                        <!-- Booking Summary (Unified Design) -->
                        <?php if (isset($_SESSION['booking_flow'])): ?>
                            <div class="booking-summary-highlight mt-4 p-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary">Service Date</h6>
                                        <p class="mb-2"><?= htmlspecialchars($_SESSION['booking_flow']['booking_date'] ?? 'N/A') ?></p>
                                        <h6 class="text-primary">Service Time</h6>
                                        <p class="mb-2"><?= htmlspecialchars($_SESSION['booking_flow']['booking_time'] ?? 'N/A') ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-primary">Payment Mode</h6>
                                        <p class="mb-2"><?= ($_SESSION['booking_flow']['payment_mode'] ?? '') === '50_percent' ? '50% Deposit' : 'Full Payment' ?></p>
                                        <h6 class="text-primary">Payment Method</h6>
                                        <p class="mb-2 text-capitalize"><?= htmlspecialchars($_SESSION['booking_flow']['payment_method'] ?? 'N/A') ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-center gap-3 mt-4">
                            <a href="../../user/dashboard_CLEAN.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-home me-2"></i>Back to Dashboard
                            </a>
                            <a href="booking_details.php?booking_id=<?= $booking_id ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-eye me-2"></i>View Booking Details
                            </a>
                        </div>
                    </div>
                </div>
                                    .booking-summary-highlight {
                                        background: #f5f8ff;
                                        border-radius: 18px;
                                        box-shadow: 0 2px 12px rgba(63,103,229,0.07);
                                        border: 1px solid #e3eafd;
                                    }
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>