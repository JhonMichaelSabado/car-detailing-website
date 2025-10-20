<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Session Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .session-data { 
            background: #f8f9fa; 
            border: 1px solid #dee2e6; 
            border-radius: 8px; 
            padding: 15px; 
            margin: 10px 0; 
        }
        .step-indicator {
            padding: 20px;
            margin: 10px 0;
            border-radius: 8px;
        }
        .step-complete { background: #d4edda; border: 1px solid #c3e6cb; }
        .step-incomplete { background: #f8d7da; border: 1px solid #f5c6cb; }
        .step-current { background: #fff3cd; border: 1px solid #ffeaa7; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>🔍 Booking Session Debug</h1>
        <p class="text-muted">This page shows all data stored in the booking session</p>
        
        <!-- Current Session Step -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">📍 Current Session Status</h5>
            </div>
            <div class="card-body">
                <p><strong>Current Step:</strong> <?= $_SESSION['booking_step'] ?? 'Not Set' ?></p>
                <p><strong>User ID:</strong> <?= $_SESSION['user_id'] ?? 'Not Set' ?></p>
                <p><strong>Session ID:</strong> <?= session_id() ?></p>
            </div>
        </div>
        
        <!-- Step Progress Indicators -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">📋 Booking Flow Progress</h5>
            </div>
            <div class="card-body">
                <?php
                $steps = [
                    1 => 'Service Selection',
                    2 => 'Location & Travel Fee',
                    3 => 'Date & Time',
                    4 => 'Payment Mode & Method',
                    5 => 'Review & Confirmation'
                ];
                
                $currentStep = $_SESSION['booking_step'] ?? 0;
                $bookingFlow = $_SESSION['booking_flow'] ?? [];
                
                foreach ($steps as $stepNum => $stepName) {
                    $status = 'incomplete';
                    $data = '';
                    
                    switch ($stepNum) {
                        case 1:
                            if (isset($bookingFlow['service_id'])) {
                                $status = 'complete';
                                $data = "Service ID: {$bookingFlow['service_id']}, Vehicle: {$bookingFlow['vehicle_size']}";
                            }
                            break;
                        case 2:
                            if (isset($bookingFlow['location'])) {
                                $status = 'complete';
                                $data = "Location: {$bookingFlow['location']}, Travel Fee: ₱{$bookingFlow['travel_fee']}";
                            }
                            break;
                        case 3:
                            if (isset($bookingFlow['booking_date'])) {
                                $status = 'complete';
                                $data = "Date: {$bookingFlow['booking_date']}, Time: {$bookingFlow['booking_time']}";
                            }
                            break;
                        case 4:
                            if (isset($bookingFlow['payment_mode'])) {
                                $status = 'complete';
                                $data = "Mode: {$bookingFlow['payment_mode']}, Method: {$bookingFlow['payment_method']}";
                            }
                            break;
                        case 5:
                            if ($currentStep >= 5) {
                                $status = 'complete';
                                $data = "Review completed";
                            }
                            break;
                    }
                    
                    if ($stepNum == $currentStep) $status = 'current';
                    
                    $class = "step-$status";
                    echo "<div class='step-indicator $class'>";
                    echo "<h6>Step $stepNum: $stepName</h6>";
                    echo "<small>$data</small>";
                    echo "</div>";
                }
                ?>
            </div>
        </div>
        
        <!-- Complete Session Data -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">💾 Complete Session Data</h5>
            </div>
            <div class="card-body">
                <h6>Booking Flow Data:</h6>
                <div class="session-data">
                    <pre><?php 
                    if (isset($_SESSION['booking_flow'])) {
                        print_r($_SESSION['booking_flow']);
                    } else {
                        echo "No booking flow data found";
                    }
                    ?></pre>
                </div>
                
                <h6>Complete Session:</h6>
                <div class="session-data">
                    <pre><?php print_r($_SESSION); ?></pre>
                </div>
            </div>
        </div>
        
        <!-- Quick Navigation -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">🚀 Quick Navigation</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Booking Steps:</h6>
                        <a href="step1_service_selection.php" class="btn btn-outline-primary btn-sm me-2 mb-2">Step 1: Services</a>
                        <a href="step2_location.php" class="btn btn-outline-primary btn-sm me-2 mb-2">Step 2: Location</a>
                        <a href="step3_datetime.php" class="btn btn-outline-primary btn-sm me-2 mb-2">Step 3: Date/Time</a>
                        <a href="step4_payment_mode.php" class="btn btn-outline-primary btn-sm me-2 mb-2">Step 4: Payment</a>
                        <a href="step5_review.php" class="btn btn-outline-primary btn-sm me-2 mb-2">Step 5: Review</a>
                    </div>
                    <div class="col-md-6">
                        <h6>Debug Tools:</h6>
                        <a href="payment_test.php" class="btn btn-outline-warning btn-sm me-2 mb-2">Payment Test</a>
                        <a href="javascript:location.reload()" class="btn btn-outline-secondary btn-sm me-2 mb-2">Refresh Debug</a>
                        <a href="?clear=1" class="btn btn-outline-danger btn-sm me-2 mb-2">Clear Session</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    // Clear session if requested
    if (isset($_GET['clear'])) {
        session_destroy();
        echo "<script>alert('Session cleared!'); window.location.href = 'session_debug.php';</script>";
    }
    ?>
</body>
</html>