<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Guide - Ride Revive</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --accent-color: #FFD700;
            --bg-primary: #1a1a1a;
            --bg-secondary: #2a2a2a;
            --text-primary: #ffffff;
            --text-secondary: #cccccc;
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
        }

        .header {
            background: var(--bg-secondary);
            padding: 20px;
            border-bottom: 1px solid #444;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: var(--accent-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .back-btn {
            background: var(--accent-color);
            color: #000;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #e6c200;
            transform: translateY(-2px);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .workflow-steps {
            display: grid;
            gap: 30px;
            margin-top: 30px;
        }

        .step {
            background: var(--bg-secondary);
            border-radius: 16px;
            padding: 30px;
            border: 2px solid #333;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .step::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-color), #e6c200);
        }

        .step:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.2);
            border-color: var(--accent-color);
        }

        .step-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .step-number {
            background: var(--accent-color);
            color: #000;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }

        .step-icon {
            font-size: 30px;
            color: var(--accent-color);
        }

        .step-title {
            flex: 1;
            font-size: 24px;
            font-weight: 600;
            color: var(--accent-color);
        }

        .step-content {
            margin-left: 65px;
        }

        .step-description {
            font-size: 16px;
            margin-bottom: 20px;
            color: var(--text-secondary);
        }

        .step-details {
            background: rgba(255, 215, 0, 0.1);
            border-left: 4px solid var(--accent-color);
            padding: 15px 20px;
            border-radius: 0 8px 8px 0;
            margin: 20px 0;
        }

        .step-list {
            list-style: none;
            padding: 0;
        }

        .step-list li {
            padding: 8px 0;
            position: relative;
            padding-left: 25px;
        }

        .step-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--accent-color);
            font-weight: bold;
        }

        .payment-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .payment-option {
            background: rgba(255, 215, 0, 0.05);
            border: 2px solid var(--accent-color);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }

        .payment-option h4 {
            color: var(--accent-color);
            margin-bottom: 10px;
            font-size: 18px;
        }

        .price-display {
            font-size: 24px;
            font-weight: bold;
            color: var(--text-primary);
            margin: 10px 0;
        }

        .admin-section {
            background: linear-gradient(135deg, #2a2a2a, #1a1a1a);
            border: 2px solid #444;
            border-radius: 16px;
            padding: 30px;
            margin-top: 40px;
        }

        .admin-section h3 {
            color: var(--accent-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-flow {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            flex-wrap: wrap;
            gap: 15px;
        }

        .status-item {
            background: var(--bg-secondary);
            padding: 15px 20px;
            border-radius: 8px;
            border: 2px solid #444;
            text-align: center;
            flex: 1;
            min-width: 150px;
        }

        .status-item.active {
            border-color: var(--accent-color);
            background: rgba(255, 215, 0, 0.1);
        }

        .arrow {
            color: var(--accent-color);
            font-size: 20px;
            margin: 0 10px;
        }

        .start-booking {
            background: linear-gradient(135deg, var(--accent-color), #e6c200);
            color: #000;
            padding: 20px 40px;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            margin: 40px auto;
            text-decoration: none;
            text-align: center;
        }

        .start-booking:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.4);
        }

        @media (max-width: 768px) {
            .step-content {
                margin-left: 0;
            }
            
            .step-header {
                flex-direction: column;
                text-align: center;
            }
            
            .status-flow {
                flex-direction: column;
            }
            
            .arrow {
                transform: rotate(90deg);
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>
            <i class="fas fa-map-marked-alt"></i>
            Complete Booking Guide
        </h1>
        <a href="dashboard_CLEAN.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="container">
        <div class="workflow-steps">
            <!-- Step 1: Select Service -->
            <div class="step">
                <div class="step-header">
                    <div class="step-number">1</div>
                    <div class="step-icon">🛒</div>
                    <div class="step-title">Select a Product or Service</div>
                </div>
                <div class="step-content">
                    <div class="step-description">
                        Browse our premium car detailing services and choose the perfect package for your vehicle.
                    </div>
                    <div class="step-details">
                        <h4>What you'll see:</h4>
                        <ul class="step-list">
                            <li>Service name and detailed description</li>
                            <li>Pricing for different vehicle sizes (Small, Medium, Large)</li>
                            <li>Service inclusions and duration</li>
                            <li>Before/after photos and testimonials</li>
                        </ul>
                    </div>
                    <p><strong>Action:</strong> Click "Book Now" on your preferred service to continue.</p>
                </div>
            </div>

            <!-- Step 2: Choose Schedule -->
            <div class="step">
                <div class="step-header">
                    <div class="step-number">2</div>
                    <div class="step-icon">📅</div>
                    <div class="step-title">Choose Schedule & Options</div>
                </div>
                <div class="step-content">
                    <div class="step-description">
                        Select your preferred date, time, and vehicle details for the service.
                    </div>
                    <div class="step-details">
                        <h4>You'll need to provide:</h4>
                        <ul class="step-list">
                            <li>Vehicle size (affects pricing)</li>
                            <li>Preferred date (minimum 1 day advance)</li>
                            <li>Preferred time slot (8 AM - 4 PM)</li>
                            <li>Vehicle details (make, model, color)</li>
                            <li>Special requests (optional)</li>
                        </ul>
                    </div>
                    <p><strong>Note:</strong> System automatically checks availability for your selected slot.</p>
                </div>
            </div>

            <!-- Step 3: Booking Summary -->
            <div class="step">
                <div class="step-header">
                    <div class="step-number">3</div>
                    <div class="step-icon">🧾</div>
                    <div class="step-title">Booking Summary Page</div>
                </div>
                <div class="step-content">
                    <div class="step-description">
                        Review your booking details and understand the payment requirements.
                    </div>
                    <div class="step-details">
                        <h4>Summary includes:</h4>
                        <ul class="step-list">
                            <li>Selected service and price</li>
                            <li>Vehicle size and total cost</li>
                            <li>Scheduled date and time</li>
                            <li>Payment rules and requirements</li>
                        </ul>
                    </div>
                    <div class="payment-options">
                        <div class="payment-option">
                            <h4>📋 Important Notice</h4>
                            <p>Payment is required to secure your booking and prevent no-shows</p>
                            <div class="price-display">50% minimum required</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 4: Payment Method -->
            <div class="step">
                <div class="step-header">
                    <div class="step-number">4</div>
                    <div class="step-icon">💳</div>
                    <div class="step-title">Payment Method Selection</div>
                </div>
                <div class="step-content">
                    <div class="step-description">
                        Choose your preferred payment option and method.
                    </div>
                    <div class="payment-options">
                        <div class="payment-option">
                            <h4>💰 Partial Payment (Recommended)</h4>
                            <div class="price-display">Pay 50% Now</div>
                            <p>Remaining 50% on service completion</p>
                            <small>Perfect for securing your slot</small>
                        </div>
                        <div class="payment-option">
                            <h4>💳 Full Payment (Convenient)</h4>
                            <div class="price-display">Pay 100% Now</div>
                            <p>No money needed in person</p>
                            <small>Complete convenience</small>
                        </div>
                    </div>
                    <div class="step-details">
                        <h4>Available Payment Methods:</h4>
                        <ul class="step-list">
                            <li>GCash (Most popular in Philippines)</li>
                            <li>Bank Transfer (For larger amounts)</li>
                            <li>Cash on Arrival (Traditional option)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Step 5: Process Payment -->
            <div class="step">
                <div class="step-header">
                    <div class="step-number">5</div>
                    <div class="step-icon">🔐</div>
                    <div class="step-title">Process Payment</div>
                </div>
                <div class="step-content">
                    <div class="step-description">
                        Complete your payment securely through your chosen method.
                    </div>
                    <div class="step-details">
                        <h4>Payment Process:</h4>
                        <ul class="step-list">
                            <li>If GCash/Bank Transfer → Follow payment instructions</li>
                            <li>If Cash on Arrival → Confirm your commitment</li>
                            <li>Upload proof of payment (for online payments)</li>
                            <li>Receive payment confirmation</li>
                        </ul>
                    </div>
                    <p><strong>Security:</strong> All payments are processed securely with encryption.</p>
                </div>
            </div>

            <!-- Step 6: Confirmation -->
            <div class="step">
                <div class="step-header">
                    <div class="step-number">6</div>
                    <div class="step-icon">✅</div>
                    <div class="step-title">Booking Confirmation</div>
                </div>
                <div class="step-content">
                    <div class="step-description">
                        Your booking is confirmed and saved in our system.
                    </div>
                    <div class="step-details">
                        <h4>What happens next:</h4>
                        <ul class="step-list">
                            <li>Booking saved with unique reference number</li>
                            <li>Status changed to "Pending Approval"</li>
                            <li>Email notification sent to you</li>
                            <li>Dashboard updated with booking details</li>
                        </ul>
                    </div>
                    <div class="status-flow">
                        <div class="status-item active">Payment Received</div>
                        <div class="arrow">→</div>
                        <div class="status-item">Pending Approval</div>
                        <div class="arrow">→</div>
                        <div class="status-item">Confirmed</div>
                    </div>
                </div>
            </div>

            <!-- Step 7: Admin Side -->
            <div class="step">
                <div class="step-header">
                    <div class="step-number">7</div>
                    <div class="step-icon">🧍‍♂️</div>
                    <div class="step-title">Admin Management</div>
                </div>
                <div class="step-content">
                    <div class="step-description">
                        Our admin team manages and confirms your booking.
                    </div>
                    <div class="admin-section">
                        <h3><i class="fas fa-user-shield"></i> Admin Actions</h3>
                        <ul class="step-list">
                            <li>Receives immediate notification of new booking</li>
                            <li>Reviews booking details and payment status</li>
                            <li>Approves or requests modifications</li>
                            <li>Sends final confirmation to customer</li>
                            <li>Collects remaining payment (if partial) on service day</li>
                        </ul>
                        
                        <div class="status-flow">
                            <div class="status-item">New Booking</div>
                            <div class="arrow">→</div>
                            <div class="status-item">Under Review</div>
                            <div class="arrow">→</div>
                            <div class="status-item">Confirmed</div>
                            <div class="arrow">→</div>
                            <div class="status-item">Service Day</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <a href="dashboard_CLEAN.php" class="start-booking">
            <i class="fas fa-rocket"></i>
            Start Your Booking Journey
        </a>
    </div>
</body>
</html>