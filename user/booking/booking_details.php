<?php
session_start();
require_once '../../includes/config.php';
/** @var PDO $pdo */

// Get booking_id from query
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : null;
if (!$booking_id) {
    header('Location: booking_confirmation.php');
    exit();
}

// Fetch booking details
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_id = ?");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$booking || $booking['user_id'] != $_SESSION['user_id']) {
    header('Location: ../../user/dashboard_CLEAN.php');
    exit();
}

// Fetch service details
$service_stmt = $pdo->prepare("SELECT * FROM services WHERE service_id = ?");
$service_stmt->execute([$booking['service_id']]);
$service = $service_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch payment details
$payment_stmt = $pdo->prepare("SELECT * FROM payments WHERE booking_id = ? AND user_id = ? ORDER BY payment_id DESC LIMIT 1");
$payment_stmt->execute([$booking_id, $_SESSION['user_id']]);
$payment = $payment_stmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - RideReviveDetailing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            min-height: 100vh;
            color: #222;
            font-family: -apple-system, BlinkMacSystemFont, 'San Francisco', 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }
        .details-card {
            background: #fff;
            border-radius: 28px;
            box-shadow: 0 12px 48px rgba(63,103,229,0.10);
            overflow: hidden;
            border: 1.5px solid #e3eafd;
            padding: 0 0 32px 0;
            margin-top: 48px;
            animation: fadeIn 0.7s cubic-bezier(.4,0,.2,1);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .details-header {
            background: #3f67e5;
            color: #fff;
            padding: 48px 32px 36px 32px;
            text-align: center;
            border-top-left-radius: 28px;
            border-top-right-radius: 28px;
        }
        .details-header h1 {
            font-size: 2.3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .details-section {
            padding: 32px 38px 0 38px;
        }
        .details-label {
            font-weight: 600;
            color: #3f67e5;
            margin-bottom: 2px;
        }
        .details-value {
            font-size: 1.1rem;
            margin-bottom: 18px;
        }
        .details-divider {
            border: none;
            border-top: 2px solid #e3eafd;
            margin: 32px 0 24px 0;
            opacity: 0.7;
        }
        .details-summary {
            background: #f5f8ff;
            border-radius: 16px;
            padding: 24px 18px;
            border: 1px solid #e3eafd;
            margin-top: 24px;
        }
        .details-summary h4 {
            color: #3f67e5;
            font-weight: 700;
            margin-bottom: 18px;
        }
        .details-summary .row > div {
            margin-bottom: 12px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="details-card">
                    <div class="details-header">
                        <h1><i class="fas fa-file-invoice me-2"></i>Booking Details</h1>
                        <div class="details-value">Reference: <span style="color:#111;font-family:'SF Mono','Menlo','Consolas',monospace;letter-spacing:0.04em;"><?= htmlspecialchars($booking['booking_reference']) ?></span></div>
                    </div>
                    <div class="details-section">
                        <div class="details-label">Service</div>
                        <div class="details-value"><?= htmlspecialchars($service['service_name']) ?></div>
                        <div class="details-label">Category</div>
                        <div class="details-value"><?= htmlspecialchars($service['category']) ?></div>
                    <div class="summary-card booking-summary-highlight">
                        <div class="details-value"><?= htmlspecialchars($booking['service_date'] ?? 'N/A') ?></div>
                        <div class="details-label">Time</div>
                        <div class="details-value"><?= htmlspecialchars($booking['service_time'] ?? 'N/A') ?></div>
                        <div class="details-label">Location</div>
                        <div class="details-value"><?= htmlspecialchars($booking['service_address']) ?></div>
                        <div class="details-label">Technician</div>
                        <div class="details-value"><?= htmlspecialchars($booking['technician_name'] ?? 'To be assigned') ?></div>
                        <hr class="details-divider" />
                        <div class="summary-card">
                            <h4>Payment Summary</h4>
                            <div class="row">
                                <div class="col-6">
                                    <div class="details-label">Payment Mode</div>
                                    <div class="details-value"><?= ($booking['payment_mode'] ?? '') === '50_percent' ? '50% Deposit' : 'Full Payment' ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="details-label">Payment Method</div>
                                    <div class="details-value text-capitalize"><?= htmlspecialchars($booking['payment_method'] ?? 'N/A') ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="details-label">Amount Paid</div>
                                    <div class="details-value">₱<?= number_format($payment['amount'] ?? 0, 2) ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="details-label">Payment Status</div>
                                    <div class="details-value"><?= htmlspecialchars($payment['payment_status'] ?? 'Pending') ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-4">
                            <a href="booking_confirmation.php?booking_id=<?= $booking_id ?>" class="btn btn-primary"><i class="fas fa-arrow-left me-2"></i>Back to Confirmation</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
