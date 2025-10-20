<?php
session_start();
require_once '../../includes/config.php';

// Set test user if not logged in (for development)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

// Create test session data if missing
if (!isset($_SESSION['booking_flow'])) {
    $_SESSION['booking_flow'] = [
        'service_id' => 1,
        'vehicle_size' => 'small',
        'service_address' => 'Test Location',
        'travel_fee' => 50,
        'booking_date' => '2024-01-15',
        'booking_time' => '10:00',
        'addon_services' => '[]'
    ];
}

// Handle form submission - this will work!
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save payment data to session
    $_SESSION['booking_flow']['payment_mode'] = $_POST['payment_mode'];
    $_SESSION['booking_flow']['payment_method'] = $_POST['payment_method'];
    $_SESSION['booking_step'] = 5;
    
    // Redirect to Step 5
    header("Location: step5_review.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Selection - WORKING VERSION</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .payment-option { 
            border: 2px solid #dee2e6; 
            padding: 20px; 
            margin: 15px 0; 
            cursor: pointer; 
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        .payment-option:hover { 
            border-color: #007bff; 
            transform: translateY(-2px);
        }
        .payment-option.selected { 
            background: linear-gradient(135deg, #007bff, #0056b3); 
            color: white; 
            border-color: #007bff;
        }
        .payment-method { 
            border: 2px solid #dee2e6; 
            padding: 15px; 
            margin: 10px 0; 
            cursor: pointer; 
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .payment-method:hover { 
            border-color: #28a745; 
        }
        .payment-method.selected { 
            background: linear-gradient(135deg, #28a745, #1e7e34); 
            color: white; 
            border-color: #28a745;
        }
        .working-form {
            border: 3px solid #28a745;
            border-radius: 15px;
            padding: 30px;
            background: white;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow">
                    <div class="card-header bg-success text-white text-center">
                        <h3 class="mb-0">🎯 Step 4: Payment Selection - WORKING VERSION</h3>
                        <p class="mb-0">Simple form that actually submits to Step 5</p>
                    </div>
                    <div class="card-body working-form">
                        
                        <form method="POST" action="" id="workingForm">
                            <h4 class="text-primary mb-4">💳 Select Payment Mode</h4>
                            
                            <div class="payment-option" data-mode="50_percent">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-1">50% Deposit Now</h5>
                                        <p class="mb-0 text-muted">Pay half now, half after service completion</p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <h5 class="mb-0">₱500 now</h5>
                                        <small>₱500 later</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="payment-option" data-mode="full_payment">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-1">Full Payment</h5>
                                        <p class="mb-0 text-muted">Pay the complete amount now</p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <h5 class="mb-0">₱1,000</h5>
                                        <small>Pay once</small>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <h4 class="text-primary mb-4" id="methodSection" style="display: none;">💰 Select Payment Method</h4>
                            
                            <div id="paymentMethods" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="payment-method" data-method="gcash">
                                            <div class="text-center">
                                                <i class="fas fa-mobile-alt fa-2x text-primary mb-2"></i>
                                                <h6>GCash</h6>
                                                <small>Mobile wallet</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="payment-method" data-method="maya">
                                            <div class="text-center">
                                                <i class="fas fa-wallet fa-2x text-success mb-2"></i>
                                                <h6>Maya</h6>
                                                <small>Digital payments</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="payment-method" data-method="credit_card">
                                            <div class="text-center">
                                                <i class="fas fa-credit-card fa-2x text-warning mb-2"></i>
                                                <h6>Credit Card</h6>
                                                <small>Visa, Mastercard</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="payment-method" data-method="bank_transfer">
                                            <div class="text-center">
                                                <i class="fas fa-university fa-2x text-info mb-2"></i>
                                                <h6>Bank Transfer</h6>
                                                <small>Online banking</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hidden inputs for form submission -->
                            <input type="hidden" name="payment_mode" id="selectedMode" value="">
                            <input type="hidden" name="payment_method" id="selectedMethod" value="">
                            
                            <!-- Status display -->
                            <div class="alert alert-info mt-4" id="statusDisplay">
                                <h6><i class="fas fa-info-circle me-2"></i>Selection Status:</h6>
                                <p class="mb-1">Payment Mode: <span id="modeStatus">Not selected</span></p>
                                <p class="mb-0">Payment Method: <span id="methodStatus">Not selected</span></p>
                            </div>
                            
                            <!-- Submit button -->
                            <div class="d-flex justify-content-between mt-4">
                                <a href="step3_datetime.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Date & Time
                                </a>
                                <button type="submit" id="submitBtn" class="btn btn-success btn-lg" disabled>
                                    <i class="fas fa-arrow-right me-2"></i>Continue to Review
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let selectedMode = '';
        let selectedMethod = '';
        
        function updateStatus() {
            // Update status display
            document.getElementById('modeStatus').textContent = selectedMode || 'Not selected';
            document.getElementById('methodStatus').textContent = selectedMethod || 'Not selected';
            
            // Update hidden inputs
            document.getElementById('selectedMode').value = selectedMode;
            document.getElementById('selectedMethod').value = selectedMethod;
            
            // Enable/disable submit button
            const submitBtn = document.getElementById('submitBtn');
            if (selectedMode && selectedMethod) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-secondary');
                submitBtn.classList.add('btn-success');
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.remove('btn-success');
                submitBtn.classList.add('btn-secondary');
            }
            
            console.log('Status updated:', { mode: selectedMode, method: selectedMethod });
        }
        
        // Payment mode selection
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                console.log('Payment mode clicked:', this.dataset.mode);
                
                // Remove previous selection
                document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
                
                // Select current
                this.classList.add('selected');
                selectedMode = this.dataset.mode;
                
                // Show payment methods
                document.getElementById('methodSection').style.display = 'block';
                document.getElementById('paymentMethods').style.display = 'block';
                
                updateStatus();
            });
        });
        
        // Payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                console.log('Payment method clicked:', this.dataset.method);
                
                // Remove previous selection
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                
                // Select current
                this.classList.add('selected');
                selectedMethod = this.dataset.method;
                
                updateStatus();
            });
        });
        
        // Form submission
        document.getElementById('workingForm').addEventListener('submit', function(e) {
            console.log('✅ Form submitting with:', {
                mode: selectedMode,
                method: selectedMethod
            });
            
            // No validation - let it submit!
            if (selectedMode && selectedMethod) {
                // Add loading state
                const submitBtn = document.getElementById('submitBtn');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                submitBtn.disabled = true;
            }
        });
        
        // Initialize
        updateStatus();
        console.log('🎯 Working payment form loaded');
    </script>
</body>
</html>