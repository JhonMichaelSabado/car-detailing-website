<?php
session_start();
require_once '../../includes/config.php';

// Set test user if not logged in (for development)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Test user
}

// Get booking and payment IDs
$booking_id = $_GET['booking_id'] ?? null;
$payment_id = $_GET['payment_id'] ?? null;

if (!$booking_id || !$payment_id) {
    header("Location: ../dashboard.php");
    exit();
}

try {
    // Get booking details
    $booking_stmt = $pdo->prepare("
        SELECT b.*, s.service_name, s.category, s.duration_minutes,
               u.first_name, u.last_name, u.email
        FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        JOIN users u ON b.user_id = u.user_id
        WHERE b.booking_id = ? AND b.user_id = ?
    ");
    $booking_stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $booking_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        header("Location: ../dashboard.php");
        exit();
    }
    
    // Get payment details
    $payment_stmt = $pdo->prepare("
        SELECT * FROM payments WHERE payment_id = ? AND booking_id = ?
    ");
    $payment_stmt->execute([$payment_id, $booking_id]);
    $payment = $payment_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate time remaining for auto-cancel
    $auto_cancel_time = new DateTime($booking['auto_cancel_after']);
    $now = new DateTime();
    $time_remaining = $auto_cancel_time > $now ? $auto_cancel_time->getTimestamp() - $now->getTimestamp() : 0;
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header("Location: ../dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Pending - Professional Car Detailing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .booking-progress {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
        }
        .progress-step {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            margin: 0 10px;
            font-weight: bold;
        }
        .progress-step.active {
            background: #fff;
            color: #667eea;
        }
        .progress-step.completed {
            background: #28a745;
            color: white;
        }
        .status-card {
            background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);
            color: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(255, 193, 7, 0.3);
        }
        .status-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .countdown-timer {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .timer-display {
            font-size: 2rem;
            font-weight: bold;
            color: #dc3545;
            text-align: center;
            margin: 15px 0;
        }
        .next-steps {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
        }
        .step-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .step-item:last-child {
            border-bottom: none;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: bold;
        }
        .booking-details {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
        }
        .btn-outline-danger {
            border-width: 2px;
            border-radius: 25px;
            padding: 12px 30px;
        }
        .alert-info {
            border-left: 4px solid #0dcaf0;
            background: #e7f3ff;
            border-radius: 0 10px 10px 0;
        }
    </style>
</head>
<body>
    <!-- Progress Header -->
    <div class="booking-progress">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="mb-0"><i class="fas fa-car-wash me-2"></i>Professional Car Detailing Booking</h4>
                    <p class="mb-0 opacity-75">Step 6 of 9: Booking Pending Approval</p>
                </div>
                <div class="col-md-4">
                    <div class="d-flex justify-content-end">
                        <div class="progress-step completed">1</div>
                        <div class="progress-step completed">2</div>
                        <div class="progress-step completed">3</div>
                        <div class="progress-step completed">4</div>
                        <div class="progress-step completed">5</div>
                        <div class="progress-step active">6</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Status Card -->
                <div class="status-card">
                    <div class="status-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <h2 class="mb-3">Booking Submitted Successfully!</h2>
                    <p class="lead mb-4">
                        Your booking request has been received and is pending admin approval.
                    </p>
                    <div class="h5">
                        Booking Reference: <strong><?= htmlspecialchars($booking['booking_reference']) ?></strong>
                    </div>
                </div>

                <!-- Auto-Cancel Timer -->
                <?php if ($time_remaining > 0): ?>
                <div class="countdown-timer">
                    <h5 class="text-center mb-3">
                        <i class="fas fa-clock me-2 text-danger"></i>
                        Automatic Cancellation Timer
                    </h5>
                    <div class="timer-display" id="countdownTimer">
                        --:--:--
                    </div>
                    <p class="text-center text-muted mb-0">
                        This booking will be automatically cancelled if not approved within the time limit.
                    </p>
                </div>
                <?php endif; ?>

                <!-- Important Information -->
                <div class="alert alert-info">
                    <h6 class="alert-heading">
                        <i class="fas fa-info-circle me-2"></i>
                        Important Information
                    </h6>
                    <ul class="mb-0">
                        <li>Your time slot is temporarily reserved for 10 minutes</li>
                        <li>Admin approval is required before payment processing</li>
                        <li>You will receive notifications about booking status updates</li>
                        <li>No payment will be charged until booking is confirmed</li>
                    </ul>
                </div>

                <!-- Next Steps -->
                <div class="next-steps">
                    <h5 class="mb-4">
                        <i class="fas fa-list-check me-2 text-primary"></i>
                        What Happens Next?
                    </h5>
                    
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <div>
                            <h6 class="mb-1">Admin Review</h6>
                            <p class="text-muted mb-0">Our team will review your booking request and check availability.</p>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-number">2</div>
                        <div>
                            <h6 class="mb-1">Booking Confirmation</h6>
                            <p class="text-muted mb-0">Once approved, you'll receive confirmation and payment instructions.</p>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-number">3</div>
                        <div>
                            <h6 class="mb-1">Payment Processing</h6>
                            <p class="text-muted mb-0">Complete the payment to secure your booking.</p>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-number">4</div>
                        <div>
                            <h6 class="mb-1">Service Delivery</h6>
                            <p class="text-muted mb-0">Our team will arrive at your location on the scheduled date and time.</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex justify-content-between">
                    <a href="../dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-home me-2"></i>Back to Dashboard
                    </a>
                    <div>
                        <a href="booking_details.php?id=<?= $booking_id ?>" class="btn btn-primary me-3">
                            <i class="fas fa-eye me-2"></i>View Full Details
                        </a>
                        <button type="button" class="btn btn-outline-danger" onclick="cancelBooking()">
                            <i class="fas fa-times me-2"></i>Cancel Booking
                        </button>
                    </div>
                </div>
            </div>

            <!-- Booking Summary Sidebar -->
            <div class="col-lg-4">
                <div class="booking-details sticky-top" style="top: 20px;">
                    <h5 class="mb-4">
                        <i class="fas fa-receipt me-2 text-primary"></i>
                        Booking Summary
                    </h5>
                    
                    <div class="detail-row">
                        <span>Reference:</span>
                        <strong><?= htmlspecialchars($booking['booking_reference']) ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Status:</span>
                        <span class="badge bg-warning">🟡 Pending</span>
                    </div>
                    <div class="detail-row">
                        <span>Service:</span>
                        <span><?= htmlspecialchars($booking['service_name']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Vehicle Size:</span>
                        <span class="text-capitalize"><?= htmlspecialchars($booking['vehicle_size']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Date:</span>
                        <span><?= date('M j, Y', strtotime($booking['booking_date'])) ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Time:</span>
                        <span><?= date('g:i A', strtotime($booking['booking_time'])) ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Duration:</span>
                        <span><?= $booking['estimated_duration'] ?> minutes</span>
                    </div>
                    
                    <hr>
                    
                    <div class="detail-row">
                        <span>Total Amount:</span>
                        <strong class="text-primary">₱<?= number_format($booking['total_amount'], 2) ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Payment Mode:</span>
                        <span><?= $booking['payment_mode'] === 'deposit_50' ? '50% Deposit' : 'Full Payment' ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Amount Due Now:</span>
                        <strong class="text-success">₱<?= number_format($booking['deposit_amount'], 2) ?></strong>
                    </div>
                    
                    <hr>
                    
                    <div class="text-center">
                        <h6 class="text-muted">Customer Service</h6>
                        <p class="mb-2">
                            <i class="fas fa-phone me-2 text-primary"></i>
                            +63 (2) 123-4567
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-envelope me-2 text-primary"></i>
                            support@cardetailing.com
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Countdown timer
        const timeRemaining = <?= $time_remaining ?>;
        
        if (timeRemaining > 0) {
            const countdownElement = document.getElementById('countdownTimer');
            let remaining = timeRemaining;
            
            function updateTimer() {
                const hours = Math.floor(remaining / 3600);
                const minutes = Math.floor((remaining % 3600) / 60);
                const seconds = remaining % 60;
                
                countdownElement.textContent = 
                    String(hours).padStart(2, '0') + ':' +
                    String(minutes).padStart(2, '0') + ':' +
                    String(seconds).padStart(2, '0');
                
                if (remaining <= 0) {
                    countdownElement.textContent = 'EXPIRED';
                    countdownElement.classList.add('text-danger');
                    // Optionally redirect or show expired message
                    return;
                }
                
                remaining--;
            }
            
            updateTimer();
            setInterval(updateTimer, 1000);
        }
        
        // Auto-refresh page every 30 seconds to check for status updates
        setInterval(function() {
            // Check booking status via AJAX
            fetch('check_booking_status.php?booking_id=<?= $booking_id ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.status !== 'pending') {
                        // Booking status changed, reload page
                        window.location.reload();
                    }
                })
                .catch(error => console.log('Status check failed:', error));
        }, 30000);
        
        function cancelBooking() {
            if (confirm('Are you sure you want to cancel this booking?')) {
                window.location.href = 'cancel_booking.php?booking_id=<?= $booking_id ?>';
            }
        }
        
        // Show success message with auto-hide
        window.addEventListener('load', function() {
            // You could add a toast notification here if desired
        });
    </script>
</body>
</html>