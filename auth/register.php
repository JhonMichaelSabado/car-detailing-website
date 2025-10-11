<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create Account - Ride Revive</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            contain: layout style;
        }

        /* Animated Background Elements */
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
            background: linear-gradient(45deg, rgba(255, 215, 0, 0.08), rgba(255, 215, 0, 0.04));
            border-radius: 50%;
            animation: float 8s infinite ease-in-out;
            will-change: transform, opacity;
        }

        .shape:nth-child(1) { width: 60px; height: 60px; top: 20%; left: 10%; animation-delay: 0s; }
        .shape:nth-child(2) { width: 80px; height: 80px; top: 60%; left: 80%; animation-delay: 2s; }
        .shape:nth-child(3) { width: 70px; height: 70px; top: 80%; left: 20%; animation-delay: 4s; }

        @keyframes float {
            0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg); opacity: 0.4; }
            50% { transform: translate3d(0, -15px, 0) rotate(180deg); opacity: 0.7; }
        }

        /* Main Container */
        .main-container {
            display: flex;
            max-width: 1200px;
            width: 90%;
            min-height: 600px;
            background: rgba(18, 18, 18, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 215, 0, 0.1);
            overflow: hidden;
            animation: slideIn 0.6s ease-out;
            will-change: transform, opacity;
        }

        @keyframes slideIn {
            from { 
                opacity: 0; 
                transform: translateY(30px) scale(0.95); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0) scale(1); 
            }
        }

        /* Left Panel - Branding */
        .brand-panel {
            flex: 1;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 50%, #FF8C00 100%);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 40px;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('../images/backg.png') center/cover;
            opacity: 0.2;
            z-index: 1;
        }

        .brand-content {
            position: relative;
            z-index: 2;
            color: #000;
        }

        .logo {
            font-size: 3.5rem;
            font-weight: 900;
            letter-spacing: 3px;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3), 0 0 10px rgba(0, 0, 0, 0.2); }
            to { text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3), 0 0 20px rgba(0, 0, 0, 0.4); }
        }

        .tagline {
            font-size: 1.2rem;
            font-weight: 300;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .features {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-width: 300px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
            opacity: 0.8;
        }

        .feature i {
            font-size: 1.2rem;
            width: 24px;
        }

        /* Right Panel - Form */
        .form-panel {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: rgba(34, 34, 34, 0.9);
            backdrop-filter: blur(10px);
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #FFD700;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-subtitle {
            color: #ccc;
            font-size: 1rem;
            font-weight: 300;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .input-container {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 15px 20px 15px 50px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 215, 0, 0.2);
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .form-input:focus {
            outline: none;
            border-color: #FFD700;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
            transform: translateY(-2px);
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
            transition: opacity 0.3s ease;
        }

        .form-input:focus::placeholder {
            opacity: 0;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #FFD700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus + .input-icon {
            color: #FFA500;
            transform: translateY(-50%) scale(1.1);
        }

        /* Button Styles */
        .btn-primary {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            border: none;
            border-radius: 12px;
            color: #000;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.4);
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        /* Google Sign-In Button */
        .btn-google {
            width: 100%;
            padding: 15px 20px;
            background: white;
            color: #333;
            border: 2px solid #ddd;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px 0;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-google:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            border-color: #ccc;
            text-decoration: none;
            color: #333;
        }

        .btn-google:active {
            transform: translateY(0);
        }

        /* Links */
        .form-links {
            text-align: center;
            margin-top: 25px;
        }

        .form-link {
            color: #FFD700;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        .form-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #FFD700;
            transition: width 0.3s ease;
        }

        .form-link:hover::after {
            width: 100%;
        }

        .form-link:hover {
            color: #FFA500;
        }

        .divider {
            margin: 30px 0;
            text-align: center;
            color: #666;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(255, 215, 0, 0.2);
        }

        .divider span {
            background: rgba(34, 34, 34, 0.9);
            padding: 0 20px;
            position: relative;
        }

        /* Messages */
        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            font-weight: 500;
            animation: slideDown 0.5s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .error-message {
            background: linear-gradient(135deg, rgba(244, 67, 54, 0.9), rgba(211, 47, 47, 0.9));
            color: #fff;
            border-left: 4px solid #f44336;
        }

        .success-message {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.9), rgba(56, 142, 60, 0.9));
            color: #fff;
            border-left: 4px solid #4caf50;
        }

        /* Forgot Password Section */
        .form-section {
            opacity: 0;
            transform: translateX(20px);
            transition: all 0.3s ease;
            position: absolute;
            width: 100%;
            top: 0;
            left: 0;
            visibility: hidden;
        }

        .form-section.active {
            opacity: 1;
            transform: translateX(0);
            position: relative;
            visibility: visible;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #FFD700;
            text-decoration: none;
            font-size: 0.9rem;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            color: #FFA500;
            transform: translateX(-5px);
        }

        /* Loading State */
        .btn-loading {
            position: relative;
            color: transparent !important;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid transparent;
            border-top: 2px solid #000;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
                width: 95%;
                margin: 20px;
            }

            .brand-panel {
                padding: 30px 20px;
                min-height: 200px;
            }

            .logo {
                font-size: 2.5rem;
            }

            .form-panel {
                padding: 40px 30px;
            }

            .form-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .main-container {
                margin: 10px;
            }

            .form-panel {
                padding: 30px 20px;
            }

            .logo {
                font-size: 2rem;
            }

            .form-title {
                font-size: 1.8rem;
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

    <div class="main-container">
        <!-- Brand Panel -->
        <div class="brand-panel">
            <div class="brand-content">
                <div class="logo">RIDE REVIVE</div>
                <div class="tagline">Premium Car Detailing Experience</div>
                
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-car"></i>
                        <span>Professional Car Care</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-shield-alt"></i>
                        <span>Quality Guaranteed</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-clock"></i>
                        <span>24/7 Online Booking</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-star"></i>
                        <span>5-Star Reviews</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Panel -->
        <div class="form-panel">
            <!-- Messages -->
            <?php if (isset($_GET['error'])): ?>
                <div class="message error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php 
                    switch($_GET['error']) {
                        case 'invalid_credentials':
                            echo 'Invalid email or password. Please try again.';
                            break;
                        case 'user_not_found':
                            echo 'No account found with this email address.';
                            break;
                        case 'email_not_found':
                            echo 'Email address not found in our system.';
                            break;
                        case 'reset_failed':
                            echo 'Failed to send reset link. Please try again.';
                            break;
                        default:
                            echo 'Login failed. Please check your credentials.';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="message success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                    switch($_GET['success']) {
                        case 'reset_link_sent':
                            echo 'Password reset link sent to your email!';
                            break;
                        case 'registration_complete':
                            echo 'Registration complete! Welcome to Ride Revive.';
                            break;
                        default:
                            echo 'Success!';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <div id="register-section" class="form-section active">
                <div class="form-header">
                    <h1 class="form-title">Create Your Account</h1>
                    <p class="form-subtitle">Start your journey with premium car detailing</p>
                </div>

                <form action="authenticate.php" method="POST" onsubmit="return validateRegistration()">
                    <input type="hidden" name="action" value="register">
                    
                    <div style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <div class="input-container">
                                <input type="text" class="form-input" name="first_name" placeholder="First name" required>
                                <i class="fas fa-user input-icon"></i>
                            </div>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <div class="input-container">
                                <input type="text" class="form-input" name="last_name" placeholder="Last name" required>
                                <i class="fas fa-user input-icon"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-container">
                            <input type="email" class="form-input" name="email" placeholder="Enter your email" required>
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-container">
                            <input type="tel" class="form-input" name="phone" placeholder="Enter your phone number" required>
                            <i class="fas fa-phone input-icon"></i>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <div class="input-container">
                                <input type="password" class="form-input" name="password" placeholder="Create password" required minlength="8" oninput="checkPasswordStrength()">
                                <i class="fas fa-lock input-icon"></i>
                            </div>
                            <div class="password-strength" id="password-strength" style="margin-top: 5px; height: 4px; background: #333; border-radius: 2px; overflow: hidden;">
                                <div class="password-strength-bar" style="height: 100%; width: 0%; background: #ff4444; transition: all 0.3s ease;"></div>
                            </div>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <div class="input-container">
                                <input type="password" class="form-input" name="confirm_password" placeholder="Confirm password" required minlength="8">
                                <i class="fas fa-lock input-icon"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="checkbox-group" style="margin: 20px 0; display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" id="terms" name="terms" required style="width: auto; margin: 0;">
                        <label for="terms" style="color: #ccc; font-size: 14px; margin: 0;">
                            I agree to the <a href="terms-of-service.php" style="color: #FFD700;" target="_blank">Terms of Service</a> and 
                            <a href="privacy-policy.php" style="color: #FFD700;" target="_blank">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        <span>Create Account</span>
                    </button>
                </form>

                <!-- Google Sign-In Button -->
                <?php
                $google_config = require_once __DIR__ . '/../config/google_config.php';
                $google_auth_url = "https://accounts.google.com/o/oauth2/auth?client_id={$google_config['client_id']}&redirect_uri={$google_config['redirect_uri']}&scope=email%20profile&response_type=code&access_type=offline";
                ?>
                <a href="<?php echo $google_auth_url; ?>" class="btn-google">
                    <svg width="18" height="18" viewBox="0 0 24 24" style="margin-right: 10px;">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Continue with Google
                </a>

                <div class="divider">
                    <span>or</span>
                </div>

                <div class="form-links">
                    <p style="color: #ccc;">
                        Already have an account? 
                        <a href="login.php" class="form-link">Sign In</a>
                    </p>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Registration validation
        function validateRegistration() {
            const firstName = document.querySelector('input[name="first_name"]').value.trim();
            const lastName = document.querySelector('input[name="last_name"]').value.trim();
            const email = document.querySelector('input[name="email"]').value.trim();
            const phone = document.querySelector('input[name="phone"]').value.trim();
            const password = document.querySelector('input[name="password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            const terms = document.querySelector('input[name="terms"]').checked;

            if (firstName.length < 2) {
                showError('First name must be at least 2 characters long');
                return false;
            }
            
            if (lastName.length < 2) {
                showError('Last name must be at least 2 characters long');
                return false;
            }

            if (!isValidEmail(email)) {
                showError('Please enter a valid email address');
                return false;
            }

            const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
            if (!phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''))) {
                showError('Please enter a valid phone number');
                return false;
            }

            if (password.length < 8) {
                showError('Password must be at least 8 characters long');
                return false;
            }

            if (password !== confirmPassword) {
                showError('Passwords do not match');
                return false;
            }

            let strength = 0;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            if (strength < 3) {
                showError('Password is too weak. Include uppercase, lowercase, numbers, and special characters');
                return false;
            }

            if (!terms) {
                showError('You must accept the Terms of Service and Privacy Policy');
                return false;
            }

            return true;
        }

        function checkPasswordStrength() {
            const password = document.querySelector('input[name="password"]').value;
            const strengthIndicator = document.getElementById('password-strength');
            const strengthBar = strengthIndicator.querySelector('.password-strength-bar');
            
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            if (strength <= 2) {
                strengthBar.style.width = '33%';
                strengthBar.style.background = '#ff4444';
            } else if (strength <= 4) {
                strengthBar.style.width = '66%';
                strengthBar.style.background = '#ffaa00';
            } else {
                strengthBar.style.width = '100%';
                strengthBar.style.background = '#44ff44';
            }
        }

        // Real-time password confirmation check
        document.addEventListener('DOMContentLoaded', function() {
            const confirmPassword = document.querySelector('input[name="confirm_password"]');
            if (confirmPassword) {
                confirmPassword.addEventListener('input', function() {
                    const password = document.querySelector('input[name="password"]').value;
                    const confirmPassword = this.value;
                    
                    if (confirmPassword && password !== confirmPassword) {
                        this.style.borderColor = '#ff4444';
                    } else {
                        this.style.borderColor = '';
                    }
                });
            }
        });

        // Enhanced form handling with loading states
        function handleSubmit(form) {
            const button = form.querySelector('.btn-primary');
            const span = button.querySelector('span');
            
            // Add loading state
            button.classList.add('btn-loading');
            button.disabled = true;
            
            // Validate form
            const email = form.querySelector('input[name="email"]').value.trim();
            const password = form.querySelector('input[name="password"]');
            
            if (!email) {
                showError('Please enter your email address');
                resetButton(button);
                return false;
            }
            
            if (!isValidEmail(email)) {
                showError('Please enter a valid email address');
                resetButton(button);
                return false;
            }
            
            if (password && password.value.length < 6) {
                showError('Password must be at least 6 characters long');
                resetButton(button);
                return false;
            }
            
            return true;
        }

        function resetButton(button) {
            setTimeout(() => {
                button.classList.remove('btn-loading');
                button.disabled = false;
            }, 500);
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function showError(message) {
            // Remove existing error messages
            const existingError = document.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }
            
            // Create new error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'message error-message';
            errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
            
            // Insert at the top of form panel
            const formPanel = document.querySelector('.form-panel');
            formPanel.insertBefore(errorDiv, formPanel.firstChild);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                errorDiv.remove();
            }, 5000);
        }

        function showResetForm() {
            document.getElementById('login-section').classList.remove('active');
            setTimeout(() => {
                document.getElementById('reset-section').classList.add('active');
            }, 150);
        }

        function showLoginForm() {
            document.getElementById('reset-section').classList.remove('active');
            setTimeout(() => {
                document.getElementById('login-section').classList.add('active');
            }, 150);
        }

        // Add input focus animations
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(msg => {
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
