<?php
session_start();
require_once '../../includes/config.php';

// Set test user if not logged in (for development)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Test user
}

// Check if coming from step 4
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log what we received
    error_log("Step 5 received POST data: " . print_r($_POST, true));
    
    if (isset($_POST['payment_mode'])) {
        $_SESSION['booking_flow']['payment_mode'] = $_POST['payment_mode'];
        $_SESSION['booking_flow']['payment_method'] = $_POST['payment_method'];
        
        // Save promo code and discount if provided
        if (!empty($_POST['promo_code'])) {
            $_SESSION['booking_flow']['promo_code'] = $_POST['promo_code'];
            $_SESSION['booking_flow']['promo_discount'] = floatval($_POST['promo_discount'] ?? 0);
        } else {
            // Clear promo if not provided
            unset($_SESSION['booking_flow']['promo_code']);
            unset($_SESSION['booking_flow']['promo_discount']);
        }
        
        $_SESSION['booking_step'] = 5;
    } else {
        error_log("Step 5 error: payment_mode not found in POST data");
        header("Location: step4_payment_mode.php");
        exit();
    }
} elseif (!isset($_SESSION['booking_flow']['payment_mode'])) {
    error_log("Step 5 error: No payment_mode in session");
    header("Location: step4_payment_mode.php");
    exit();
}

// Get all details for final review
try {
    $service_stmt = $pdo->prepare("SELECT * FROM services WHERE service_id = ?");
    $service_stmt->execute([$_SESSION['booking_flow']['service_id']]);
    $service = $service_stmt->fetch(PDO::FETCH_ASSOC);
    
    $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$_SESSION['user_id']]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate final pricing
    $vehicle_size = $_SESSION['booking_flow']['vehicle_size'];
    $base_price = $service["price_$vehicle_size"];
    $travel_fee = $_SESSION['booking_flow']['travel_fee'];
    $addons = json_decode($_SESSION['booking_flow']['addon_services'] ?? '[]', true);
    $addons_total = 0;
    
    if (!empty($addons)) {
        $addon_ids = implode(',', array_map('intval', $addons));
        $addon_stmt = $pdo->query("SELECT * FROM addon_services WHERE addon_id IN ($addon_ids)");
        $addon_services = $addon_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($addon_services as $addon) {
            $addons_total += $addon["price_$vehicle_size"];
        }
    }
    
    $subtotal = $base_price + $travel_fee + $addons_total;
    $vat = $subtotal * 0.12;
    $total_amount = $subtotal + $vat;
    
    // Apply promo discount if available
    $promo_discount = 0;
    $promo_code = '';
    if (isset($_SESSION['booking_flow']['promo_discount']) && $_SESSION['booking_flow']['promo_discount'] > 0) {
        $promo_discount = floatval($_SESSION['booking_flow']['promo_discount']);
        $promo_code = $_SESSION['booking_flow']['promo_code'] ?? '';
        $total_amount = $total_amount - $promo_discount;
        
        // Ensure total doesn't go below zero
        if ($total_amount < 0) $total_amount = 0;
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header("Location: step4_payment_mode.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Car Detailing - Review Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Apple-style progress bar */
        .booking-progress-bar {
            position: absolute;
            top: 32px;
            left: 48px;
            display: flex;
            flex-direction: row;
            gap: 18px;
            max-width: 420px;
            background: none;
            box-shadow: none;
            padding: 0 32px;
            z-index: 10;
            overflow: visible;
        }
        .progress-step {
            width: 38px;
            height: 38px;
            aspect-ratio: 1/1;
            border-radius: 50%;
            background: #fff;
            color: #3f67e5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.25rem;
            box-shadow: 0 2px 12px 0 rgba(63,103,229,0.05);
            border: 2.5px solid #e3eafd;
            transition: background 0.2s, border 0.2s;
            vertical-align: middle;
            box-sizing: border-box;
        }
        .progress-step.active {
            background: #3f67e5;
            color: #fff;
            border: 2.5px solid #3f67e5;
            box-shadow: 0 2px 12px 0 rgba(63,103,229,0.13);
        }
        .progress-step.completed {
            background: #3f67e5;
            color: #fff;
            border: 2.5px solid #3f67e5;
            opacity: 0.7;
        }
        .booking-progress-header {
            font-family: -apple-system, BlinkMacSystemFont, 'San Francisco', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 2.9rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #222;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        .booking-progress-subtitle {
            font-size: 1.1rem;
            color: #888;
            font-weight: 400;
            letter-spacing: 0.01em;
            margin-bottom: 1.2rem;
            text-align: center;
        }
        .booking-progress-container {
            position: relative;
            width: 100%;
            height: 180px;
            margin-bottom: 0;
        }
        /* Button color override */
        .btn-primary, .btn-primary:active, .btn-primary:focus, .btn-primary:hover {
            background: #3f67e5 !important;
            border-color: #3f67e5 !important;
            color: #fff !important;
            box-shadow: 0 2px 8px 0 rgba(63,103,229,0.08);
        }
        .btn-outline-primary, .btn-outline-primary:active, .btn-outline-primary:focus, .btn-outline-primary:hover {
            color: #3f67e5 !important;
            border-color: #3f67e5 !important;
            background: #f5f8ff !important;
        }
        }
        .review-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        .review-header {
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .edit-link {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .edit-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        .price-breakdown {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        .total-amount {
            background: #3f67e5;
            color: #fff;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .card-header.bg-primary.text-white {
            background: #3f67e5 !important;
            color: #fff !important;
        }
        .btn-primary, .btn-primary:active, .btn-primary:focus, .btn-primary:hover {
            background: #3f67e5 !important;
            border: none;
            color: #fff !important;
            padding: 15px 40px;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: bold;
            box-shadow: 0 2px 8px 0 rgba(63,103,229,0.08);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
        }
        .terms-checkbox {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .security-badges {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }
        .security-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: #e8f5e8;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #28a745;
        }
    </style>
</head>
<body>
    <!-- Apple-style Progress Bar and Header -->
    <div class="booking-progress-container">
        <div class="booking-progress-bar">
            <div class="progress-step completed">1</div>
            <div class="progress-step completed">2</div>
            <div class="progress-step completed">3</div>
            <div class="progress-step completed">4</div>
            <div class="progress-step active">5</div>
        </div>
        <div style="max-width: 1100px; margin: 0 auto; padding-top: 32px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <div class="booking-progress-header">Professional Car Detailing Booking</div>
            <span class="booking-progress-subtitle">Step 5 of 9<span style="margin: 0 0.5em;">•</span>Review & Confirm</span>
        </div>
        <hr style="border: none; border-top: 2.5px solid #e3e3ea; margin: 32px 0 0 0; width: 99%; opacity: 0.7;" />
        <div style="margin-bottom: 48px;"></div>
    </div>

    <div class="container my-5">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <form id="confirmBookingForm" method="POST" action="process_booking_fixed.php">
                    <!-- Customer Information -->
                    <div class="review-section">
                        <div class="review-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user me-2 text-primary"></i>Customer Information
                            </h5>
                        </div>
                        <div class="detail-row">
                            <span>Name:</span>
                            <span><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span>Email:</span>
                            <span><?= htmlspecialchars($user['email']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span>Phone:</span>
                            <span><?= htmlspecialchars($user['phone'] ?? 'Not provided') ?></span>
                        </div>
                    </div>

                    <!-- Service Details -->
                    <div class="review-section">
                        <div class="review-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-car-wash me-2 text-primary"></i>Service Details
                                </h5>
                                <a href="step1_service_selection.php" class="edit-link">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </a>
                            </div>
                        </div>
                        <div class="detail-row">
                            <span>Service:</span>
                            <span><?= htmlspecialchars($service['service_name']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span>Category:</span>
                            <span><?= htmlspecialchars($service['category']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span>Vehicle Size:</span>
                            <span class="text-capitalize"><?= htmlspecialchars($vehicle_size) ?></span>
                        </div>
                        <div class="detail-row">
                            <span>Duration:</span>
                            <span><?= $service['duration_minutes'] ?> minutes</span>
                        </div>
                        <?php if (!empty($addon_services)): ?>
                        <div class="detail-row">
                            <span>Add-ons:</span>
                            <div>
                                <?php foreach ($addon_services as $addon): ?>
                                <div class="small"><?= htmlspecialchars($addon['service_name']) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Location & Schedule -->
                    <div class="review-section">
                        <div class="review-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-map-marker-alt me-2 text-primary"></i>Location & Schedule
                                </h5>
                                <div>
                                    <a href="step2_location.php" class="edit-link me-3">
                                        <i class="fas fa-edit me-1"></i>Edit Location
                                    </a>
                                    <a href="step3_datetime.php" class="edit-link">
                                        <i class="fas fa-edit me-1"></i>Edit Time
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="detail-row">
                            <span>Service Address:</span>
                            <span class="text-end"><?= htmlspecialchars($_SESSION['booking_flow']['service_address']) ?></span>
                        </div>
                        <?php if (!empty($_SESSION['booking_flow']['landmark_instructions'])): ?>
                        <div class="detail-row">
                            <span>Landmark:</span>
                            <span><?= htmlspecialchars($_SESSION['booking_flow']['landmark_instructions']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="detail-row">
                            <span>Date:</span>
                            <span><?= date('l, F j, Y', strtotime($_SESSION['booking_flow']['booking_date'])) ?></span>
                        </div>
                        <div class="detail-row">
                            <span>Time:</span>
                            <span><?= date('g:i A', strtotime($_SESSION['booking_flow']['booking_time'])) ?></span>
                        </div>
                        <div class="detail-row">
                            <span>Travel Fee:</span>
                            <span>₱<?= number_format($travel_fee, 2) ?></span>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="review-section">
                        <div class="review-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-credit-card me-2 text-primary"></i>Payment Information
                                </h5>
                                <a href="step4_payment_mode.php" class="edit-link">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </a>
                            </div>
                        </div>
                        <div class="detail-row">
                            <span>Payment Mode:</span>
                            <span>
                                <?= $_SESSION['booking_flow']['payment_mode'] === 'deposit_50' ? '50% Deposit' : 'Full Payment' ?>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span>Payment Method:</span>
                            <span class="text-capitalize">
                                <?php
                                $method_names = [
                                    'gcash' => 'GCash',
                                    'maya' => 'Maya',
                                    'credit_card' => 'Credit Card',
                                    'bank_transfer' => 'Bank Transfer'
                                ];
                                echo $method_names[$_SESSION['booking_flow']['payment_method']] ?? $_SESSION['booking_flow']['payment_method'];
                                ?>
                            </span>
                        </div>
                        <?php
                        $pay_now = $_SESSION['booking_flow']['payment_mode'] === 'deposit_50' ? $total_amount * 0.5 : $total_amount;
                        $pay_later = $_SESSION['booking_flow']['payment_mode'] === 'deposit_50' ? $total_amount * 0.5 : 0;
                        ?>
                        <div class="detail-row">
                            <span>Amount to Pay Now:</span>
                            <strong class="text-success">₱<?= number_format($pay_now, 2) ?></strong>
                        </div>
                        <div class="detail-row">
                            <span>Amount to Pay Later:</span>
                            <span class="text-muted">₱<?= number_format($pay_later, 2) ?></span>
                        </div>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="terms-checkbox">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="termsAccepted" required>
                            <label class="form-check-label" for="termsAccepted">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a> 
                                and <a href="#" data-bs-toggle="modal" data-bs-target="#policyModal">Privacy Policy</a>
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="cancellationPolicy" required>
                            <label class="form-check-label" for="cancellationPolicy">
                                I understand the <a href="#" data-bs-toggle="modal" data-bs-target="#cancellationModal">Cancellation Policy</a>
                            </label>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between">
                        <a href="step4_payment_mode.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Payment
                        </a>
                        <button type="submit" id="confirmBtn" class="btn btn-primary" disabled>
                            <i class="fas fa-lock me-2"></i>Confirm & Proceed to Payment
                        </button>
                    </div>

                    <!-- Security Badges -->
                    <div class="security-badges">
                        <div class="security-badge">
                            <i class="fas fa-shield-alt"></i>
                            <span>Secure Booking</span>
                        </div>
                        <div class="security-badge">
                            <i class="fas fa-clock"></i>
                            <span>Instant Confirmation</span>
                        </div>
                        <div class="security-badge">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Refund Protection</span>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Price Summary Sidebar -->
            <div class="col-lg-4">
                <div class="summary-card sticky-top" style="top: 20px;">
                    <div class="summary-header">
                        <h6 class="mb-0"><i class="fas fa-calculator me-2"></i>Final Pricing</h6>
                    </div>
                    <div class="summary-body">
                        <div class="price-breakdown">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Base Service:</span>
                                <span>₱<?= number_format($base_price, 2) ?></span>
                            </div>
                            <?php if ($addons_total > 0): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Add-ons:</span>
                                <span>₱<?= number_format($addons_total, 2) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Travel Fee:</span>
                                <span>₱<?= number_format($travel_fee, 2) ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>₱<?= number_format($subtotal, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>VAT (12%):</span>
                                <span>₱<?= number_format($vat, 2) ?></span>
                            </div>
                            <?php if ($promo_discount > 0): ?>
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span><i class="fas fa-tag me-1"></i>Promo Discount (<?= htmlspecialchars($promo_code) ?>):</span>
                                <span>-₱<?= number_format($promo_discount, 2) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="total-amount">
                            <h5 class="mb-2">Total Amount</h5>
                            <h3 class="mb-0">₱<?= number_format($total_amount, 2) ?></h3>
                        </div>

                        <div class="bg-light p-3 rounded">
                            <h6 class="mb-2">Payment Breakdown</h6>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Pay Now:</span>
                                <strong class="text-success">₱<?= number_format($pay_now, 2) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Pay After Service:</span>
                                <span class="text-muted">₱<?= number_format($pay_later, 2) ?></span>
                            </div>
                        </div>

                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Booking will be confirmed after admin approval
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>Terms and Conditions</h6>
                    <p>By booking a service with us, you agree to the following terms:</p>
                    <ul>
                        <li><strong>Service Scope</strong> — All detailing services are performed based on the selected package and any add-ons chosen during booking. Any additional requests outside the package may incur extra fees.</li>
                        <li><strong>Booking Confirmation</strong> — Your appointment will be confirmed once payment (if required) or deposit is received.</li>
                        <li><strong>Vehicle Condition</strong> — Please ensure your vehicle is accessible and ready for service. We are not responsible for pre-existing damage or items left inside the vehicle.</li>
                        <li><strong>Timing</strong> — Estimated service durations may vary depending on vehicle size, condition, and chosen package.</li>
                        <li><strong>Liability</strong> — While we take great care, we are not liable for any loss or damage caused by circumstances beyond our control (e.g., weather, traffic, or unforeseen incidents).</li>
                        <li><strong>Acceptance</strong> — By proceeding with your booking, you acknowledge that you have read, understood, and agreed to all the terms stated here.</li>
                    </ul>
                </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Privacy Policy Modal -->
    <div class="modal fade" id="policyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Privacy Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>Privacy Policy</h6>
                    <ul>
                        <li><strong>Data Collection</strong> — We collect personal information (name, contact details, address, and vehicle information) only to process your booking and provide service updates.</li>
                        <li><strong>Data Use</strong> — Your information is used solely for communication, booking management, and customer support. We do not sell, rent, or share your data with third parties.</li>
                        <li><strong>Data Security</strong> — All personal data is stored securely and protected against unauthorized access.</li>
                        <li><strong>Cookies</strong> — Our website may use cookies to improve your browsing experience.</li>
                        <li><strong>Your Rights</strong> — You may request correction or deletion of your data at any time by contacting our support team.</li>
                    </ul>
                </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Cancellation Policy Modal -->
    <div class="modal fade" id="cancellationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancellation Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>Cancellation Policy</h6>
                    <ul>
                        <li><strong>Cancellations</strong> — You may cancel or reschedule your booking at least 24 hours before your appointment without penalty.</li>
                        <li><strong>Late Cancellations / No-Shows</strong> — Cancellations made within 24 hours or failure to appear at the scheduled time may result in a cancellation fee or loss of deposit.</li>
                        <li><strong>Weather Delays</strong> — In the event of severe weather or safety concerns, we may reschedule your booking at no additional cost.</li>
                        <li><strong>Refunds</strong> — Eligible refunds (if any) will be processed within 3–5 business days.</li>
                    </ul>
                </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function checkFormComplete() {
            const termsAccepted = document.getElementById('termsAccepted').checked;
            const cancellationAccepted = document.getElementById('cancellationPolicy').checked;
            const confirmBtn = document.getElementById('confirmBtn');
            confirmBtn.disabled = !(termsAccepted && cancellationAccepted);
        }

        document.getElementById('termsAccepted').addEventListener('change', checkFormComplete);
        document.getElementById('cancellationPolicy').addEventListener('change', checkFormComplete);
    </script>
</body>
</html>