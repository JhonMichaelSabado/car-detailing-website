<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Type hints for better IDE support
/** @var Database $database */

if (isset($_POST['action']) && $_POST['action'] == 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, username, email, password, first_name, last_name, role 
              FROM users WHERE email = ? AND is_active = TRUE";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['name'] = $row['first_name'] . ' ' . $row['last_name'];
            
            if ($row['role'] == 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../user/dashboard.php");
            }
            exit();
        } else {
            header("Location: login.php?error=invalid_credentials");
            exit();
        }
    } else {
        header("Location: login.php?error=user_not_found");
        exit();
    }
}

if (isset($_POST['action']) && $_POST['action'] == 'forgot_password') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        header("Location: login.php?error=email_not_found");
        exit();
    }

    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT id FROM users WHERE email = ? AND is_active = TRUE";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = $row['id'];

        // Generate reset token
        $reset_token = bin2hex(random_bytes(32));
        $reset_expires = date('Y-m-d H:i:s', time() + 86400); // 24 hours from now

        // Update user with token and expiry
        $update_query = "UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(1, $reset_token);
        $update_stmt->bindParam(2, $reset_expires);
        $update_stmt->bindParam(3, $user_id);

        if ($update_stmt->execute()) {
            error_log("Reset token updated successfully for user ID: $user_id, token: $reset_token, expires: $reset_expires");
            // Send reset email using PHPMailer
            /** @var PHPMailer $mail */
            $mail = new PHPMailer(true);

            try {
                //Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'riderevivehelp@gmail.com'; // Replace with your Gmail
                $mail->Password   = 'lsbh yeda kppk ekmf'; // Replace with your app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                //Recipients
                $mail->setFrom('noreply@riderevive.com', 'Ride Revive Detailing');
                $mail->addAddress($email);

                //Content
                $mail->isHTML(false);
                $mail->Subject = 'Password Reset - Ride Revive Detailing';
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/car-detailing/auth/reset_password.php?token=" . $reset_token;
                $mail->Body    = "Hello,\n\nYou requested a password reset for your Ride Revive Detailing account.\n\nClick the link below to reset your password:\n\n" . $reset_link . "\n\nThis link will expire in 24 hours.\n\nIf you did not request this, please ignore this email.\n\nBest regards,\nRide Revive Detailing Team";

                $mail->send();
                header("Location: login.php?success=reset_link_sent");
            } catch (Exception $e) {
                error_log("PHPMailer error for user $user_id: " . $e->getMessage());
                // Clear the token if email fails
                $clear_query = "UPDATE users SET reset_token = NULL, reset_expires = NULL WHERE id = ?";
                $clear_stmt = $db->prepare($clear_query);
                $clear_stmt->bindParam(1, $user_id);
                $clear_stmt->execute();
                header("Location: login.php?error=reset_failed");
            }
        } else {
            error_log("Failed to update reset token for user ID: $user_id");
            header("Location: login.php?error=reset_failed");
        }
    } else {
        header("Location: login.php?error=email_not_found");
    }
    exit();
}

if (isset($_POST['action']) && $_POST['action'] == 'register') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    
    if ($password !== $confirm_password) {
        header("Location: register.php?error=passwords_mismatch");
        exit();
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Generate username from email
    $username_base = explode('@', $email)[0];
    $username = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($username_base));
    $original_username = $username;
    $counter = 1;
    while (true) {
        $check_query = "SELECT id FROM users WHERE username = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(1, $username);
        $check_stmt->execute();
        if ($check_stmt->rowCount() == 0) {
            break;
        }
        $username = $original_username . $counter++;
    }
    
    $check_email = "SELECT id FROM users WHERE email = ?";
    $stmt_email = $db->prepare($check_email);
    $stmt_email->bindParam(1, $email);
    $stmt_email->execute();
    
    if ($stmt_email->rowCount() > 0) {
        header("Location: register.php?error=email_exists");
        exit();
    }
    
    $query = "INSERT INTO users (username, email, password, first_name, last_name, phone, role, is_active) 
              VALUES (?, ?, ?, ?, ?, ?, 'user', TRUE)";
    $stmt = $db->prepare($query);
    
    try {
        if ($stmt->execute([$username, $email, $hashed_password, $first_name, $last_name, $phone])) {
            header("Location: login.php?success=registration_complete");
        } else {
            header("Location: register.php?error=registration_failed");
        }
    } catch(PDOException $e) {
        if ($e->getCode() == 23000) {
            header("Location: register.php?error=username_exists");
        } else {
            header("Location: register.php?error=registration_failed");
        }
    }
}
?>
