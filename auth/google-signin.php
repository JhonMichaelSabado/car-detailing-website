<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (isset($_POST['action']) && $_POST['action'] == 'save_name') {
    if (!isset($_SESSION['google_temp'])) {
        echo json_encode(['success' => false, 'error' => 'No Google data found']);
        exit();
    }

    $temp = $_SESSION['google_temp'];
    $full_name = trim($_POST['full_name']);
    if (empty($full_name)) {
        echo json_encode(['success' => false, 'error' => 'Full name is required']);
        exit();
    }

    // Split full name into first and last
    $name_parts = explode(' ', $full_name, 2);
    $first_name = $name_parts[0];
    $last_name = isset($name_parts[1]) ? $name_parts[1] : '';

    $database = new Database();
    $db = $database->getConnection();

    // Generate username from email if no username
    $username = preg_replace('/[^a-zA-Z0-9]/', '', strtolower(explode('@', $temp['email'])[0]));

    // Check if username exists, append number if needed
    $base_username = $username;
    $counter = 1;
    while (true) {
        $check_user_query = "SELECT id FROM users WHERE username = ?";
        $check_user_stmt = $db->prepare($check_user_query);
        $check_user_stmt->bindParam(1, $username);
        $check_user_stmt->execute();
        if ($check_user_stmt->rowCount() == 0) break;
        $username = $base_username . $counter++;
    }

    // Insert new user
    $insert_query = "INSERT INTO users (google_id, username, email, first_name, last_name, role, is_active) 
                     VALUES (?, ?, ?, ?, ?, 'user', TRUE)";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->bindParam(1, $temp['google_id']);
    $insert_stmt->bindParam(2, $username);
    $insert_stmt->bindParam(3, $temp['email']);
    $insert_stmt->bindParam(4, $first_name);
    $insert_stmt->bindParam(5, $last_name);

    if ($insert_stmt->execute()) {
        $user_id = $db->lastInsertId();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'user';
        $_SESSION['name'] = $full_name;
        unset($_SESSION['google_temp']);
        echo json_encode(['success' => true, 'redirect' => '../user/dashboard_CLEAN.php']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Registration failed']);
    }
    exit();
}

if (isset($_POST['action']) && $_POST['action'] == 'google_signup') {
    $id_token = $_POST['id_token'];

    // Verify ID token with Google
    $token_info_url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($id_token);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_info_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $token_info = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($http_code !== 200 || !empty($curl_error)) {
        echo json_encode(['success' => false, 'error' => 'Token verification failed', 'http_code' => $http_code, 'curl_error' => $curl_error, 'response' => $token_info]);
        exit();
    }

    $user = json_decode($token_info, true);

    $google_config = require_once __DIR__ . '/../config/google_config.php';
    if (!isset($user['aud']) || $user['aud'] !== $google_config['client_id'] || 
        !isset($user['email_verified']) || !$user['email_verified']) {
        echo json_encode(['success' => false, 'error' => 'Invalid or expired token']);
        exit();
    }

    $google_id = $user['sub'];
    $email = $user['email'];
    $name = $user['name'];
    $given_name = $user['given_name'] ?? substr($name, 0, strpos($name, ' '));
    $family_name = $user['family_name'] ?? substr($name, strpos($name, ' ') + 1);
    $username = preg_replace('/[^a-zA-Z0-9]/', '', strtolower(explode('@', $email)[0]));

    $database = new Database();
    $db = $database->getConnection();

    // Check if user exists
    $check_query = "SELECT id, username, first_name, last_name, role FROM users WHERE google_id = ? OR email = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(1, $google_id);
    $check_stmt->bindParam(2, $email);
    $check_stmt->execute();

    if ($check_stmt->rowCount() > 0) {
        $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'] ?? 'user';
        $_SESSION['name'] = trim($row['first_name'] . ' ' . $row['last_name']);
        $redirect = ($row['role'] == 'admin') ? '../admin/dashboard.php' : '../user/dashboard_CLEAN.php';
    } else {
        // Store Google data temporarily for name entry
        $_SESSION['google_temp'] = [
            'google_id' => $google_id,
            'email' => $email,
            'given_name' => $given_name,
            'family_name' => $family_name
        ];
        $redirect = 'google-name-entry.php';
    }

    echo json_encode(['success' => true, 'redirect' => $redirect]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>
