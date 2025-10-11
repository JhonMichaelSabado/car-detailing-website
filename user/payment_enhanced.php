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
    <title>Payment Processing - Ride Revive</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --accent-color: #FFD700;
            --bg-primary: #1a1a1a;
            --bg-secondary: #2a2a2a;
            --text-primary: #ffffff;
            --text-secondary: #cccccc;
            --success-color: #4CAF50;
            --warning-color: #ff9800;
            --error-color: #f44336;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
        }

        .payment-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .payment-header {
            background: var(--bg-secondary);
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
            border: 2px solid #333;
        }

        .payment-header h1 {
            color: var(--accent-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .booking-summary {
            background: var(--bg-secondary);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border: 2px solid #333;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #444;
        }

        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 18px;
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid var(--accent-color);
        }

        .payment-methods {
            background: var(--bg-secondary);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border: 2px solid #333;
        }

        .payment-methods h3 {
            color: var(--accent-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .payment-method {
            display: flex;
            align-items: center;
            padding: 20px;
            margin-bottom: 15px;
            border: 2px solid #444;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-method:hover {
            border-color: var(--accent-color);
            background: rgba(255, 215, 0, 0.1);
        }

        .payment-method.selected {
            border-color: var(--accent-color);
            background: rgba(255, 215, 0, 0.2);
        }

        .payment-method input {
            display: none;
        }

        .payment-icon {
            width: 60px;
            text-align: center;
            margin-right: 20px;
        }

        .payment-icon i {
            font-size: 24px;
            color: var(--accent-color);
        }

        .payment-info h4 {
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .payment-info p {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .payment-instructions {
            background: var(--bg-secondary);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border: 2px solid #333;
        }

        .payment-instruction-panel {
            padding: 20px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 8px;
            border-left: 4px solid var(--accent-color);
        }

        .payment-instruction-panel h4 {
            color: var(--accent-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .qr-section {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .qr-placeholder {
            background: #fff;
            color: #333;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .qr-placeholder i {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .payment-details {
            background: rgba(0,0,0,0.3);
            padding: 15px;
            border-radius: 8px;
        }

        .payment-details p {
            margin-bottom: 8px;
        }

        .bank-accounts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .bank-account {
            background: rgba(0,0,0,0.3);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--accent-color);
        }

        .bank-account h5 {
            color: var(--accent-color);
            margin-bottom: 10px;
        }

        .cash-info {
            background: rgba(33, 150, 243, 0.2);
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .cash-info i {
            color: #2196f3;
            font-size: 20px;
        }

        .cash-note {
            background: rgba(255, 152, 0, 0.2);
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 4px solid var(--warning-color);
        }

        .proof-upload {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #444;
        }

        .proof-upload h4 {
            color: var(--accent-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .upload-area {
            border: 2px dashed #444;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .upload-area:hover {
            border-color: var(--accent-color);
            background: rgba(255, 215, 0, 0.1);
        }

        .upload-area input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .upload-content i {
            font-size: 48px;
            color: var(--accent-color);
            margin-bottom: 15px;
        }

        .upload-preview {
            text-align: center;
            padding: 20px;
        }

        .upload-preview img {
            max-width: 300px;
            max-height: 200px;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .remove-file {
            background: var(--error-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
        }

        .payment-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-bottom: 30px;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--accent-color);
            color: #000;
        }

        .btn-primary:hover {
            background: #e6c200;
            transform: translateY(-2px);
        }

        .btn-primary:disabled {
            background: #666;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: #555;
            color: white;
        }

        .btn-secondary:hover {
            background: #666;
        }

        .security-note {
            background: rgba(76, 175, 80, 0.2);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }

        .security-note i {
            margin-right: 10px;
        }

        @media (max-width: 768px) {
            .qr-section {
                grid-template-columns: 1fr;
            }
            
            .bank-accounts {
                grid-template-columns: 1fr;
            }
            
            .payment-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <!-- Payment Header -->
        <div class="payment-header">
            <h1>
                <i class="fas fa-credit-card"></i>
                Complete Your Payment
            </h1>
            <p>Secure your booking with payment confirmation</p>
        </div>

        <!-- Booking Summary -->
        <div class="booking-summary">
            <h3><i class="fas fa-file-invoice"></i> Booking Summary</h3>
            <div class="summary-row">
                <span>Service:</span>
                <span><?php echo htmlspecialchars($service['service_name']); ?></span>
            </div>
            <div class="summary-row">
                <span>Vehicle Size:</span>
                <span><?php echo ucfirst($payment_data['vehicle_size']); ?></span>
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

        <!-- Payment Instructions (Hidden by default) -->
        <div id="payment-instructions" class="payment-instructions" style="display: none;">
            <!-- GCash Instructions -->
            <div id="gcash-instructions" class="payment-instruction-panel" style="display: none;">
                <h4><i class="fas fa-mobile-alt"></i> GCash Payment Instructions</h4>
                <div class="instruction-content">
                    <div class="qr-section">
                        <div class="qr-placeholder">
                            <i class="fas fa-qrcode"></i>
                            <p><strong>GCash QR</strong></p>
                            <small>Scan with GCash app</small>
                        </div>
                        <div class="payment-details">
                            <p><strong>GCash Number:</strong> 09123456789</p>
                            <p><strong>Account Name:</strong> Ride Revive Services</p>
                            <p><strong>Amount:</strong> ₱<?php echo number_format($payment_data['payment_amount'], 2); ?></p>
                            <p><strong>Reference:</strong> RR-<?php echo $booking_id; ?>-<?php echo date('mdY'); ?></p>
                        </div>
                    </div>
                    <ol>
                        <li>Open your GCash app</li>
                        <li>Go to "Send Money" → "To Mobile Number"</li>
                        <li>Enter: <strong>09123456789</strong></li>
                        <li>Enter amount: <strong>₱<?php echo number_format($payment_data['payment_amount'], 2); ?></strong></li>
                        <li>Add reference: <strong>RR-<?php echo $booking_id; ?>-<?php echo date('mdY'); ?></strong></li>
                        <li>Complete the transaction</li>
                        <li>Take a screenshot of the confirmation</li>
                        <li>Upload the screenshot below</li>
                    </ol>
                </div>
            </div>

            <!-- Bank Transfer Instructions -->
            <div id="bank-instructions" class="payment-instruction-panel" style="display: none;">
                <h4><i class="fas fa-university"></i> Bank Transfer Instructions</h4>
                <div class="instruction-content">
                    <div class="bank-accounts">
                        <div class="bank-account">
                            <h5>BPI (Bank of the Philippine Islands)</h5>
                            <p><strong>Account Number:</strong> 1234-5678-90</p>
                            <p><strong>Account Name:</strong> Ride Revive Services</p>
                        </div>
                        <div class="bank-account">
                            <h5>BDO (Banco de Oro)</h5>
                            <p><strong>Account Number:</strong> 9876-5432-10</p>
                            <p><strong>Account Name:</strong> Ride Revive Services</p>
                        </div>
                    </div>
                    <ol>
                        <li>Transfer <strong>₱<?php echo number_format($payment_data['payment_amount'], 2); ?></strong> to any of the accounts above</li>
                        <li>Use reference: <strong>RR-<?php echo $booking_id; ?>-<?php echo date('mdY'); ?></strong></li>
                        <li>Keep your deposit slip or transaction receipt</li>
                        <li>Upload a photo of your receipt below</li>
                    </ol>
                </div>
            </div>

            <!-- Cash Payment Instructions -->
            <div id="cash-instructions" class="payment-instruction-panel" style="display: none;">
                <h4><i class="fas fa-money-bill"></i> Cash Payment Instructions</h4>
                <div class="instruction-content">
                    <div class="cash-info">
                        <i class="fas fa-info-circle"></i>
                        <p>You've selected to pay cash when our service provider arrives.</p>
                    </div>
                    <ul>
                        <li>Prepare exact amount: <strong>₱<?php echo number_format($payment_data['payment_amount'], 2); ?></strong></li>
                        <li>Payment due when service provider arrives</li>
                        <li>Official receipt will be provided</li>
                        <li>Your booking will be marked as "Confirmed - Cash Payment"</li>
                    </ul>
                    <div class="cash-note">
                        <strong>Note:</strong> Cash payments help secure your booking. Please have the exact amount ready.
                    </div>
                </div>
            </div>

            <!-- Proof of Payment Upload -->
            <div id="proof-upload" class="proof-upload" style="display: none;">
                <h4><i class="fas fa-upload"></i> Upload Proof of Payment</h4>
                <div class="upload-area" id="upload-area">
                    <input type="file" id="proof-file" accept="image/*" onchange="handleFileUpload(this)">
                    <div class="upload-content">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Drag & drop your screenshot here, or click to browse</p>
                        <small>Accepted formats: JPG, PNG, GIF (Max 5MB)</small>
                    </div>
                </div>
                <div id="upload-preview" class="upload-preview" style="display: none;">
                    <img id="preview-image" src="" alt="Proof of payment">
                    <p id="file-name"></p>
                    <button type="button" onclick="removeFile()" class="remove-file">
                        <i class="fas fa-times"></i> Remove
                    </button>
                </div>
            </div>
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

    <script>
        let selectedPaymentMethod = null;
        let uploadedFile = null;

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
            
            // Show payment instructions
            showPaymentInstructions(method);
            
            // Enable pay button for cash, require upload for others
            updatePayButton();
        }

        function showPaymentInstructions(method) {
            // Hide all instruction panels
            document.querySelectorAll('.payment-instruction-panel').forEach(el => {
                el.style.display = 'none';
            });
            
            // Show instructions container
            document.getElementById('payment-instructions').style.display = 'block';
            
            // Show specific instructions
            document.getElementById(method + '-instructions').style.display = 'block';
            
            // Show upload for online payments, hide for cash
            const proofUpload = document.getElementById('proof-upload');
            if (method === 'cash') {
                proofUpload.style.display = 'none';
            } else {
                proofUpload.style.display = 'block';
            }
        }

        function handleFileUpload(input) {
            const file = input.files[0];
            if (!file) return;
            
            // Validate file size (5MB limit)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                input.value = '';
                return;
            }
            
            // Validate file type
            if (!file.type.startsWith('image/')) {
                alert('Please upload an image file');
                input.value = '';
                return;
            }
            
            uploadedFile = file;
            
            // Preview the image
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview-image').src = e.target.result;
                document.getElementById('file-name').textContent = file.name;
                document.getElementById('upload-area').style.display = 'none';
                document.getElementById('upload-preview').style.display = 'block';
            };
            reader.readAsDataURL(file);
            
            updatePayButton();
        }

        function removeFile() {
            uploadedFile = null;
            document.getElementById('proof-file').value = '';
            document.getElementById('upload-area').style.display = 'block';
            document.getElementById('upload-preview').style.display = 'none';
            updatePayButton();
        }

        function updatePayButton() {
            const payButton = document.getElementById('payButton');
            
            if (selectedPaymentMethod === 'cash') {
                // Cash payment - no upload required
                payButton.disabled = false;
                payButton.innerHTML = '<i class="fas fa-check"></i> Confirm Cash Payment';
            } else if (uploadedFile) {
                // Online payment with proof uploaded
                payButton.disabled = false;
                payButton.innerHTML = '<i class="fas fa-credit-card"></i> Submit Payment';
            } else {
                // Online payment but no proof yet
                payButton.disabled = true;
                payButton.innerHTML = '<i class="fas fa-upload"></i> Upload Proof First';
            }
        }

        function processPayment() {
            if (!selectedPaymentMethod) {
                alert('Please select a payment method.');
                return;
            }

            if (selectedPaymentMethod !== 'cash' && !uploadedFile) {
                alert('Please upload proof of payment.');
                return;
            }

            const payButton = document.getElementById('payButton');
            payButton.disabled = true;
            payButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            const formData = new FormData();
            formData.append('booking_id', '<?php echo $booking_id; ?>');
            formData.append('payment_method', selectedPaymentMethod);
            formData.append('payment_amount', '<?php echo $payment_data['payment_amount']; ?>');
            formData.append('payment_type', '<?php echo $payment_data['payment_type']; ?>');
            formData.append('payment_reference', 'RR-<?php echo $booking_id; ?>-<?php echo date('mdY'); ?>');
            
            if (uploadedFile) {
                formData.append('payment_proof', uploadedFile);
            }

            fetch('process_payment_enhanced.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message with details
                    let successMessage = 'Payment processed successfully!\n\n';
                    successMessage += 'Booking ID: ' + data.booking_id + '\n';
                    successMessage += 'Payment Reference: ' + data.payment_reference + '\n';
                    successMessage += 'Status: ' + data.status + '\n\n';
                    successMessage += 'You will receive a confirmation email shortly.';
                    
                    alert(successMessage);
                    window.location.href = 'dashboard_CLEAN.php?booking_confirmed=' + data.booking_id;
                } else {
                    alert('Payment failed: ' + data.message);
                    payButton.disabled = false;
                    updatePayButton();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing payment.');
                payButton.disabled = false;
                updatePayButton();
            });
        }

        // Drag and drop functionality
        const uploadArea = document.getElementById('upload-area');
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = 'var(--accent-color)';
            this.style.background = 'rgba(255, 215, 0, 0.1)';
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '#444';
            this.style.background = 'transparent';
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#444';
            this.style.background = 'transparent';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('proof-file').files = files;
                handleFileUpload(document.getElementById('proof-file'));
            }
        });
    </script>
</body>
</html>