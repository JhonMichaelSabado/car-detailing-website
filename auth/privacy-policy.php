<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Ride Revive</title>
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

        .privacy-notice {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 165, 0, 0.1));
            border: 2px solid rgba(255, 215, 0, 0.3);
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            text-align: center;
        }

        .privacy-notice h3 {
            color: #FFD700;
            margin-bottom: 15px;
            font-size: 1.4rem;
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
                <a href="terms-of-service.php"><i class="fas fa-file-contract"></i> Terms</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-shield-alt"></i> Privacy Policy</h1>
            <p>Your privacy is important to us. Learn how we collect, use, and protect your personal information.</p>
        </div>

        <!-- Privacy Notice -->
        <div class="privacy-notice">
            <h3><i class="fas fa-lock"></i> Your Privacy Matters</h3>
            <p>We are committed to protecting your privacy and ensuring the security of your personal information. This policy explains our practices regarding data collection and usage.</p>
        </div>

        <!-- Privacy Content -->
        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-info-circle"></i>
                Information We Collect
            </div>
            <div class="section-content">
                <h3>Personal Information</h3>
                <p>When you use our services, we may collect the following information:</p>
                <ul>
                    <li><span class="highlight">Contact Information:</span> Name, email address, phone number</li>
                    <li><span class="highlight">Vehicle Information:</span> Make, model, year, license plate</li>
                    <li><span class="highlight">Service History:</span> Previous bookings, preferences, notes</li>
                    <li><span class="highlight">Payment Information:</span> Billing address, payment method details</li>
                    <li><span class="highlight">Location Data:</span> Service address, GPS coordinates for mobile services</li>
                </ul>
                
                <h3>Automatically Collected Information</h3>
                <p>We automatically collect certain information when you visit our website:</p>
                <ul>
                    <li>IP address and device information</li>
                    <li>Browser type and version</li>
                    <li>Pages visited and time spent</li>
                    <li>Referring website information</li>
                    <li>Cookies and similar tracking technologies</li>
                </ul>
            </div>
        </div>

        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-cogs"></i>
                How We Use Your Information
            </div>
            <div class="section-content">
                <h3>Service Delivery</h3>
                <p>We use your information to:</p>
                <ul>
                    <li>Schedule and provide car detailing services</li>
                    <li>Communicate about appointments and service updates</li>
                    <li>Process payments and manage billing</li>
                    <li>Maintain service records and history</li>
                </ul>
                
                <h3>Business Operations</h3>
                <ul>
                    <li><span class="highlight">Customer Support:</span> Respond to inquiries and resolve issues</li>
                    <li><span class="highlight">Quality Improvement:</span> Analyze service performance and customer satisfaction</li>
                    <li><span class="highlight">Marketing:</span> Send promotional offers and service reminders (with consent)</li>
                    <li><span class="highlight">Legal Compliance:</span> Meet regulatory requirements and protect our rights</li>
                </ul>
            </div>
        </div>

        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-share-alt"></i>
                Information Sharing
            </div>
            <div class="section-content">
                <h3>We DO NOT sell your personal information</h3>
                <p>We may share your information only in the following circumstances:</p>
                
                <h3>Service Providers</h3>
                <ul>
                    <li><span class="highlight">Payment Processors:</span> To process transactions securely</li>
                    <li><span class="highlight">Cloud Storage:</span> To store data securely with encrypted services</li>
                    <li><span class="highlight">Communication Tools:</span> To send appointment reminders and notifications</li>
                </ul>
                
                <h3>Legal Requirements</h3>
                <p>We may disclose information when required by law, such as:</p>
                <ul>
                    <li>Response to court orders or legal process</li>
                    <li>Protection of our rights and property</li>
                    <li>Investigation of fraud or security issues</li>
                    <li>Emergency situations involving safety</li>
                </ul>
            </div>
        </div>

        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-cookie-bite"></i>
                Cookies and Tracking
            </div>
            <div class="section-content">
                <h3>Types of Cookies We Use</h3>
                <ul>
                    <li><span class="highlight">Essential Cookies:</span> Required for website functionality</li>
                    <li><span class="highlight">Performance Cookies:</span> Help us understand website usage</li>
                    <li><span class="highlight">Functional Cookies:</span> Remember your preferences</li>
                    <li><span class="highlight">Marketing Cookies:</span> Deliver relevant advertisements</li>
                </ul>
                
                <h3>Managing Cookies</h3>
                <p>You can control cookies through your browser settings. Note that disabling certain cookies may affect website functionality.</p>
            </div>
        </div>

        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-lock"></i>
                Data Security
            </div>
            <div class="section-content">
                <h3>Security Measures</h3>
                <p>We implement multiple layers of security to protect your information:</p>
                <ul>
                    <li><span class="highlight">Encryption:</span> All data is encrypted in transit and at rest</li>
                    <li><span class="highlight">Access Controls:</span> Limited access on a need-to-know basis</li>
                    <li><span class="highlight">Regular Audits:</span> Continuous monitoring and security assessments</li>
                    <li><span class="highlight">Secure Hosting:</span> Data stored in certified secure facilities</li>
                    <li><span class="highlight">Staff Training:</span> Regular privacy and security training for employees</li>
                </ul>
                
                <h3>Data Breach Response</h3>
                <p>In the unlikely event of a data breach, we will:</p>
                <ul>
                    <li>Immediately investigate and contain the breach</li>
                    <li>Notify affected customers within 72 hours</li>
                    <li>Cooperate with law enforcement and regulatory authorities</li>
                    <li>Implement additional security measures as needed</li>
                </ul>
            </div>
        </div>

        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-user-cog"></i>
                Your Rights and Choices
            </div>
            <div class="section-content">
                <h3>Data Subject Rights</h3>
                <p>You have the right to:</p>
                <ul>
                    <li><span class="highlight">Access:</span> Request a copy of your personal data</li>
                    <li><span class="highlight">Correction:</span> Update or correct inaccurate information</li>
                    <li><span class="highlight">Deletion:</span> Request deletion of your data (subject to legal requirements)</li>
                    <li><span class="highlight">Portability:</span> Receive your data in a machine-readable format</li>
                    <li><span class="highlight">Objection:</span> Object to certain uses of your data</li>
                </ul>
                
                <h3>Communication Preferences</h3>
                <p>You can control how we communicate with you:</p>
                <ul>
                    <li>Unsubscribe from marketing emails using the link provided</li>
                    <li>Update your notification preferences in your account settings</li>
                    <li>Contact us directly to modify your communication preferences</li>
                </ul>
            </div>
        </div>

        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-clock"></i>
                Data Retention
            </div>
            <div class="section-content">
                <h3>Retention Periods</h3>
                <p>We retain your information for different periods based on the type of data:</p>
                <ul>
                    <li><span class="highlight">Account Information:</span> Retained while your account is active</li>
                    <li><span class="highlight">Service Records:</span> Kept for 7 years for business and tax purposes</li>
                    <li><span class="highlight">Payment Data:</span> Retained according to financial regulations</li>
                    <li><span class="highlight">Marketing Data:</span> Deleted upon unsubscribe request</li>
                    <li><span class="highlight">Website Analytics:</span> Aggregated data retained for 26 months</li>
                </ul>
                
                <h3>Secure Deletion</h3>
                <p>When data is no longer needed, it is securely deleted using industry-standard methods to ensure it cannot be recovered.</p>
            </div>
        </div>

        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-baby"></i>
                Children's Privacy
            </div>
            <div class="section-content">
                <p>Our services are not intended for children under 18 years of age. We do not knowingly collect personal information from children under 18.</p>
                <p>If we become aware that we have collected personal information from a child under 18, we will take steps to delete such information promptly.</p>
                <p>If you believe we have collected information from a child under 18, please contact us immediately.</p>
            </div>
        </div>

        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-globe"></i>
                International Data Transfers
            </div>
            <div class="section-content">
                <p>Your information may be transferred to and processed in countries other than your own. We ensure that:</p>
                <ul>
                    <li>All transfers comply with applicable data protection laws</li>
                    <li>Adequate safeguards are in place to protect your data</li>
                    <li>Recipients are bound by appropriate data protection obligations</li>
                </ul>
            </div>
        </div>

        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-edit"></i>
                Changes to This Policy
            </div>
            <div class="section-content">
                <p>We may update this Privacy Policy from time to time to reflect changes in our practices or legal requirements. When we make changes:</p>
                <ul>
                    <li>We will post the updated policy on our website</li>
                    <li>We will update the "Last Modified" date</li>
                    <li>For significant changes, we will notify you by email</li>
                    <li>Your continued use constitutes acceptance of the updated policy</li>
                </ul>
            </div>
        </div>

        <div class="content-section">
            <div class="section-title">
                <i class="fas fa-phone"></i>
                Contact Us
            </div>
            <div class="section-content">
                <p>If you have any questions about this Privacy Policy or wish to exercise your rights, please contact us:</p>
                <ul>
                    <li><strong>Privacy Officer:</strong> privacy@riderevive.com</li>
                    <li><strong>Phone:</strong> (555) 123-4567</li>
                    <li><strong>Address:</strong> 123 Detail Street, Auto City, AC 12345</li>
                    <li><strong>Response Time:</strong> We will respond within 30 days</li>
                </ul>
                
                <h3>Data Protection Authority</h3>
                <p>You also have the right to lodge a complaint with your local data protection authority if you believe we have not addressed your concerns adequately.</p>
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