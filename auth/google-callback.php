<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Load Google OAuth configuration
$google_config = require_once __DIR__ . '/../config/google_config.php';
$client_id = $google_config['client_id'];
$client_secret = $google_config['client_secret'];
$redirect_uri = $google_config['redirect_uri'];

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Exchange code for access token
    $token_url = 'https://oauth2.googleapis.com/token';
    $token_data = [
        'code' => $code,
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $token_response = curl_exec($ch);
    curl_close($ch);

    $token = json_decode($token_response, true);
    if (isset($token['access_token'])) {
        $access_token = $token['access_token'];

        // Get user info
        $user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $user_info_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $user_response = curl_exec($ch);
        curl_close($ch);

        $user = json_decode($user_response, true);
        if (isset($user['id'], $user['email'], $user['name'])) {
            $google_id = $user['id'];
            $email = $user['email'];
            $name = $user['name'];
            $given_name = $user['given_name'] ?? '';
            $family_name = $user['family_name'] ?? '';

            // Generate username from email if no username
            $username = explode('@', $email)[0]; // Simple: use local part of email

            $database = new Database();
            $db = $database->getConnection();

            // Check if user exists by google_id or email
            $check_query = "SELECT id, username, first_name, last_name, role FROM users WHERE google_id = ? OR email = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(1, $google_id);
            $check_stmt->bindParam(2, $email);
            $check_stmt->execute();

            if ($check_stmt->rowCount() > 0) {
                // User exists, log in
                $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'] ?? 'user';
                $_SESSION['name'] = $row['first_name'] . ' ' . $row['last_name'];
            } else {
                // Register new user
                $insert_query = "INSERT INTO users (google_id, username, email, first_name, last_name, role, is_active) 
                                 VALUES (?, ?, ?, ?, ?, 'user', TRUE)";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->bindParam(1, $google_id);
                $insert_stmt->bindParam(2, $username);
                $insert_stmt->bindParam(3, $email);
                $insert_stmt->bindParam(4, $given_name);
                $insert_stmt->bindParam(5, $family_name);

                if ($insert_stmt->execute()) {
                    // Set session
                    $user_id = $db->lastInsertId();
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = 'user';
                    $_SESSION['name'] = $given_name . ' ' . $family_name;
                } else {
                    header("Location: register.php?error=google_registration_failed");
                    exit();
                }
            }

            // Redirect based on role
            if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../user/dashboard_CLEAN.php");
            }
            exit();
        } else {
            header("Location: register.php?error=google_user_info_failed");
            exit();
        }
    } else {
        header("Location: register.php?error=google_token_failed");
        exit();
    }
} else {
    header("Location: register.php?error=no_code");
    exit();
}
?>
