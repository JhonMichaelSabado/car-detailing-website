<?php
session_start();
require_once '../../includes/config.php';

// Set test user if not logged in (for development)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Test user
}

// Check if coming from step 3
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_date'])) {
    $_SESSION['booking_flow']['booking_date'] = $_POST['booking_date'];
    $_SESSION['booking_flow']['booking_time'] = $_POST['booking_time'];
    $_SESSION['booking_flow']['estimated_duration'] = $_POST['estimated_duration'];
    $_SESSION['booking_step'] = 4;
} elseif (!isset($_SESSION['booking_flow']['booking_date'])) {
    header("Location: step3_datetime.php");
    exit();
}

// Calculate total pricing
try {
    $service_stmt = $pdo->prepare("SELECT * FROM services WHERE service_id = ?");
    $service_stmt->execute([$_SESSION['booking_flow']['service_id']]);
    $service = $service_stmt->fetch(PDO::FETCH_ASSOC);
    
    $vehicle_size = $_SESSION['booking_flow']['vehicle_size'];
    $base_price = $service["price_$vehicle_size"];
    $travel_fee = $_SESSION['booking_flow']['travel_fee'];
    $subtotal = $base_price + $travel_fee;
    $vat = $subtotal * 0.12;
    $total_amount = $subtotal + $vat;
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header("Location: step3_datetime.php");
    exit();
}

// Clear promo code from session if starting fresh (not coming back from step 5)
if (!isset($_GET['back']) && isset($_SESSION['booking_flow']['promo_code'])) {
    unset($_SESSION['booking_flow']['promo_code']);
    unset($_SESSION['booking_flow']['promo_discount']);
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Car Detailing - Payment Options</title>
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
        .payment-option {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        .payment-option:hover {
            border-color: #667eea;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }
        .payment-option.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }
        .payment-option .badge {
            position: absolute;
            top: 15px;
            right: 15px;
        }
        .payment-method-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .payment-method {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-method:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        .payment-method.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        .payment-method img {
            max-height: 40px;
            margin-bottom: 10px;
        }
        .security-info {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 15px;
            border-radius: 0 8px 8px 0;
            margin-top: 20px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        .amount-breakdown {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
        }
        .recommended-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
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
            <div class="progress-step active">4</div>
            <div class="progress-step">5</div>
        </div>
        <div style="max-width: 1100px; margin: 0 auto; padding-top: 32px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <div class="booking-progress-header">Professional Car Detailing Booking</div>
            <span class="booking-progress-subtitle">Step 4 of 9<span style="margin: 0 0.5em;">•</span>Payment Options</span>
        </div>
        <hr style="border: none; border-top: 2.5px solid #e3e3ea; margin: 32px 0 0 0; width: 99%; opacity: 0.7;" />
        <div style="margin-bottom: 48px;"></div>
    </div>

    <div class="container my-5">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <form id="paymentForm" method="POST" action="step5_review.php">
                    <!-- Payment Mode Selection -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-credit-card me-2 text-primary"></i>Choose Payment Mode</h5>
                        </div>
                        <div class="card-body">
                            <!-- 50% Deposit Option -->
                            <div class="payment-option" data-mode="deposit_50">
                                <span class="badge recommended-badge">Recommended</span>
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h6 class="mb-2">
                                            <i class="fas fa-coins me-2 text-warning"></i>
                                            50% Deposit Now
                                        </h6>
                                        <p class="text-muted mb-3">
                                            Pay 50% now to secure your booking. Remaining balance will be collected after service completion.
                                        </p>
                                        <div class="amount-breakdown">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Pay Now (50%):</span>
                                                <strong class="text-success">₱<?= number_format($total_amount * 0.5, 2) ?></strong>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Pay After Service:</span>
                                                <span class="text-muted">₱<?= number_format($total_amount * 0.5, 2) ?></span>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <small class="text-success">
                                                <i class="fas fa-check-circle me-1"></i>
                                                Lower upfront cost • Flexible payment • Secure booking
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <div class="display-6 text-success">50%</div>
                                        <div class="text-muted">Deposit</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Full Payment Option -->
                            <div class="payment-option" data-mode="full_payment">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h6 class="mb-2">
                                            <i class="fas fa-money-bill-wave me-2 text-primary"></i>
                                            Full Payment Now
                                        </h6>
                                        <p class="text-muted mb-3">
                                            Pay the complete amount now and enjoy a hassle-free service experience.
                                        </p>
                                        <div class="amount-breakdown">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Pay Now (100%):</span>
                                                <strong class="text-primary">₱<?= number_format($total_amount, 2) ?></strong>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Pay After Service:</span>
                                                <span class="text-muted">₱0.00</span>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <small class="text-primary">
                                                <i class="fas fa-star me-1"></i>
                                                Complete payment • No follow-up required • Premium experience
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <div class="display-6 text-primary">100%</div>
                                        <div class="text-muted">Full Payment</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method Selection -->
                    <div class="card border-0 shadow-sm mb-4" id="paymentMethodCard" style="display: none;">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-wallet me-2 text-primary"></i>Select Payment Method</h5>
                        </div>
                        <div class="card-body">
                            <div class="payment-method-grid">
                                <div class="payment-method" data-method="gcash">
                                    <div class="mb-2">
                                        <img src="/car-detailing/assets/logos/gcash-logo.png" alt="GCash Logo" style="height: 38px; width: auto; object-fit: contain;" />
                                    </div>
                                    <h6 class="mb-1">GCash</h6>
                                    <p class="small text-muted mb-0">Mobile wallet</p>
                                </div>
                                <div class="payment-method" data-method="maya">
                                    <div class="mb-2">
                                        <img src="/car-detailing/assets/logos/maya-logo.png" alt="Maya Logo" style="height: 48px; width: auto; object-fit: contain;" />
                                    </div>
                                    <h6 class="mb-1">Maya</h6>
                                    <p class="small text-muted mb-0">Digital payments</p>
                                </div>
                                <div class="payment-method" data-method="credit_card">
                                    <div class="mb-2">
                                        <i class="fab fa-cc-visa fa-2x text-primary me-2"></i>
                                        <i class="fab fa-cc-mastercard fa-2x text-warning"></i>
                                    </div>
                                    <h6 class="mb-1">Credit Card</h6>
                                    <p class="small text-muted mb-0">Visa, Mastercard</p>
                                </div>
                                <div class="payment-method" data-method="bank_transfer">
                                    <div class="mb-2">
                                        <i class="fas fa-university fa-2x text-success"></i>
                                    </div>
                                    <h6 class="mb-1">Bank Transfer</h6>
                                    <p class="small text-muted mb-0">Online banking</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Information -->
                    <div class="security-info">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="mb-2">
                                    <i class="fas fa-shield-alt me-2 text-success"></i>
                                    Secure Payment Processing
                                </h6>
                                <ul class="mb-0 small">
                                    <li>All payments are processed through secure, encrypted channels</li>
                                    <li>Your payment information is never stored on our servers</li>
                                    <li>Full refund available if service is cancelled by us</li>
                                    <li>PCI DSS compliant payment processing</li>
                                </ul>
                            </div>
                            <div class="col-md-4 text-center">
                                <i class="fas fa-lock fa-3x text-success opacity-50"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden inputs -->
                    <input type="hidden" id="selectedPaymentMode" name="payment_mode" required>
                    <input type="hidden" id="selectedPaymentMethod" name="payment_method" required>
                    <input type="hidden" id="appliedPromoCode" name="promo_code" value="">
                    <input type="hidden" id="promoDiscount" name="promo_discount" value="0">

                    <!-- Promo Code Section -->
                    <div class="card border-0 shadow-sm mb-4" id="promoSection">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-tags me-2 text-success"></i>Promo Code
                                <small class="text-muted">(Optional)</small>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-end">
                                <div class="col-md-8">
                                    <label for="promoCodeInput" class="form-label">Enter promo code</label>
                                    <input type="text" class="form-control" id="promoCodeInput" placeholder="e.g. WELCOME20, SAVE100" style="text-transform: uppercase;">
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Enter a valid promo code to get instant discount
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-outline-success w-100" id="applyPromoBtn">
                                        <i class="fas fa-check me-2"></i>Apply Code
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Promo Status Display -->
                            <div id="promoStatus" style="display: none;">
                                <div class="alert alert-success mt-3 mb-0" id="promoSuccess" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-check-circle me-2"></i>
                                            <strong id="promoCodeDisplay"></strong> applied!
                                            <br><small id="promoDescription"></small>
                                        </div>
                                        <div class="text-end">
                                            <h6 class="mb-0 text-success">-₱<span id="promoSavings">0</span></h6>
                                            <button type="button" class="btn btn-link btn-sm text-danger p-0" id="removePromoBtn">
                                                <i class="fas fa-times me-1"></i>Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-danger mt-3 mb-0" id="promoError" style="display: none;">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <span id="promoErrorText"></span>
                                </div>
                            </div>
                            
                            <!-- Sample Codes (for demo) -->
                            <div class="mt-3">
                                <small class="text-muted">
                                    <strong>Try these codes:</strong> 
                                    <span class="badge bg-light text-dark me-1" style="cursor: pointer;" onclick="document.getElementById('promoCodeInput').value='WELCOME20'">WELCOME20</span>
                                    <span class="badge bg-light text-dark me-1" style="cursor: pointer;" onclick="document.getElementById('promoCodeInput').value='CLEAN15'">CLEAN15</span>
                                    <span class="badge bg-light text-dark" style="cursor: pointer;" onclick="document.getElementById('promoCodeInput').value='SAVE100'">SAVE100</span>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="step3_datetime.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Date & Time
                        </a>
                        
                        <div>
                            <button type="submit" id="continueBtn" class="btn btn-primary" disabled>
                                Review Booking <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Booking Summary Sidebar -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-receipt me-2"></i>Payment Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6>Service Details</h6>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Service:</span>
                                <span><?= htmlspecialchars($service['service_name']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Vehicle Size:</span>
                                <span class="text-capitalize"><?= htmlspecialchars($vehicle_size) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Date:</span>
                                <span><?= date('M j, Y', strtotime($_SESSION['booking_flow']['booking_date'])) ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Time:</span>
                                <span><?= date('g:i A', strtotime($_SESSION['booking_flow']['booking_time'])) ?></span>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <h6>Pricing Breakdown</h6>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Base Price:</span>
                                <span>₱<?= number_format($base_price, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Travel Fee:</span>
                                <span>₱<?= number_format($travel_fee, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Subtotal:</span>
                                <span>₱<?= number_format($subtotal, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>VAT (12%):</span>
                                <span>₱<?= number_format($vat, 2) ?></span>
                            </div>
                        </div>

                        <hr>
                        
                        <!-- Promo Discount Line (Hidden by default) -->
                        <div class="d-flex justify-content-between mb-2 text-success" id="promoDiscountLine" style="display: none;">
                            <span><i class="fas fa-tag me-1"></i>Promo Discount:</span>
                            <span>-₱<span id="promoDiscountAmount">0.00</span></span>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total Amount:</strong>
                            <strong class="text-primary" data-summary="total">₱<?= number_format($total_amount, 2) ?></strong>
                        </div>

                        <div class="bg-light p-3 rounded" id="paymentSummary">
                            <h6 class="mb-3">Selected Payment</h6>
                            <div id="paymentDetails">
                                <p class="text-muted text-center">
                                    <i class="fas fa-hand-pointer fa-2x mb-2 opacity-50"></i><br>
                                    Select a payment option above
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const totalAmount = <?= $total_amount ?>;
        let selectedPaymentMode = null;
        let selectedPaymentMethod = null;
        let appliedPromo = null;
        let currentTotal = totalAmount;
        let promoDiscount = 0;

        // Simplified payment mode selection
        function initPaymentOptions() {
            console.log('Initializing payment options');
            const paymentOptions = document.querySelectorAll('.payment-option');
            console.log('Found payment options:', paymentOptions.length);
            
            paymentOptions.forEach(option => {
                option.addEventListener('click', function() {
                    console.log('Payment option clicked:', this.dataset.mode);
                    
                    // Remove previous selection
                    document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
                    
                    // Select current
                    this.classList.add('selected');
                    selectedPaymentMode = this.dataset.mode;
                    
                    // Set hidden input immediately
                    const hiddenInput = document.getElementById('selectedPaymentMode');
                    if (hiddenInput) {
                        hiddenInput.value = selectedPaymentMode;
                        console.log('✅ Payment mode set:', selectedPaymentMode);
                    } else {
                        console.error('❌ Hidden input selectedPaymentMode not found');
                    }
                    
                    // Show payment method selection
                    const paymentMethodCard = document.getElementById('paymentMethodCard');
                    if (paymentMethodCard) {
                        paymentMethodCard.style.display = 'block';
                    }
                    
                    updatePaymentSummary();
                    checkFormComplete();
                });
            });
        }

        // Simplified payment method selection
        function initPaymentMethods() {
            console.log('Initializing payment methods');
            const paymentMethods = document.querySelectorAll('.payment-method');
            console.log('Found payment methods:', paymentMethods.length);
            
            paymentMethods.forEach(method => {
                method.addEventListener('click', function() {
                    console.log('Payment method clicked:', this.dataset.method);
                    
                    // Remove previous selection
                    document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                    
                    // Select current
                    this.classList.add('selected');
                    selectedPaymentMethod = this.dataset.method;
                    
                    // Set hidden input immediately
                    const hiddenInput = document.getElementById('selectedPaymentMethod');
                    if (hiddenInput) {
                        hiddenInput.value = selectedPaymentMethod;
                        console.log('✅ Payment method set:', selectedPaymentMethod);
                    } else {
                        console.error('❌ Hidden input selectedPaymentMethod not found');
                    }
                    
                    updatePaymentSummary();
                    checkFormComplete();
                });
            });
        }

        function updatePaymentSummary() {
            if (!selectedPaymentMode) return;
            
            const paymentDetails = document.getElementById('paymentDetails');
            
            // Use currentTotal which includes promo discount
            const effectiveTotal = currentTotal;
            
            let payNowAmount, payLaterAmount, modeText, methodText = '';
            
            if (selectedPaymentMode === 'deposit_50') {
                payNowAmount = effectiveTotal * 0.5;
                payLaterAmount = effectiveTotal * 0.5;
                modeText = '50% Deposit';
            } else {
                payNowAmount = effectiveTotal;
                payLaterAmount = 0;
                modeText = 'Full Payment';
            }
            
            if (selectedPaymentMethod) {
                const methodNames = {
                    'gcash': 'GCash',
                    'maya': 'Maya',
                    'credit_card': 'Credit Card',
                    'bank_transfer': 'Bank Transfer'
                };
                methodText = methodNames[selectedPaymentMethod];
            }
            
            paymentDetails.innerHTML = `
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Payment Mode:</span>
                        <strong>${modeText}</strong>
                    </div>
                    ${selectedPaymentMethod ? `
                    <div class="d-flex justify-content-between mb-1">
                        <span>Payment Method:</span>
                        <strong>${methodText}</strong>
                    </div>
                    ` : ''}
                </div>
                <hr>
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Pay Now:</span>
                        <strong class="text-success">₱${payNowAmount.toLocaleString('en-PH', {minimumFractionDigits: 2})}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Pay Later:</span>
                        <span class="text-muted">₱${payLaterAmount.toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                    </div>
                </div>
            `;
        }

        // Simplified form completion check
        function checkFormComplete() {
            const continueBtn = document.getElementById('continueBtn');
            const paymentModeInput = document.getElementById('selectedPaymentMode');
            const paymentMethodInput = document.getElementById('selectedPaymentMethod');
            
            const hasMode = paymentModeInput && paymentModeInput.value;
            const hasMethod = paymentMethodInput && paymentMethodInput.value;
            const isComplete = hasMode && hasMethod;
            
            if (continueBtn) {
                continueBtn.disabled = !isComplete;
                if (isComplete) {
                    continueBtn.style.opacity = '1';
                } else {
                    continueBtn.style.opacity = '0.6';
                }
            }
            
            // Debug logging
            console.log('Form completion check:', {
                hasMode,
                hasMethod,
                isComplete,
                modeValue: paymentModeInput?.value,
                methodValue: paymentMethodInput?.value
            });
        }
        
        // Simplified form submission handler
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            console.log('🚀 Form submit event triggered');
            
            const paymentModeInput = document.getElementById('selectedPaymentMode');
            const paymentMethodInput = document.getElementById('selectedPaymentMethod');
            
            const modeValue = paymentModeInput ? paymentModeInput.value : '';
            const methodValue = paymentMethodInput ? paymentMethodInput.value : '';
            
            console.log('Checking form data:', {
                mode: modeValue,
                method: methodValue,
                hasMode: !!modeValue,
                hasMethod: !!methodValue
            });
            
            // Only check if hidden inputs have values - ignore JavaScript variables
            if (!modeValue || !methodValue) {
                console.log('❌ Missing required values - preventing submission');
                e.preventDefault();
                alert('Please select both a payment mode and payment method before continuing.');
                return false;
            }
            
            console.log('✅ All required values present - proceeding with submission');
            
            // Add loading state to button
            const submitBtn = document.getElementById('continueBtn');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Preparing Summary...';
                submitBtn.disabled = true;
                
                // Restore button if submission takes too long
                setTimeout(() => {
                    if (submitBtn.innerHTML.includes('Preparing')) {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        console.log('⚠️ Form submission took too long - button restored');
                    }
                }, 10000);
            }
            
            console.log('🎯 Submitting to step5_review.php');
            // Form will submit normally
        });
        
        // Debug function to submit form directly
        function directSubmit() {
            console.log('🔧 Direct submit triggered');
            
            // Set default values for testing
            document.getElementById('selectedPaymentMode').value = 'full_payment';
            document.getElementById('selectedPaymentMethod').value = 'gcash';
            
            console.log('🔧 Set default values - submitting form');
            document.getElementById('paymentForm').submit();
        }
        
        // Show debug button if in development (optional)
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            setTimeout(() => {
                const debugBtn = document.getElementById('debugSubmit');
                if (debugBtn) debugBtn.style.display = 'inline-block';
            }, 2000);
        }
        
        // Promo Code Functions
        function updatePricingWithPromo() {
            // Update the payment summary with promo discount
            const promoDiscountLine = document.getElementById('promoDiscountLine');
            const promoDiscountAmount = document.getElementById('promoDiscountAmount');
            
            // Show/hide promo discount line
            if (promoDiscount > 0) {
                promoDiscountLine.style.display = 'flex';
                promoDiscountAmount.textContent = promoDiscount.toLocaleString('en-PH', {minimumFractionDigits: 2});
            } else {
                promoDiscountLine.style.display = 'none';
            }
            
            // Calculate final total after discount
            const finalTotal = currentTotal;
            
            // Update total amount in sidebar
            const totalElement = document.querySelector('[data-summary="total"]');
            if (totalElement) {
                totalElement.textContent = '₱' + finalTotal.toLocaleString('en-PH', {minimumFractionDigits: 2});
            }
            
            // Update payment summary (This will be called by updatePaymentSummary)
            console.log('Promo discount applied:', promoDiscount, 'New total:', finalTotal);
        }
        
        async function applyPromoCode() {
            const promoInput = document.getElementById('promoCodeInput');
            const applyBtn = document.getElementById('applyPromoBtn');
            const promoCode = promoInput.value.trim().toUpperCase();
            
            if (!promoCode) {
                showPromoError('Please enter a promo code');
                return;
            }
            
            // Show loading state
            applyBtn.disabled = true;
            applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Checking...';
            
            try {
                const response = await fetch('validate_promo_code.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        code: promoCode,
                        total: totalAmount
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    appliedPromo = result;
                    promoDiscount = result.discount;
                    currentTotal = result.newTotal;
                    
                    // Update hidden inputs
                    document.getElementById('appliedPromoCode').value = result.code;
                    document.getElementById('promoDiscount').value = result.discount;
                    
                    // Show success message
                    showPromoSuccess(result);
                    
                    // Update pricing display
                    updatePricingWithPromo();
                    updatePaymentSummary();
                    
                } else {
                    showPromoError(result.message);
                }
                
            } catch (error) {
                console.error('Promo validation error:', error);
                showPromoError('Network error. Please try again.');
            }
            
            // Reset button
            applyBtn.disabled = false;
            applyBtn.innerHTML = '<i class="fas fa-check me-2"></i>Apply Code';
        }
        
        function showPromoSuccess(promo) {
            const statusDiv = document.getElementById('promoStatus');
            const successDiv = document.getElementById('promoSuccess');
            const errorDiv = document.getElementById('promoError');
            
            document.getElementById('promoCodeDisplay').textContent = promo.code;
            document.getElementById('promoDescription').textContent = promo.description;
            document.getElementById('promoSavings').textContent = promo.discount.toLocaleString('en-PH', {minimumFractionDigits: 2});
            
            errorDiv.style.display = 'none';
            successDiv.style.display = 'block';
            statusDiv.style.display = 'block';
            
            // Disable input and apply button
            document.getElementById('promoCodeInput').disabled = true;
            document.getElementById('applyPromoBtn').disabled = true;
        }
        
        function showPromoError(message) {
            const statusDiv = document.getElementById('promoStatus');
            const successDiv = document.getElementById('promoSuccess');
            const errorDiv = document.getElementById('promoError');
            
            document.getElementById('promoErrorText').textContent = message;
            
            successDiv.style.display = 'none';
            errorDiv.style.display = 'block';
            statusDiv.style.display = 'block';
            
            // Auto-hide error after 5 seconds
            setTimeout(() => {
                statusDiv.style.display = 'none';
            }, 5000);
        }
        
        function removePromoCode() {
            appliedPromo = null;
            promoDiscount = 0;
            currentTotal = totalAmount;
            
            // Clear hidden inputs
            document.getElementById('appliedPromoCode').value = '';
            document.getElementById('promoDiscount').value = '0';
            
            // Enable input and apply button
            document.getElementById('promoCodeInput').disabled = false;
            document.getElementById('promoCodeInput').value = '';
            document.getElementById('applyPromoBtn').disabled = false;
            
            // Hide status
            document.getElementById('promoStatus').style.display = 'none';
            
            // Update pricing display
            updatePricingWithPromo();
            updatePaymentSummary();
        }
        
        // Initialize form state
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded, initializing payment form');
            
            // Check if elements exist first
            const paymentOptions = document.querySelectorAll('.payment-option');
            const paymentMethods = document.querySelectorAll('.payment-method');
            
            console.log('Payment options found:', paymentOptions.length);
            console.log('Payment methods found:', paymentMethods.length);
            
            if (paymentOptions.length === 0) {
                console.error('No payment options found! Check CSS selector .payment-option');
            }
            
            if (paymentMethods.length === 0) {
                console.error('No payment methods found! Check CSS selector .payment-method');
            }
            
            // Initialize payment options and methods
            initPaymentOptions();
            initPaymentMethods();
            
            // Check initial form state
            checkFormComplete();
            
            // Verify all required elements exist
            const requiredElements = [
                'selectedPaymentMode',
                'selectedPaymentMethod', 
                'paymentForm',
                'continueBtn'
            ];
            
            requiredElements.forEach(id => {
                const element = document.getElementById(id);
                if (!element) {
                    console.error(`Required element missing: ${id}`);
                } else {
                    console.log(`Found element: ${id}`);
                }
            });
            
            // Add promo code event listeners
            const applyPromoBtn = document.getElementById('applyPromoBtn');
            const removePromoBtn = document.getElementById('removePromoBtn');
            const promoInput = document.getElementById('promoCodeInput');
            
            if (applyPromoBtn) {
                applyPromoBtn.addEventListener('click', applyPromoCode);
            }
            
            if (removePromoBtn) {
                removePromoBtn.addEventListener('click', removePromoCode);
            }
            
            if (promoInput) {
                // Allow Enter key to apply promo
                promoInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        applyPromoCode();
                    }
                });
                
                // Auto-uppercase input
                promoInput.addEventListener('input', function(e) {
                    e.target.value = e.target.value.toUpperCase();
                });
            }
        });
    </script>
</body>
</html>