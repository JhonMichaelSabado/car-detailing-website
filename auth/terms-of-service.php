<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Ride Revive</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0c0c0c 0%, #1a1a1a 50%, #2d2d2d 100%);
            min-height: 100vh;
            color: #e0e0e0;
            line-height: 1.6;
            contain: layout style;
        }

        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .shape {
            position: absolute;
            background: linear-gradient(45deg, rgba(255, 215, 0, 0.06), rgba(255, 215, 0, 0.03));
            border-radius: 50%;
            animation: float 10s infinite ease-in-out;
            will-change: transform, opacity;
        }

        .shape:nth-child(1) { width: 50px; height: 50px; top: 20%; left: 10%; animation-delay: 0s; }
        .shape:nth-child(2) { width: 70px; height: 70px; top: 60%; left: 85%; animation-delay: 3s; }
        .shape:nth-child(3) { width: 60px; height: 60px; top: 80%; left: 20%; animation-delay: 6s; }

        @keyframes float {
            0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg); opacity: 0.4; }
            50% { transform: translate3d(0, -20px, 0) rotate(180deg); opacity: 0.7; }
        }

        /* Header */
        .header {
            background: rgba(18, 18, 18, 0.95);
            backdrop-filter: blur(15px);
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-size: 2rem;
            font-weight: 900;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -1px;
        }

        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-links a {
            color: #e0e0e0;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            color: #FFD700;
            background: rgba(255, 215, 0, 0.1);
        }

        /* Main Container */
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 50px;
            padding: 40px;
            background: rgba(18, 18, 18, 0.8);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 215, 0, 0.2);
        }

        .page-header h1 {
            font-size: 3rem;
            font-weight: 700;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
        }

        .page-header p {
            font-size: 1.2rem;
            color: #bbb;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Content Sections */
        .content-section {
            background: rgba(18, 18, 18, 0.8);
            backdrop-filter: blur(15px);
            border-radius: 15px;
            padding: 40px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .content-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(255, 215, 0, 0.1);
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #FFD700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .section-title i {
            font-size: 1.5rem;
        }

        .section-content h3 {
            color: #FFA500;
            font-size: 1.3rem;
            margin: 25px 0 15px 0;
            font-weight: 600;
        }

        .section-content p {
            margin-bottom: 15px;
            color: #ccc;
            font-size: 1rem;
        }

        .section-content ul {
            margin: 15px 0;
            padding-left: 25px;
        }

        .section-content li {
            margin-bottom: 10px;
            color: #ccc;
        }

        .highlight {
            background: rgba(255, 215, 0, 0.1);
            color: #FFD700;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }

        .last-updated {
            text-align: center;
            margin: 50px 0;
            padding: 20px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 10px;
            border: 1px solid rgba(255, 215, 0, 0.3);
        }

        .last-updated strong {
            color: #FFD700;
        }

        /* Footer */
        .footer {
            background: rgba(18, 18, 18, 0.95);
            padding: 30px 0;
            text-align: center;
            border-top: 1px solid rgba(255, 215, 0, 0.2);
            margin-top: 50px;
        }

        .footer p {
            color: #bbb;
        }

        .footer a {
            color: #FFD700;
            text-decoration: none;
            font-weight: 600;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 20px;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
            }

            .page-header h1 {
                font-size: 2.5rem;
            }

            .container {
                padding: 20px 15px;
            }

            .content-section {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">RIDE REVIVE</div>
            <nav class="nav-links">
                <a href="../index.php"><i class="fas fa-home"></i> Home</a>
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
                <a href="privacy-policy.php"><i class="fas fa-shield-alt"></i> Privacy</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-file-contract"></i> Terms of Service</h1>
            <p>Please read these terms carefully before using our premium car detailing services</p>
        </div>

        <!-- Terms Content -->
        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-handshake"></i>
                Agreement to Terms
            </div>
            <div class="section-content">
                <p>By accessing and using <span class="highlight">Ride Revive</span> car detailing services, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.</p>
                <p>These terms apply to all visitors, users, and others who access or use our services.</p>
            </div>
        </div>

        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-car-wash"></i>
                Service Description
            </div>
            <div class="section-content">
                <p><span class="highlight">Ride Revive</span> provides premium car detailing services including but not limited to:</p>
                <ul>
                    <li>Exterior washing and waxing</li>
                    <li>Interior cleaning and conditioning</li>
                    <li>Paint correction and protection</li>
                    <li>Ceramic coating applications</li>
                    <li>Mobile detailing services</li>
                </ul>
                <p>All services are performed by trained professionals using industry-standard equipment and products.</p>
            </div>
        </div>

        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-calendar-check"></i>
                Booking and Scheduling
            </div>
            <div class="section-content">
                <h3>Appointment Requirements</h3>
                <p>All services must be booked in advance through our online platform or by phone. Walk-in services are subject to availability.</p>
                
                <h3>Cancellation Policy</h3>
                <ul>
                    <li><span class="highlight">24+ hours notice:</span> Full refund or free rescheduling</li>
                    <li><span class="highlight">12-24 hours notice:</span> 50% cancellation fee</li>
                    <li><span class="highlight">Less than 12 hours:</span> Full charge applies</li>
                </ul>
                
                <h3>Weather Policy</h3>
                <p>Services may be postponed due to inclement weather. We will reschedule at no additional charge.</p>
            </div>
        </div>

        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-dollar-sign"></i>
                Payment Terms
            </div>
            <div class="section-content">
                <h3>Payment Methods</h3>
                <p>We accept cash, credit cards, debit cards, and online payments through our secure platform.</p>
                
                <h3>Pricing</h3>
                <p>All prices are clearly displayed on our website and may vary based on vehicle size, condition, and selected services.</p>
                
                <h3>Deposits</h3>
                <p>Premium services may require a <span class="highlight">deposit</span> to secure your booking.</p>
            </div>
        </div>

        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-shield-alt"></i>
                Liability and Insurance
            </div>
            <div class="section-content">
                <h3>Our Responsibility</h3>
                <p>We maintain comprehensive liability insurance and take utmost care in handling your vehicle. However, pre-existing damage should be noted before service begins.</p>
                
                <h3>Customer Responsibility</h3>
                <p>Customers are responsible for:</p>
                <ul>
                    <li>Removing all personal items from the vehicle</li>
                    <li>Ensuring fuel tank is adequately filled</li>
                    <li>Disclosing any known mechanical issues</li>
                    <li>Providing accurate contact information</li>
                </ul>
                
                <h3>Limitation of Liability</h3>
                <p>Our liability is limited to the value of the service provided. We are not responsible for items left in vehicles.</p>
            </div>
        </div>

        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-user-check"></i>
                User Conduct
            </div>
            <div class="section-content">
                <p>Users agree to:</p>
                <ul>
                    <li>Provide accurate information when booking services</li>
                    <li>Treat our staff with respect and professionalism</li>
                    <li>Be present or available during scheduled service times</li>
                    <li>Not interfere with service operations</li>
                </ul>
                
                <h3>Prohibited Activities</h3>
                <p>The following activities are strictly prohibited:</p>
                <ul>
                    <li>Harassment of staff members</li>
                    <li>Damage to company property</li>
                    <li>Fraudulent payment methods</li>
                    <li>Misrepresentation of vehicle condition</li>
                </ul>
            </div>
        </div>

        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-certificate"></i>
                Quality Guarantee
            </div>
            <div class="section-content">
                <h3>Satisfaction Guarantee</h3>
                <p>We guarantee your satisfaction with our services. If you are not completely satisfied, please contact us within <span class="highlight">48 hours</span> of service completion.</p>
                
                <h3>Warranty</h3>
                <p>Selected services come with limited warranties as follows:</p>
                <ul>
                    <li><span class="highlight">Ceramic Coating:</span> 2-year warranty</li>
                    <li><span class="highlight">Paint Correction:</span> 6-month warranty</li>
                    <li><span class="highlight">Interior Protection:</span> 1-year warranty</li>
                </ul>
            </div>
        </div>

        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-edit"></i>
                Modifications to Terms
            </div>
            <div class="section-content">
                <p>We reserve the right to modify these terms at any time. Changes will be posted on our website and take effect immediately. Continued use of our services constitutes acceptance of modified terms.</p>
                <p>Major changes will be communicated to registered users via email.</p>
            </div>
        </div>

        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-gavel"></i>
                Governing Law
            </div>
            <div class="section-content">
                <p>These terms are governed by and construed in accordance with local laws. Any disputes arising from these terms will be resolved through binding arbitration.</p>
                <p>If any provision of these terms is found to be unenforceable, the remaining provisions will remain in full force and effect.</p>
            </div>
        </div>

        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-phone"></i>
                Contact Information
            </div>
            <div class="section-content">
                <p>For questions about these Terms of Service, please contact us:</p>
                <ul>
                    <li><strong>Email:</strong> legal@riderevive.com</li>
                    <li><strong>Phone:</strong> (555) 123-4567</li>
                    <li><strong>Address:</strong> 123 Detail Street, Auto City, AC 12345</li>
                    <li><strong>Hours:</strong> Monday - Saturday, 8:00 AM - 6:00 PM</li>
                </ul>
            </div>
        </div>

        <!-- Last Updated -->
        <div class="last-updated">
            <strong>Last Updated:</strong> September 30, 2025<br>
            <strong>Effective Date:</strong> September 30, 2025
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2025 Ride Revive. All rights reserved. | 
        <a href="privacy-policy.php">Privacy Policy</a> | 
        <a href="terms-of-service.php">Terms of Service</a></p>
    </footer>
</body>
</html>