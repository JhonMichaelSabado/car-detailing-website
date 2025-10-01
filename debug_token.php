<?php
require_once __DIR__ . '/config/database.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    echo "No token provided.\n";
    exit;
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, email, reset_token, reset_expires, is_active FROM users WHERE reset_token = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $token);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "User found:\n";
    echo "ID: " . $row['id'] . "\n";
    echo "Email: " . $row['email'] . "\n";
    echo "Reset Token: " . $row['reset_token'] . "\n";
    echo "Reset Expires: " . $row['reset_expires'] . "\n";
    echo "Is Active: " . ($row['is_active'] ? 'TRUE' : 'FALSE') . "\n";
    echo "Current Time: " . date('Y-m-d H:i:s') . "\n";
    echo "Token Valid: " . ($row['reset_expires'] > date('Y-m-d H:i:s') && $row['is_active'] ? 'YES' : 'NO') . "\n";
} else {
    echo "No user found with this token.\n";
}
?>
