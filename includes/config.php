<?php
// Database configuration
$host = 'localhost';
$dbname = 'car_detailing';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Site configuration
define('SITE_URL', 'http://localhost/car-detailing');
define('SITE_NAME', 'CarDetailing Pro');

// Email configuration
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@cardetailing.com');
define('FROM_NAME', 'CarDetailing Pro');

// Business settings
define('FREE_RADIUS_KM', 10); // Free delivery within 10km
define('TRAVEL_FEE_PER_KM', 15); // ₱15 per km beyond free radius
define('BUSINESS_LAT', 14.5995); // Manila coordinates
define('BUSINESS_LNG', 120.9842);

// Google Maps API
define('GOOGLE_MAPS_API_KEY', 'your_google_maps_api_key_here');
?>