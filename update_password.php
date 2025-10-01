<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    $email = 'admin@cardetailing.com';
    $new_password = 'admin123';
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $query = "UPDATE users SET password = ? WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $hashed_password);
    $stmt->bindParam(2, $email);

    if ($stmt->execute()) {
        echo "Password updated successfully for $email.\n";
    } else {
        echo "Failed to update password.\n";
    }
} else {
    echo "Database connection failed.\n";
}
?>
