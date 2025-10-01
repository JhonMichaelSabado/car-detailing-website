<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $database = new Database();
        $db = $database->getConnection();

        $query = "SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW() AND is_active = TRUE";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $token);
        $stmt->execute();

        error_log("Reset token validation (POST): token=$token, rowCount=" . $stmt->rowCount());

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_id = $row['id'];

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $update_query = "UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(1, $hashed_password);
            $update_stmt->bindParam(2, $user_id);

            if ($update_stmt->execute()) {
                error_log("Password reset successful for user ID: $user_id");
                $success = 'Password reset successfully. You can now log in with your new password.';
            } else {
                error_log("Failed to update password for user ID: $user_id");
                $error = 'Failed to reset password. Please try again.';
            }
        } else {
            error_log("Invalid token during POST reset: $token");
            $error = 'Invalid or expired reset link.';
        }
    }
} else {
    // Check if token is valid on GET
    if (!empty($token)) {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW() AND is_active = TRUE";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $token);
    $stmt->execute();

    error_log("Reset token validation (GET): token=$token, rowCount=" . $stmt->rowCount());

    if ($stmt->rowCount() == 0) {
        $error = 'Invalid or expired reset link.';
    } else {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Valid token found for user ID: " . $row['id']);
    }
    } else {
        $error = 'No reset token provided.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reset Password - Ride Revive</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 50%, #1a1a1a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('../images/backg.png') center/cover no-repeat;
            opacity: 0.1;
            z-index: -1;
        }

        /* Main Container */
        .main-container {
            display: flex;
            max-width: 1000px;
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
            font-size: 3rem;
            font-weight: 900;
            letter-spacing: 2px;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3), 0 0 10px rgba(0, 0, 0, 0.2); }
            to { text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3), 0 0 20px rgba(0, 0, 0, 0.4); }
        }

        .tagline {
            font-size: 1.1rem;
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
            font-size: 2.2rem;
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
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            color: #888;
            font-size: 1rem;
            z-index: 2;
            transition: color 0.3s ease;
        }

        .form-input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
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
        }

        .form-input:focus + .input-icon {
            color: #FFD700;
        }

        .form-input::placeholder {
            color: #888;
        }

        /* Primary Button */
        .btn-primary {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #000;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-error {
            background: rgba(255, 68, 68, 0.1);
            color: #ff6b6b;
            border: 1px solid rgba(255, 68, 68, 0.2);
        }

        .alert-success {
            background: rgba(68, 255, 68, 0.1);
            color: #51cf66;
            border: 1px solid rgba(68, 255, 68, 0.2);
        }

        /* Footer Links */
        .form-footer {
            text-align: center;
            margin-top: 30px;
            color: #888;
        }

        .form-footer a {
            color: #FFD700;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .form-footer a:hover {
            color: #FFA500;
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
                margin: 20px;
                min-height: auto;
            }

            .brand-panel {
                padding: 30px 20px;
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
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Left Panel - Branding -->
        <div class="brand-panel">
            <div class="brand-content">
                <h1 class="logo">RIDE REVIVE</h1>
                <p class="tagline">Premium Car Detailing</p>
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-shield-alt"></i>
                        <span>Secure Password Reset</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-lock"></i>
                        <span>Encrypted & Protected</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-user-check"></i>
                        <span>Verified Account Access</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Reset Form -->
        <div class="form-panel">
            <div class="form-header">
                <h2 class="form-title">Reset Password</h2>
                <p class="form-subtitle">Enter your new password below</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <div class="form-footer">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i> Back to Sign In
                    </a>
                </div>
            <?php elseif (empty($error)): ?>
                <form action="" method="POST">
                    <div class="form-group">
                        <div class="input-container">
                            <input type="password" id="password" name="password" class="form-input" placeholder="Enter new password" required minlength="6">
                            <i class="fas fa-lock input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-container">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Confirm new password" required minlength="6">
                            <i class="fas fa-lock input-icon"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                </form>

                <div class="form-footer">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i> Back to Sign In
                    </a>
                </div>
            <?php else: ?>
                <div class="form-footer">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i> Back to Sign In
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
