<?php
session_start();
require_once '../../includes/config.php';

// Set test user if not logged in (for development)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Test user
}

// Create minimal session for testing
if (!isset($_SESSION['booking_flow'])) {
    $_SESSION['booking_flow'] = [
        'service_id' => 1,
        'vehicle_size' => 'small',
        'location' => 'Test Location',
        'travel_fee' => 50,
        'booking_date' => '2024-01-15',
        'booking_time' => '10:00',
        'addon_services' => '[]'
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h1>✅ FORM SUBMISSION SUCCESSFUL!</h1>";
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    echo "<h3>Session Data:</h3>";
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    echo "<br><a href='step4_payment_mode.php'>Back to Payment Page</a>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .payment-option, .payment-method { 
            border: 2px solid #ddd; 
            padding: 15px; 
            margin: 10px 0; 
            cursor: pointer; 
            border-radius: 8px;
        }
        .payment-option.selected, .payment-method.selected { 
            background-color: #007bff; 
            color: white; 
            border-color: #007bff;
        }
        .test-form {
            border: 3px solid #28a745;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Payment Form Debug Test</h1>
        
        <div class="alert alert-info">
            <strong>Instructions:</strong>
            <ol>
                <li>Open browser dev tools (F12) and go to Console tab</li>
                <li>Select a payment mode</li>
                <li>Select a payment method</li>
                <li>Click "Test Submit" button</li>
                <li>Check if form submits successfully</li>
            </ol>
        </div>
        
        <form method="POST" action="" class="test-form" id="testForm">
            <h3>💳 Payment Mode</h3>
            <div class="payment-option" data-mode="50_percent">
                <strong>50% Deposit</strong><br>
                <small>Pay ₱500 now, ₱500 after service</small>
            </div>
            <div class="payment-option" data-mode="full_payment">
                <strong>Full Payment</strong><br>
                <small>Pay ₱1000 now</small>
            </div>
            
            <h3>💰 Payment Method</h3>
            <div class="payment-method" data-method="gcash">
                <strong>GCash</strong><br>
                <small>Mobile wallet</small>
            </div>
            <div class="payment-method" data-method="maya">
                <strong>Maya</strong><br>
                <small>Digital payments</small>
            </div>
            <div class="payment-method" data-method="credit_card">
                <strong>Credit Card</strong><br>
                <small>Visa, Mastercard</small>
            </div>
            
            <!-- Hidden inputs to store selections -->
            <input type="hidden" name="payment_mode" id="paymentMode" value="">
            <input type="hidden" name="payment_method" id="paymentMethod" value="">
            
            <!-- Debug display -->
            <div class="mt-3 p-3 bg-light">
                <strong>Current Selections:</strong><br>
                Payment Mode: <span id="modeDisplay">None</span><br>
                Payment Method: <span id="methodDisplay">None</span>
            </div>
            
            <button type="submit" id="submitBtn" class="btn btn-success btn-lg mt-3" disabled>
                🚀 Test Submit
            </button>
        </form>
    </div>

    <script>
        let selectedMode = null;
        let selectedMethod = null;
        
        function updateDisplay() {
            document.getElementById('modeDisplay').textContent = selectedMode || 'None';
            document.getElementById('methodDisplay').textContent = selectedMethod || 'None';
            
            // Enable/disable submit button
            const submitBtn = document.getElementById('submitBtn');
            if (selectedMode && selectedMethod) {
                submitBtn.disabled = false;
                submitBtn.style.backgroundColor = '#28a745';
            } else {
                submitBtn.disabled = true;
                submitBtn.style.backgroundColor = '#ccc';
            }
            
            console.log('Selection updated:', { mode: selectedMode, method: selectedMethod });
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
                document.getElementById('paymentMode').value = selectedMode;
                
                updateDisplay();
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
                document.getElementById('paymentMethod').value = selectedMethod;
                
                updateDisplay();
            });
        });
        
        // Form submission
        document.getElementById('testForm').addEventListener('submit', function(e) {
            console.log('🚀 Form submission triggered!');
            console.log('Mode:', selectedMode);
            console.log('Method:', selectedMethod);
            console.log('Hidden inputs:', {
                mode: document.getElementById('paymentMode').value,
                method: document.getElementById('paymentMethod').value
            });
            
            if (!selectedMode || !selectedMethod) {
                e.preventDefault();
                alert('❌ Please select both payment mode and method!');
                return false;
            }
            
            console.log('✅ Form submission proceeding...');
            // Form will submit normally
        });
        
        // Initialize
        updateDisplay();
        console.log('🔧 Payment test page loaded');
    </script>
</body>
</html>