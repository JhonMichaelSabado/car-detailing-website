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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent-color: #FFD700;
            --accent-hover: #FFC107;
            --bg-primary: #000000;
            --bg-secondary: #0a0a0a;
            --bg-card: #1d1d1f;
            --text-primary: #f5f5f7;
            --text-secondary: #a1a1a6;
            --text-tertiary: #86868b;
            --border-subtle: #2d2d2f;
            --shadow-ambient: rgba(0, 0, 0, 0.7);
            --shadow-glow: rgba(255, 215, 0, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'SF Pro Display', system-ui, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.5;
            font-weight: 400;
            overflow-x: hidden;
        }

        /* Apple-style Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-subtle);
            padding: 16px 0;
            z-index: 1000;
            transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: var(--text-primary);
            font-size: 21px;
            font-weight: 600;
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header h1 i {
            color: var(--accent-color);
            font-size: 20px;
        }

        .back-btn {
            background: transparent;
            color: var(--accent-color);
            padding: 8px 16px;
            border: 1px solid var(--accent-color);
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: -0.008em;
            transition: all 0.25s cubic-bezier(0.4, 0.0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: var(--accent-color);
            color: var(--bg-primary);
            transform: translateY(-1px);
            box-shadow: 0 4px 20px var(--shadow-glow);
        }

        /* Hero Section */
        .hero {
            padding: 120px 0 80px;
            text-align: center;
            opacity: 0;
            animation: fadeInUp 1.2s cubic-bezier(0.4, 0.0, 0.2, 1) 0.3s forwards;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 32px;
        }

        .hero h2 {
            font-size: clamp(48px, 8vw, 76px);
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1.1;
            margin-bottom: 24px;
            background: linear-gradient(135deg, var(--text-primary), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: 21px;
            font-weight: 400;
            color: var(--text-secondary);
            letter-spacing: -0.01em;
            line-height: 1.4;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 32px;
        }

        /* Steps Section */
        .workflow-steps {
            display: flex;
            flex-direction: column;
            gap: 120px;
            margin: 120px 0;
        }

        .step {
            opacity: 0;
            transform: translateY(60px);
            transition: all 0.8s cubic-bezier(0.4, 0.0, 0.2, 1);
        }

        .step.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .step-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
            min-height: 400px;
        }

        .step:nth-child(even) .step-container {
            direction: rtl;
        }

        .step:nth-child(even) .step-content,
        .step:nth-child(even) .step-visual {
            direction: ltr;
        }

        .step-content {
            padding: 0;
        }

        .step-header {
            margin-bottom: 32px;
        }

        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: var(--accent-color);
            color: var(--bg-primary);
            border-radius: 50%;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .step-title {
            font-size: clamp(32px, 5vw, 48px);
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.1;
            color: var(--text-primary);
            margin-bottom: 16px;
        }

        .step-description {
            font-size: 19px;
            font-weight: 400;
            color: var(--text-secondary);
            letter-spacing: -0.01em;
            line-height: 1.5;
            margin-bottom: 32px;
        }

        .step-details {
            background: linear-gradient(135deg, var(--bg-card), rgba(255, 215, 0, 0.05));
            border-radius: 16px;
            padding: 32px;
            border: 1px solid var(--border-subtle);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .step-details h4 {
            font-size: 17px;
            font-weight: 600;
            color: var(--accent-color);
            margin-bottom: 20px;
            letter-spacing: -0.01em;
        }

        .step-list {
            list-style: none;
            padding: 0;
        }

        .step-list li {
            padding: 12px 0;
            position: relative;
            padding-left: 32px;
            font-size: 15px;
            color: var(--text-secondary);
            letter-spacing: -0.01em;
            line-height: 1.4;
        }

        .step-list li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 20px;
            width: 8px;
            height: 8px;
            background: var(--accent-color);
            border-radius: 50%;
        }

        .step-visual {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 300px;
            background: linear-gradient(135deg, var(--bg-card), var(--bg-secondary));
            border-radius: 24px;
            border: 1px solid var(--border-subtle);
            position: relative;
            overflow: hidden;
        }

        .step-visual::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 215, 0, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        .step-icon {
            font-size: 64px;
            color: var(--accent-color);
            z-index: 1;
            position: relative;
        }

        /* Payment Options */
        .payment-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin: 32px 0;
        }

        .payment-option {
            background: linear-gradient(135deg, var(--bg-card), rgba(255, 215, 0, 0.05));
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 32px;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
        }

        .payment-option:hover {
            transform: translateY(-4px);
            border-color: var(--accent-color);
            box-shadow: 0 20px 40px var(--shadow-glow);
        }

        .payment-option h4 {
            color: var(--accent-color);
            margin-bottom: 16px;
            font-size: 17px;
            font-weight: 600;
            letter-spacing: -0.01em;
        }

        .price-display {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 16px 0;
            letter-spacing: -0.02em;
        }

        /* Admin Section */
        .admin-section {
            background: linear-gradient(135deg, var(--bg-card), var(--bg-secondary));
            border: 1px solid var(--border-subtle);
            border-radius: 20px;
            padding: 48px;
            margin: 48px 0;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .admin-section h3 {
            color: var(--accent-color);
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: -0.01em;
        }

        .status-flow {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 32px 0;
            flex-wrap: wrap;
            gap: 16px;
        }

        .status-item {
            background: var(--bg-secondary);
            padding: 20px 24px;
            border-radius: 12px;
            border: 1px solid var(--border-subtle);
            text-align: center;
            flex: 1;
            min-width: 140px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            letter-spacing: -0.01em;
            transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
        }

        .status-item:hover {
            border-color: var(--accent-color);
            color: var(--text-primary);
        }

        .arrow {
            color: var(--accent-color);
            font-size: 16px;
            margin: 0 8px;
        }

        /* CTA Button */
        .start-booking {
            background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
            color: var(--bg-primary);
            padding: 20px 48px;
            border: none;
            border-radius: 50px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            letter-spacing: -0.01em;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin: 80px auto 120px;
            transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
            box-shadow: 0 4px 20px rgba(255, 215, 0, 0.3);
        }

        .start-booking:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 40px rgba(255, 215, 0, 0.4);
        }

        .cta-container {
            text-align: center;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .step-container {
                grid-template-columns: 1fr;
                gap: 48px;
                text-align: center;
            }

            .step:nth-child(even) .step-container {
                direction: ltr;
            }

            .workflow-steps {
                gap: 80px;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                padding: 0 20px;
            }

            .hero {
                padding: 100px 0 60px;
            }

            .hero-content {
                padding: 0 20px;
            }

            .container {
                padding: 0 20px;
            }

            .step-details,
            .admin-section {
                padding: 24px;
            }

            .status-flow {
                flex-direction: column;
                gap: 12px;
            }

            .arrow {
                transform: rotate(90deg);
            }

            .start-booking {
                padding: 16px 32px;
                font-size: 16px;
            }

            .workflow-steps {
                gap: 60px;
                margin: 80px 0;
            }
        }

        @media (max-width: 480px) {
            .hero h2 {
                font-size: 36px;
            }

            .hero p {
                font-size: 17px;
            }

            .step-title {
                font-size: 28px;
            }

            .step-description {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>
                <i class="fas fa-map-marked-alt"></i>
                Complete Booking Guide
            </h1>
            <a href="dashboard_CLEAN.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="hero">
        <div class="hero-content">
            <h2>Your Journey to Premium Care</h2>
            <p>Experience seamless booking with our step-by-step guide designed for your convenience and peace of mind.</p>
        </div>
    </div>

    <div class="container">
        <div class="workflow-steps">
            <!-- Step 1: Select Service -->
            <div class="step">
                <div class="step-container">
                    <div class="step-content">
                        <div class="step-header">
                            <div class="step-number">1</div>
                            <div class="step-title">Select a Product or Service</div>
                            <div class="step-description">
                                Browse our premium car detailing services and choose the perfect package for your vehicle.
                            </div>
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
                        <p style="margin-top: 24px; font-weight: 500; color: var(--text-primary);"><strong>Action:</strong> Click "Book Now" on your preferred service to continue.</p>
                    </div>
                    <div class="step-visual">
                        <div class="step-icon">🛒</div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Choose Schedule -->
            <div class="step">
                <div class="step-container">
                    <div class="step-content">
                        <div class="step-header">
                            <div class="step-number">2</div>
                            <div class="step-title">Choose Schedule & Options</div>
                            <div class="step-description">
                                Select your preferred date, time, and vehicle details for the service.
                            </div>
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
                        <p style="margin-top: 24px; font-weight: 500; color: var(--text-primary);"><strong>Note:</strong> System automatically checks availability for your selected slot.</p>
                    </div>
                    <div class="step-visual">
                        <div class="step-icon">📅</div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Booking Summary -->
            <div class="step">
                <div class="step-container">
                    <div class="step-content">
                        <div class="step-header">
                            <div class="step-number">3</div>
                            <div class="step-title">Booking Summary Page</div>
                            <div class="step-description">
                                Review your booking details and understand the payment requirements.
                            </div>
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
                    <div class="step-visual">
                        <div class="step-icon">🧾</div>
                    </div>
                </div>
            </div>

            <!-- Step 4: Payment Method -->
            <div class="step">
                <div class="step-container">
                    <div class="step-content">
                        <div class="step-header">
                            <div class="step-number">4</div>
                            <div class="step-title">Payment Method Selection</div>
                            <div class="step-description">
                                Choose your preferred payment option and method.
                            </div>
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
                    <div class="step-visual">
                        <div class="step-icon">💳</div>
                    </div>
                </div>
            </div>

            <!-- Step 5: Process Payment -->
            <div class="step">
                <div class="step-container">
                    <div class="step-content">
                        <div class="step-header">
                            <div class="step-number">5</div>
                            <div class="step-title">Admin Management</div>
                            <div class="step-description">
                                Our admin team manages and confirms your booking.
                            </div>
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
                    <div class="step-visual">
                        <div class="step-icon">🔍‍♂️</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="cta-container">
            <a href="dashboard_CLEAN.php" class="start-booking">
                <i class="fas fa-rocket"></i>
                Start Your Booking Journey
            </a>
        </div>
    </div>

    <script>
        // Apple-style scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        // Observe all steps
        document.querySelectorAll('.step').forEach((step) => {
            observer.observe(step);
        });

        // Smooth header background on scroll
        let lastScrollTop = 0;
        window.addEventListener('scroll', () => {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const header = document.querySelector('.header');
            
            if (scrollTop > 100) {
                header.style.background = 'rgba(0, 0, 0, 0.95)';
            } else {
                header.style.background = 'rgba(0, 0, 0, 0.8)';
            }
            
            lastScrollTop = scrollTop;
        });

        // Preload animations
        window.addEventListener('load', () => {
            document.body.style.opacity = '1';
        });
    </script>
</body>
</html>
