<?php
session_start();
require_once '../../includes/config.php';

// Simple debug version to test form submission
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

// Create minimal booking session for testing
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

echo "<h1>Debug Step 4 - Payment Selection</h1>";
echo "<h3>Current Session Data:</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Step 4</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .payment-option { border: 1px solid #ccc; padding: 15px; margin: 10px 0; cursor: pointer; }
        .payment-option.selected { background-color: #007bff; color: white; }
        form { margin: 20px 0; padding: 20px; border: 2px solid #000; }
        input, button { margin: 5px; padding: 10px; }
    </style>
</head>
<body>
    <h2>Simple Payment Form Test</h2>
    
    <form method="POST" action="step5_review.php" id="testForm">
        <h3>Payment Mode:</h3>
        <div class="payment-option" onclick="selectPaymentMode('50_percent')">
            <input type="radio" name="payment_mode" value="50_percent" id="pm1">
            <label for="pm1">50% Deposit (₱500)</label>
        </div>
        <div class="payment-option" onclick="selectPaymentMode('full_payment')">
            <input type="radio" name="payment_mode" value="full_payment" id="pm2">
            <label for="pm2">Full Payment (₱1000)</label>
        </div>
        
        <h3>Payment Method:</h3>
        <div class="payment-option" onclick="selectPaymentMethod('gcash')">
            <input type="radio" name="payment_method" value="gcash" id="pmethod1">
            <label for="pmethod1">GCash</label>
        </div>
        <div class="payment-option" onclick="selectPaymentMethod('paymaya')">
            <input type="radio" name="payment_method" value="paymaya" id="pmethod2">
            <label for="pmethod2">PayMaya</label>
        </div>
        
        <!-- Hidden inputs for JavaScript compatibility -->
        <input type="hidden" name="payment_mode" id="selectedPaymentMode" value="">
        <input type="hidden" name="payment_method" id="selectedPaymentMethod" value="">
        
        <br><br>
        <button type="submit" id="submitBtn">Submit to Step 5</button>
    </form>
    
    <script>
        let selectedPaymentMode = '';
        let selectedPaymentMethod = '';
        
        function selectPaymentMode(mode) {
            console.log('Payment mode selected:', mode);
            selectedPaymentMode = mode;
            document.getElementById('selectedPaymentMode').value = mode;
            
            // Visual feedback
            document.querySelectorAll('.payment-option').forEach(el => {
                if (el.textContent.includes('Deposit') || el.textContent.includes('Full Payment')) {
                    el.classList.remove('selected');
                }
            });
            event.target.classList.add('selected');
            
            checkForm();
        }
        
        function selectPaymentMethod(method) {
            console.log('Payment method selected:', method);
            selectedPaymentMethod = method;
            document.getElementById('selectedPaymentMethod').value = method;
            
            // Visual feedback
            document.querySelectorAll('.payment-option').forEach(el => {
                if (el.textContent.includes('GCash') || el.textContent.includes('PayMaya')) {
                    el.classList.remove('selected');
                }
            });
            event.target.classList.add('selected');
            
            checkForm();
        }
        
        function checkForm() {
            const submitBtn = document.getElementById('submitBtn');
            if (selectedPaymentMode && selectedPaymentMethod) {
                submitBtn.style.backgroundColor = '#28a745';
                submitBtn.disabled = false;
            } else {
                submitBtn.style.backgroundColor = '#ccc';
                submitBtn.disabled = true;
            }
        }
        
        document.getElementById('testForm').addEventListener('submit', function(e) {
            console.log('Form submitting with:');
            console.log('Payment Mode:', selectedPaymentMode);
            console.log('Payment Method:', selectedPaymentMethod);
            console.log('Hidden inputs:', {
                mode: document.getElementById('selectedPaymentMode').value,
                method: document.getElementById('selectedPaymentMethod').value
            });
            
            if (!selectedPaymentMode || !selectedPaymentMethod) {
                e.preventDefault();
                alert('Please select both payment mode and method');
                return false;
            }
        });
        
        // Initialize
        checkForm();
    </script>
</body>
</html>