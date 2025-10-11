<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit();
}

// Check if payment data exists
if (!isset($_SESSION['pending_payment'])) {
    header("Location: dashboard_CLEAN.php");
    exit();
}

$payment_data = $_SESSION['pending_payment'];
$booking_id = $_GET['booking_id'] ?? $payment_data['booking_id'];

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/database_functions.php';

$database = new Database();
$db = $database->getConnection();
$carDB = new CarDetailingDB($db);

// Get service details
$service = $carDB->getService($payment_data['service_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Ride Revive</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0c0c0c 0%, #1a1a1a 50%, #2d2d2d 100%);
            min-height: 100vh;
            color: #ffffff;
            padding: 20px;
        }

        .payment-container {
            max-width: 800px;
            margin: 0 auto;
            background: linear-gradient(135deg, #1a1a1a, #2a2a2a);
            border-radius: 20px;
            border: 1px solid #333;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }

        .payment-header {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #1a1a1a;
            padding: 30px;
            text-align: center;
        }

        .payment-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .payment-header p {
            font-size: 16px;
            opacity: 0.8;
        }

        .payment-content {
            padding: 40px;
        }

        .booking-summary {
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .booking-summary h3 {
            color: #FFD700;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 18px;
            color: #FFD700;
        }

        .payment-methods {
            margin-bottom: 30px;
        }

        .payment-methods h3 {
            color: #FFD700;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .payment-method {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid #444;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .payment-method:hover {
            border-color: #FFD700;
            background: rgba(255, 215, 0, 0.1);
        }

        .payment-method.selected {
            border-color: #FFD700;
            background: rgba(255, 215, 0, 0.15);
        }

        .payment-method input {
            display: none;
        }

        .payment-icon {
            font-size: 24px;
            color: #FFD700;
            width: 40px;
            text-align: center;
        }

        .payment-info h4 {
            color: white;
            margin-bottom: 5px;
        }

        .payment-info p {
            color: #ccc;
            font-size: 14px;
        }

        .payment-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #1a1a1a;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid #444;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .security-note {
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid rgba(76, 175, 80, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
        }

        .security-note i {
            color: #4CAF50;
            margin-right: 10px;
        }

        @media (max-width: 768px) {
            .payment-content {
                padding: 20px;
            }
            
            .payment-actions {
                flex-direction: column;
            }
            
            .summary-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <h1><i class="fas fa-credit-card"></i> Secure Payment</h1>
            <p>Complete your booking payment</p>
        </div>

        <div class="payment-content">
            <!-- Booking Summary -->
            <div class="booking-summary">
                <h3><i class="fas fa-receipt"></i> Booking Summary</h3>
                <div class="summary-row">
                    <span>Service:</span>
                    <span><?php echo htmlspecialchars($service['service_name']); ?></span>
                </div>
                <div class="summary-row">
                    <span>Vehicle Size:</span>
                    <span><?php echo ucfirst($payment_data['vehicle_size']); ?></span>
                </div>
                <div class="summary-row">
                    <span>Booking ID:</span>
                    <span>#<?php echo $booking_id; ?></span>
                </div>
                <div class="summary-row">
                    <span>Total Service Cost:</span>
                    <span>₱<?php echo number_format($payment_data['total_amount'], 2); ?></span>
                </div>
                <?php if ($payment_data['payment_type'] === 'partial'): ?>
                    <div class="summary-row">
                        <span>Paying Now (50%):</span>
                        <span>₱<?php echo number_format($payment_data['payment_amount'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Remaining (Pay on service):</span>
                        <span>₱<?php echo number_format($payment_data['total_amount'] - $payment_data['payment_amount'], 2); ?></span>
                    </div>
                <?php endif; ?>
                <div class="summary-row">
                    <span><strong>Amount to Pay Now:</strong></span>
                    <span><strong>₱<?php echo number_format($payment_data['payment_amount'], 2); ?></strong></span>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="payment-methods">
                <h3><i class="fas fa-wallet"></i> Payment Method</h3>
                
                <label class="payment-method" onclick="selectPaymentMethod('gcash')">
                    <input type="radio" name="payment_method" value="gcash" required>
                    <div class="payment-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="payment-info">
                        <h4>GCash</h4>
                        <p>Pay securely with your GCash account</p>
                    </div>
                </label>

                <label class="payment-method" onclick="selectPaymentMethod('bank')">
                    <input type="radio" name="payment_method" value="bank" required>
                    <div class="payment-icon">
                        <i class="fas fa-university"></i>
                    </div>
                    <div class="payment-info">
                        <h4>Bank Transfer</h4>
                        <p>Direct bank transfer - BPI, BDO, Metrobank</p>
                    </div>
                </label>

                <label class="payment-method" onclick="selectPaymentMethod('cash')">
                    <input type="radio" name="payment_method" value="cash" required>
                    <div class="payment-icon">
                        <i class="fas fa-money-bill"></i>
                    </div>
                    <div class="payment-info">
                        <h4>Cash on Arrival</h4>
                        <p>Pay cash when service provider arrives</p>
                    </div>
                </label>
            </div>

            <!-- Payment Actions -->
            <div class="payment-actions">
                <a href="dashboard_CLEAN.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
                <button onclick="processPayment()" class="btn btn-primary" id="payButton" disabled>
                    <i class="fas fa-lock"></i>
                    Process Payment
                </button>
            </div>

            <!-- Security Note -->
            <div class="security-note">
                <i class="fas fa-shield-alt"></i>
                Your payment information is secure and encrypted. We never store your payment details.
            </div>
        </div>
    </div>

    <script>
        let selectedPaymentMethod = null;

        function selectPaymentMethod(method) {
            selectedPaymentMethod = method;
            
            // Remove selected class from all methods
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Add selected class to clicked method
            event.currentTarget.classList.add('selected');
            
            // Check radio button
            document.querySelector(`input[value="${method}"]`).checked = true;
            
            // Enable pay button
            document.getElementById('payButton').disabled = false;
        }

        function processPayment() {
            if (!selectedPaymentMethod) {
                alert('Please select a payment method.');
                return;
            }

            const payButton = document.getElementById('payButton');
            payButton.disabled = true;
            payButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            // Simulate payment processing
            const formData = new FormData();
            formData.append('booking_id', '<?php echo $booking_id; ?>');
            formData.append('payment_method', selectedPaymentMethod);
            formData.append('payment_amount', '<?php echo $payment_data['payment_amount']; ?>');
            formData.append('payment_type', '<?php echo $payment_data['payment_type']; ?>');

            fetch('process_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Payment successful! Your booking has been confirmed.');
                    window.location.href = 'dashboard_CLEAN.php';
                } else {
                    alert('Payment failed: ' + data.message);
                    payButton.disabled = false;
                    payButton.innerHTML = '<i class="fas fa-lock"></i> Process Payment';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing payment.');
                payButton.disabled = false;
                payButton.innerHTML = '<i class="fas fa-lock"></i> Process Payment';
            });
        }
    </script>
</body>
</html>