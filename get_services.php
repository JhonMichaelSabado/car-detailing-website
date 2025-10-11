<?php
require_once 'config/database.php';
require_once 'includes/database_functions.php';

// Create database connection
$database = new Database();
$connection = $database->getConnection();
$db = new CarDetailingDB($connection);
$services = $db->getServices();

echo "=== SERVICE IMAGE FILENAME REFERENCE ===\n\n";
foreach($services as $service) {
    $clean_name = strtolower(str_replace([' ', '+', '(', ')'], ['-', '-', '-', ''], $service['service_name']));
    $clean_name = preg_replace('/-+/', '-', $clean_name); // Remove multiple hyphens
    $clean_name = trim($clean_name, '-'); // Remove leading/trailing hyphens
    echo "Service: " . $service['service_name'] . "\n";
    echo "Filename needed: " . $clean_name . ".jpg\n";
    echo "---\n";
}
?>